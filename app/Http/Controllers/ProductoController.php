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
    /** 🔹 Listado de productos */
    public function index()
    {
        $productos = Producto::with(['categoria', 'proveedores'])->get();
        return view('dashboard.productos', compact('productos'));
    }

    /** 🔹 Vista para crear categoría */
    public function crearCategoria()
    {
        return view('dashboard.agregar_categoria');
    }

    /** 🔹 Vista para crear producto */
    public function crearProducto()
    {
        $categorias = Categoria::all();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('dashboard.agregar_producto', compact('categorias','proveedores'));
    }

    /** 🔹 Vista para editar producto */
    public function editar($id)
    {
        $producto = Producto::with(['categoria', 'proveedores'])->findOrFail($id);
        $categorias = Categoria::all();
        $proveedores = Proveedor::all();

        return view('dashboard.editar_producto', compact('producto', 'categorias', 'proveedores'));
    }

    /** 🔹 Actualizar producto y sus proveedores (SIN DETACH + con historial) */
    public function actualizar(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca'  => 'nullable|string|max:255', // ✅ NUEVO
            'categoria_id' => 'required|integer|exists:categorias,id',
            'valor_medida' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'estado' => 'required|boolean',

            // OJO: Si ya decidiste que aquí NO se toquen precios/proveedores,
            // esto se debe quitar. Pero por ahora lo dejo tal cual lo traes.
            'proveedores' => 'required|array|min:1',
            'proveedores.*.id' => 'required|integer|exists:proveedores,id',
            'proveedores.*.precio' => 'required|numeric|min:0',
            'proveedores.*.fecha_vigencia_inicio' => 'required|date',
            'proveedores.*.fecha_vigencia_final' => 'required|date|after_or_equal:proveedores.*.fecha_vigencia_inicio',
        ]);

        // ✅ 1) Actualizar datos del producto
        $producto->update([
            'nombre' => $request->nombre,
            'marca'  => $request->marca, // ✅ NUEVO
            'categoria_id' => $request->categoria_id,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'estado' => $request->estado,
        ]);

        // ✅ 2) IDs de proveedores enviados
        $proveedoresIds = collect($request->proveedores)->pluck('id')->toArray();

        // ✅ 3) Desactivar pivots que ya no vienen
        ProductoProveedor::where('producto_id', $producto->id)
            ->whereNotIn('proveedor_id', $proveedoresIds)
            ->update(['estado' => 0]);

        // ✅ 4) Crear/Actualizar pivots + guardar historial si cambió
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
                || (float)$ppActual->precio !== (float)$prov['precio']
                || (string)$ppActual->fecha_vigencia_inicio !== (string)$prov['fecha_vigencia_inicio']
                || (string)$ppActual->fecha_vigencia_final !== (string)$prov['fecha_vigencia_final']
                || (int)$ppActual->estado !== 1;

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

    /** 🔹 Guardar nuevo producto con múltiples proveedores (+ historial recomendado) */
    public function guardar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca'  => 'nullable|string|max:255', // ✅ NUEVO
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
            'marca'  => $request->marca, // ✅ NUEVO
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
}
