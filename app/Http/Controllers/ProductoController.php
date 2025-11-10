<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Categoria;  
use App\Models\Producto;
use App\Models\ProductoProveedor;

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
        return view('dashboard.agregar_producto', compact('categorias'));
    }

    /** 🔹 Vista para editar producto */
    public function editar($id)
    {
        $producto = Producto::with(['categoria', 'proveedores'])->findOrFail($id);
        $categorias = Categoria::all();
        $proveedores = Proveedor::all();

        return view('dashboard.editar_producto', compact('producto', 'categorias', 'proveedores'));
    }

    /** 🔹 Actualizar producto y sus proveedores */
    public function actualizar(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        // ✅ Validar los datos
        $request->validate([
            'nombre' => 'required|string|max:255',
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

        // ✅ 1️⃣ Actualizar los datos principales del producto
        $producto->update([
            'nombre' => $request->nombre,
            'categoria_id' => $request->categoria_id,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'estado' => $request->estado,
        ]);

        // ✅ 2️⃣ Eliminar relaciones antiguas para volver a guardar las nuevas
        $producto->proveedores()->detach();

        // ✅ 3️⃣ Registrar los proveedores actualizados
        foreach ($request->proveedores as $prov) {
            $producto->proveedores()->attach($prov['id'], [
                'precio' => $prov['precio'],
                'fecha_vigencia_inicio' => $prov['fecha_vigencia_inicio'],
                'fecha_vigencia_final' => $prov['fecha_vigencia_final'],
                'estado' => 1,
            ]);
        }

        // ✅ 4️⃣ Redirigir con éxito
        return redirect()
            ->route('dashboard.productos')
            ->with('success', '✅ Producto actualizado correctamente junto con sus proveedores.');
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

    /** 🔹 Guardar nuevo producto con múltiples proveedores */
    public function guardar(Request $request)
    {
        // ✅ Validar los campos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'valor_medida' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'proveedores' => 'required|array|min:1',
            'proveedores.*.id' => 'required|integer|exists:proveedores,id',
            'proveedores.*.precio' => 'required|numeric|min:0',
            'proveedores.*.fecha_vigencia_inicio' => 'required|date',
            'proveedores.*.fecha_vigencia_final' => 'required|date|after_or_equal:proveedores.*.fecha_vigencia_inicio',
        ]);

        // ✅ 1️⃣ Crear producto
        $producto = Producto::create([
            'nombre' => $request->nombre,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'categoria_id' => $request->categoria_id,
            'estado' => 1,
        ]);

        // ✅ 2️⃣ Asociar proveedores con precios
        foreach ($request->proveedores as $prov) {
            ProductoProveedor::create([
                'producto_id' => $producto->id,
                'proveedor_id' => $prov['id'],
                'precio' => $prov['precio'],
                'fecha_vigencia_inicio' => $prov['fecha_vigencia_inicio'],
                'fecha_vigencia_final' => $prov['fecha_vigencia_final'],
                'estado' => 1,
            ]);
        }

        // ✅ 3️⃣ Redirigir
        return redirect()
            ->route('dashboard.productos')
            ->with('success', '✅ Producto guardado correctamente con sus proveedores.');
    }
}
