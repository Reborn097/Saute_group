<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

use App\Models\PedidoEspecial;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\ProductoProveedor;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\UnidadOperativa;

class PedidoEspecialController extends Controller
{
    // ==========================
    // Helpers (igual idea que PedidoController)
    // ==========================
    private function role(): string
    {
        return Auth::user()->role ?? '';
    }

    private function esAdmin(): bool
    {
        return in_array($this->role(), ['admin', 'encargado_pedidos']);
    }

    private function estadoInicial(): string
    {
        return 'Pendiente';
    }

    // ==========================
    // FORM CREAR PEDIDO ESPECIAL
    // ==========================
    public function crear(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id');
        $categoriaId = $request->get('categoria_id');

        $productosQuery = Producto::query()
            ->with([
                'categoria',
                'proveedores' => function ($q) {
                    // importante: pivot->id = producto_proveedor.id
                    $q->select('proveedores.id', 'nombre')
                      ->withPivot('id', 'precio');
                }
            ]);

        if ($q !== '') {
            $productosQuery->where('nombre', 'like', "%{$q}%");
        }

        if (!empty($categoriaId)) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        if (!empty($proveedorId)) {
            $productosQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                $sub->where('proveedores.id', $proveedorId);
            });
        }

        $productos = $productosQuery
            ->orderBy('nombre')
            ->paginate(10)
            ->appends($request->query());

        $proveedores = Proveedor::orderBy('nombre')->get();
        $categorias  = Categoria::orderBy('nombre')->get();

        // ✅ para selector en vista cuando sea admin (igual que pedido normal)
        $unidadesOperativas = $this->esAdmin()
            ? UnidadOperativa::orderBy('nombre')->get()
            : collect();

        return view('dashboard.crear_pedido_especial', compact(
            'productos',
            'proveedores',
            'categorias',
            'unidadesOperativas'
        ));
    }

    // ==========================
    // PREVISUALIZACIÓN
    // ==========================
    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido_especial');
    }

    // ==========================
    // GUARDAR (PEDIDO + DETALLES + PEDIDO_ESPECIAL) EN TRANSACCIÓN
    // Recibe PDFs en BASE64
    // ==========================
    public function guardar(Request $request)
    {
        try {
            $request->validate([
                'fecha_solicitud'   => 'required|date',
                'fecha_entrega'     => 'required|date',
                'productos'         => 'required', // puede ser json string o array
                'pdf_solicitud'     => 'required',
                'pdf_cotizacion'    => 'required',
                'pdf_autorizacion'  => 'required',

                // si admin manda unidad
                'unidad_operativa_id' => 'nullable|integer',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No autenticado.'
                ], 401);
            }

            // ✅ Determinar unidad
            $unidadOperativaId = null;

            if ($this->esAdmin()) {
                // admin debe elegir (o si quieres permitir "por defecto" la del admin, ajustas aquí)
                $unidadOperativaId = $request->get('unidad_operativa_id') ?: null;
                if (!$unidadOperativaId) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Selecciona una unidad operativa para el pedido especial.'
                    ], 422);
                }
            } else {
                $unidadOperativaId = $user->unidad_operativa_id ?? null;
                if (!$unidadOperativaId) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Tu usuario no tiene unidad operativa asignada. No se puede crear el pedido especial.'
                    ], 422);
                }
            }

            // ✅ Parse productos (string JSON o array)
            $productos = $request->productos;
            if (is_string($productos)) {
                $productos = json_decode($productos, true);
            }
            if (!is_array($productos) || count($productos) === 0) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No se recibieron productos válidos.'
                ], 422);
            }

            // Guardar PDFs antes o dentro: yo lo hago dentro pero guardando rutas;
            // si falla algo, la transacción revierte BD, pero los archivos quedarían.
            // (Eso normalmente está bien. Si quieres “limpiar archivos” cuando falle, te lo armo.)
            $rutaSolicitud    = $this->guardarBase64($request->pdf_solicitud,    'pdfs_especiales');
            $rutaCotizacion   = $this->guardarBase64($request->pdf_cotizacion,   'pdfs_especiales');
            $rutaAutorizacion = $this->guardarBase64($request->pdf_autorizacion, 'pdfs_especiales');

            $pedido = null;

            DB::transaction(function () use (
                &$pedido,
                $request,
                $user,
                $unidadOperativaId,
                $productos,
                $rutaSolicitud,
                $rutaCotizacion,
                $rutaAutorizacion
            ) {
                // ✅ retry por colisión de código (requiere UNIQUE en pedidos.codigo)
                $intentos = 0;
                while (true) {
                    $intentos++;
                    try {
                        $pedido = Pedido::create([
                            'codigo'              => Pedido::generarCodigo(),
                            'fecha_solicitud'     => $request->fecha_solicitud,
                            'fecha_entrega'       => $request->fecha_entrega,
                            'estado'              => $this->estadoInicial(),
                            'user_id'             => $user->id,
                            'unidad_operativa_id' => (int)$unidadOperativaId,
                            'total'               => 0,
                            'es_especial'         => 1,
                        ]);
                        break;
                    } catch (QueryException $e) {
                        $sqlState   = $e->errorInfo[0] ?? null;
                        $driverCode = $e->errorInfo[1] ?? null;
                        $esDuplicado = ($sqlState === '23000' && (int)$driverCode === 1062);

                        if ($esDuplicado && $intentos < 5) {
                            continue;
                        }
                        throw $e;
                    }
                }

                $total = 0;

                foreach ($productos as $p) {
                    // Preferimos usar producto_proveedor_id (más consistente)
                    $ppId = $p['producto_proveedor_id'] ?? null;

                    // compat: si llega producto_id + proveedor_id, lo resolvemos
                    if (!$ppId && isset($p['producto_id'], $p['proveedor_id'])) {
                        $pp = ProductoProveedor::where('producto_id', $p['producto_id'])
                            ->where('proveedor_id', $p['proveedor_id'])
                            ->first();
                        $ppId = $pp?->id;
                    }

                    if (!$ppId) {
                        // no truena todo, solo ignora línea mala
                        continue;
                    }

                    $cantidad = isset($p['cantidad']) ? (float)$p['cantidad'] : 0;
                    $precio   = isset($p['precio']) ? (float)$p['precio'] : 0;

                    if ($cantidad < 0) $cantidad = 0;
                    if ($precio < 0) $precio = 0;

                    $subtotal = $cantidad * $precio;
                    $total += $subtotal;

                    DetallePedido::create([
                        'codigo'                => $pedido->codigo,
                        'producto_proveedor_id' => $ppId,
                        'cantidad_solicitada'   => $cantidad,
                        'cantidad_aprobada'     => $cantidad, // default igual
                        'precio_unitario'       => $precio,
                        'subtotal'              => $subtotal,
                        'activo'                => 1,
                    ]);
                }

                $pedido->update(['total' => $total]);

                // ✅ Inserta pedido especial (sin id manual)
                PedidoEspecial::create([
                    'codigo'        => $pedido->codigo,
                    'solicitud'     => $rutaSolicitud,
                    'cotizacion'    => $rutaCotizacion,
                    'autorizacion'  => $rutaAutorizacion,
                ]);
            });

            return response()->json([
                'success' => true,
                'codigo'  => $pedido->codigo,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ==========================
    // Guardar base64 como PDF
    // ==========================
    private function guardarBase64($base64, $folder): string
    {
        if (is_string($base64) && str_contains($base64, ',')) {
            $base64 = explode(',', $base64)[1];
        }

        $pdfData = base64_decode((string)$base64);
        if ($pdfData === false) {
            // evita guardar basura
            throw new \Exception('PDF inválido (base64 no se pudo decodificar).');
        }

        $fileName = $folder . "/" . uniqid('esp_') . ".pdf";

        Storage::disk('public')->put($fileName, $pdfData);

        return "storage/" . $fileName;
    }

    public function buscarProductos(Request $request)
{
    $q           = trim((string) $request->get('q', ''));
    $proveedorId = $request->get('proveedor_id');
    $categoriaId = $request->get('categoria_id');

    // Para NO-ADMIN: mínimo 2 letras (tu front hace lo mismo)
    if (mb_strlen($q) < 2) {
        return response()->json([
            'data' => [],
            'meta' => ['current_page' => 1, 'last_page' => 1]
        ]);
    }

    $productosQuery = Producto::query()
        ->with([
            'categoria',
            'proveedores' => function ($q) {
                $q->select('proveedores.id', 'nombre')
                  ->withPivot('precio'); // si ocupas pivot->id agrega ->withPivot('id','precio')
            }
        ])
        ->where('nombre', 'like', "%{$q}%");

    if (!empty($categoriaId)) {
        $productosQuery->where('categoria_id', $categoriaId);
    }

    if (!empty($proveedorId)) {
        $productosQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
            $sub->where('proveedores.id', $proveedorId);
        });
    }

    $productos = $productosQuery
        ->orderBy('nombre')
        ->paginate(10);

    return response()->json([
        'data' => $productos->items(),
        'meta' => [
            'current_page' => $productos->currentPage(),
            'last_page'    => $productos->lastPage(),
            'total'        => $productos->total(),
        ]
    ]);
}

}
