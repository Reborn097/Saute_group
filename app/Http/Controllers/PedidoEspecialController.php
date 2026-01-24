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
    // Helpers
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

    /**
     * ✅ Proveedor principal para un producto (para NO-admin)
     * Ajusta el orderBy si tienes campo "preferido", "activo", etc.
     */
    private function resolverProductoProveedorPrincipal(int $productoId): ?ProductoProveedor
    {
        return ProductoProveedor::where('producto_id', $productoId)
            // ->where('activo', 1)              // si existe
            // ->orderByDesc('preferido')        // si existe
            ->orderBy('id')                      // default: el "primero"
            ->first();
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
                    // pivot->id = producto_proveedor.id
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

        // ✅ Igual que pedido normal: dar default (pp) a la vista
        $productos->getCollection()->transform(function ($prod) {
            $primero = $prod->proveedores->first(); // ya viene con pivot(id,precio)
            $prod->pp_default_id = $primero?->pivot?->id;
            $prod->pp_default_precio = (float)($primero?->pivot?->precio ?? 0);
            return $prod;
        });

        $proveedores = Proveedor::orderBy('nombre')->get();
        $categorias  = Categoria::orderBy('nombre')->get();

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

            // PDFs
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
                // ✅ retry por colisión de código
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

                    // 1) Intentar tomar ppId directo
                    $ppId = $p['producto_proveedor_id'] ?? null;

                    // 2) compat: resolver por producto_id + proveedor_id (admin podría mandarlo así)
                    if (!$ppId && isset($p['producto_id'], $p['proveedor_id'])) {
                        $pp = ProductoProveedor::where('producto_id', (int)$p['producto_id'])
                            ->where('proveedor_id', (int)$p['proveedor_id'])
                            ->first();
                        $ppId = $pp?->id;
                    }

                    if (!$ppId) {
                        continue;
                    }

                    // ✅ Cargar pp para conocer producto_id y validar/forzar
                    $pp = ProductoProveedor::with(['producto', 'proveedor'])->find($ppId);
                    if (!$pp) {
                        continue;
                    }

                    // 🔒 NO-ADMIN: forzar proveedor principal del producto (anti-hack)
                    if (!$this->esAdmin()) {
                        $ppPrincipal = $this->resolverProductoProveedorPrincipal((int)$pp->producto_id);
                        if ($ppPrincipal) {
                            $ppId = $ppPrincipal->id;
                            $pp = ProductoProveedor::with(['producto', 'proveedor'])->find($ppId);
                        }
                    }

                    $cantidad = isset($p['cantidad']) ? (float)$p['cantidad'] : 0;

                    // ✅ Precio:
                    // - Admin puede mandar el precio (cotización especial)
                    // - No-admin: si manda algo raro, igual lo aceptas si quieres,
                    //   pero lo más consistente es usar el precio del pp (o el que mande si así operas).
                    // Aquí dejamos: si no viene, usa pp->precio.
                    $precio = isset($p['precio'])
                        ? (float)$p['precio']
                        : (float)($pp->precio ?? 0);

                    if ($cantidad < 0) $cantidad = 0;
                    if ($precio < 0) $precio = 0;

                    $subtotal = $cantidad * $precio;
                    $total += $subtotal;

                    DetallePedido::create([
                        'codigo'                => $pedido->codigo,
                        'producto_proveedor_id' => $ppId,
                        'cantidad_solicitada'   => $cantidad,
                        'cantidad_aprobada'     => $cantidad,
                        'precio_unitario'       => $precio,
                        'subtotal'              => $subtotal,
                        'activo'                => 1,
                    ]);
                }

                $pedido->update(['total' => $total]);

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
            throw new \Exception('PDF inválido (base64 no se pudo decodificar).');
        }

        $fileName = $folder . "/" . uniqid('esp_') . ".pdf";
        Storage::disk('public')->put($fileName, $pdfData);

        return "storage/" . $fileName;
    }

    // ==========================
    // BUSCADOR AJAX (si lo usas)
    // ==========================
    public function buscarProductos(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id');
        $categoriaId = $request->get('categoria_id');

        // ✅ Para NO-ADMIN: mínimo 2 letras
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
                    // ✅ necesitamos pivot->id y precio para seleccionar proveedor (admin)
                    $q->select('proveedores.id', 'nombre')
                      ->withPivot('id', 'precio');
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

        // ✅ agregar default (pp) en el payload JSON
        $items = collect($productos->items())->map(function ($prod) {
            $primero = $prod->proveedores->first();
            $prod->pp_default_id = $primero?->pivot?->id;
            $prod->pp_default_precio = (float)($primero?->pivot?->precio ?? 0);
            return $prod;
        })->values()->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $productos->currentPage(),
                'last_page'    => $productos->lastPage(),
                'total'        => $productos->total(),
            ]
        ]);
    }
}
