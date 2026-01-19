<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoProveedor;
use App\Models\HistorialPrecio;

class ProductoController extends Controller
{
    /**
     * 🔹 Listado de productos (PAGINADO + BUSCADOR + FILTROS por categoría y proveedor)
     * GET /dashboard/productos?q=&categoria_id=&proveedor_id=
     */
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));
        $categoriaId = $request->get('categoria_id');
        $proveedorId = $request->get('proveedor_id');

        $query = Producto::query()
            ->with([
                'categoria:id,nombre',
                'proveedores' => function ($q) {
                    $q->select('proveedores.id', 'proveedores.nombre');
                }
            ])
            ->orderBy('id', 'desc');

        // 🔎 búsqueda por nombre / marca / unidad_medida
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'like', "%{$q}%")
                    ->orWhere('marca', 'like', "%{$q}%")
                    ->orWhere('unidad_medida', 'like', "%{$q}%");
            });
        }

        // 🧩 filtro por categoría
        if (!empty($categoriaId)) {
            $query->where('categoria_id', $categoriaId);
        }

        // 🏷️ filtro por proveedor (productos que tengan ese proveedor en pivote)
        if (!empty($proveedorId)) {
            $query->whereHas('proveedores', function ($p) use ($proveedorId) {
                $p->where('proveedores.id', $proveedorId);
            });
        }

        // ✅ paginado (ajusta el 10 a tu gusto)
        $productos = $query->paginate(10)->appends($request->query());

        // ✅ catálogos para los filtros en la vista
        $categorias = Categoria::orderBy('nombre', 'asc')->get(['id', 'nombre']);
        $proveedores = Proveedor::orderBy('nombre', 'asc')->get(['id', 'nombre']);

        return view('dashboard.productos', compact('productos', 'categorias', 'proveedores', 'q', 'categoriaId', 'proveedorId'));
    }

    /** 🔹 Vista para crear categoría */
    public function crearCategoria()
    {
        return view('dashboard.agregar_categoria');
    }

    /** 🔹 Vista para crear producto */
    public function crearProducto()
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('dashboard.agregar_producto', compact('categorias', 'proveedores'));
    }

    /** 🔹 Vista para editar producto */
    public function editar($id)
    {
        $producto = Producto::with(['categoria', 'proveedores'])->findOrFail($id);
        $categorias = Categoria::orderBy('nombre')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('dashboard.editar_producto', compact('producto', 'categorias', 'proveedores'));
    }

    /** 🔹 Actualizar producto y sus proveedores (SIN DETACH + con historial) */
    public function actualizar(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca'  => 'nullable|string|max:255',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'valor_medida' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'estado' => 'required|boolean',

            'proveedores' => 'required|array|min:1',
            'proveedores.*.id' => 'required|integer|exists:proveedores,id',
            'proveedores.*.precio' => 'required|numeric|min:0',
            'proveedores.*.fecha_vigencia_inicio' => 'required|date',
            'proveedores.*.fecha_vigencia_final' => 'required|date|after_or_equal:proveedores.*.fecha_vigencia_inicio',
        ]);

        // ✅ 1) Actualizar producto
        $producto->update([
            'nombre' => $request->nombre,
            'marca'  => $request->marca,
            'categoria_id' => $request->categoria_id,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'estado' => $request->estado,
        ]);

        // ✅ 2) IDs enviados
        $proveedoresIds = collect($request->proveedores)->pluck('id')->toArray();

        // ✅ 3) Desactivar pivots que ya no vienen
        ProductoProveedor::where('producto_id', $producto->id)
            ->whereNotIn('proveedor_id', $proveedoresIds)
            ->update(['estado' => 0]);

        // ✅ 4) Crear/Actualizar pivots + historial si cambió
        foreach ($request->proveedores as $prov) {

            $ppActual = ProductoProveedor::where('producto_id', $producto->id)
                ->where('proveedor_id', $prov['id'])
                ->first();

            $pp = ProductoProveedor::updateOrCreate(
                [
                    'producto_id' => $producto->id,
                    'proveedor_id' => $prov['id'],
                ],
                [
                    'precio' => $prov['precio'],
                    'fecha_vigencia_inicio' => $prov['fecha_vigencia_inicio'],
                    'fecha_vigencia_final' => $prov['fecha_vigencia_final'],
                    'estado' => 1,
                ]
            );

            $cambio = !$ppActual
                || (float) $ppActual->precio !== (float) $prov['precio']
                || (string) $ppActual->fecha_vigencia_inicio !== (string) $prov['fecha_vigencia_inicio']
                || (string) $ppActual->fecha_vigencia_final !== (string) $prov['fecha_vigencia_final']
                || (int) $ppActual->estado !== 1;

            if ($cambio) {
                HistorialPrecio::create([
                    'producto_proveedor_id' => $pp->id,
                    'precio' => $prov['precio'],
                    'fecha_vigencia_inicio' => $prov['fecha_vigencia_inicio'],
                    'fecha_vigencia_final' => $prov['fecha_vigencia_final'],
                ]);
            }
        }

        return redirect()
            ->route('dashboard.productos')
            ->with('success', '✅ Producto actualizado sin romper historial.');
    }

    /** 🔹 Guardar nueva categoría */
    public function guardarCategoria(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|integer',
        ]);

        Categoria::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado,
        ]);

        return redirect()->route('dashboard.productos')
            ->with('success', 'Categoría agregada correctamente.');
    }

    /** 🔹 Guardar nuevo producto con múltiples proveedores (+ historial) */
    public function guardar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca'  => 'nullable|string|max:255',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'valor_medida' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'proveedores' => 'required|array|min:1',
            'proveedores.*.id' => 'required|integer|exists:proveedores,id',
            'proveedores.*.precio' => 'required|numeric|min:0',
            'proveedores.*.fecha_vigencia_inicio' => 'required|date',
            'proveedores.*.fecha_vigencia_final' => 'required|date|after_or_equal:proveedores.*.fecha_vigencia_inicio',
        ]);

        $producto = Producto::create([
            'nombre' => $request->nombre,
            'marca'  => $request->marca,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'categoria_id' => $request->categoria_id,
            'estado' => 1,
        ]);

        foreach ($request->proveedores as $prov) {

            $pp = ProductoProveedor::create([
                'producto_id' => $producto->id,
                'proveedor_id' => $prov['id'],
                'precio' => $prov['precio'],
                'fecha_vigencia_inicio' => $prov['fecha_vigencia_inicio'],
                'fecha_vigencia_final' => $prov['fecha_vigencia_final'],
                'estado' => 1,
            ]);

            HistorialPrecio::create([
                'producto_proveedor_id' => $pp->id,
                'precio' => $prov['precio'],
                'fecha_vigencia_inicio' => $prov['fecha_vigencia_inicio'],
                'fecha_vigencia_final' => $prov['fecha_vigencia_final'],
            ]);
        }

        return redirect()
            ->route('dashboard.productos')
            ->with('success', '✅ Producto guardado correctamente con proveedores e historial.');
    }

    public function pedidosDiarios()
    {
        return $this->hasMany(PedidoDiarioDetalle::class, 'producto_id');
    }

}
