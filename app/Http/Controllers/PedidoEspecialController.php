<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedidoEspecial;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Categoria;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PedidoEspecialController extends Controller
{
    /**
     * Mostrar formulario de crear pedido especial (con filtros + paginación)
     */
    public function crear(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id'); // id
        $categoriaId = $request->get('categoria_id'); // id

        $productosQuery = Producto::query()
            ->with([
                'categoria',
                'proveedores' => function ($q) {
                    $q->select('proveedores.id', 'nombre')
                      ->withPivot('precio'); // aquí normalmente hay precio (si ocupas pivot->id, agrégalo)
                }
            ]);

        // 🔎 Buscar por nombre
        if ($q !== '') {
            $productosQuery->where('nombre', 'like', "%{$q}%");
        }

        // 🧩 Filtro por categoría
        if (!empty($categoriaId)) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        // 🏷️ Filtro por proveedor
        if (!empty($proveedorId)) {
            $productosQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                $sub->where('proveedores.id', $proveedorId);
            });
        }

        // ✅ 10 por página + conservar filtros
        $productos = $productosQuery
            ->orderBy('nombre')
            ->paginate(10)
            ->appends($request->query());

        // combos de filtros
        $proveedores = Proveedor::orderBy('nombre')->get();
        $categorias  = Categoria::orderBy('nombre')->get();

        return view('dashboard.crear_pedido_especial', compact('productos', 'proveedores', 'categorias'));
    }

    /**
     * Vista de previsualización
     */
    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido_especial');
    }

    /**
     * 🟢 GUARDAR PEDIDO ESPECIAL EN BD
     * Recibe PDFs en BASE64
     */
    public function guardar(Request $request)
    {
        try {

            $request->validate([
                'fecha_solicitud' => 'required|date',
                'fecha_entrega'   => 'required|date',
                'productos'       => 'required',
                'pdf_solicitud'   => 'required',
                'pdf_cotizacion'  => 'required',
                'pdf_autorizacion'=> 'required',
            ]);

            // 1️⃣ Generar código y crear pedido normal
            $codigo = Pedido::generarCodigo();

            $pedido = Pedido::create([
                'codigo'          => $codigo,
                'fecha_solicitud' => $request->fecha_solicitud,
                'fecha_entrega'   => $request->fecha_entrega,
                'user_id'         => auth()->id(),
                'total'           => 0,
                'estado'          => 'Pendiente',
                'es_especial'     => 1,
            ]);

            // 2️⃣ Insertar productos
            $productos = json_decode($request->productos, true);
            $total = 0;

            foreach ($productos as $p) {

                $productoProveedor = DB::table('producto_proveedor')
                    ->where('producto_id', $p['producto_id'])
                    ->where('proveedor_id', $p['proveedor_id'])
                    ->first();

                if (!$productoProveedor) {
                    throw new \Exception("Relación producto-proveedor no encontrada.");
                }

                DB::table('detalle_pedidos')->insert([
                    'codigo'                => $codigo,
                    'producto_proveedor_id' => $productoProveedor->id,
                    'precio_unitario'       => $p['precio'],
                    'cantidad_solicitada'   => $p['cantidad'],
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                $total += $p['subtotal'];
            }

            $pedido->update(['total' => $total]);

            // 3️⃣ Guardar PDFs
            $rutaSolicitud    = $this->guardarBase64($request->pdf_solicitud,    'pdfs_especiales');
            $rutaCotizacion   = $this->guardarBase64($request->pdf_cotizacion,   'pdfs_especiales');
            $rutaAutorizacion = $this->guardarBase64($request->pdf_autorizacion, 'pdfs_especiales');

            // 4️⃣ Guardar pedido especial
            PedidoEspecial::create([
                'id_pedido_especial' => uniqid(),
                'solicitud'          => $rutaSolicitud,
                'cotizacion'         => $rutaCotizacion,
                'autorizacion'       => $rutaAutorizacion,
                'codigo'             => $codigo
            ]);

            return response()->json([
                'success' => true,
                'codigo'  => $codigo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔧 Función para convertir base64 → archivo físico
     */
    private function guardarBase64($base64, $folder)
    {
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64)[1];
        }

        $pdfData = base64_decode($base64);
        $fileName = $folder . "/" . uniqid() . ".pdf";

        Storage::disk('public')->put($fileName, $pdfData);

        return "storage/" . $fileName;
    }
}
