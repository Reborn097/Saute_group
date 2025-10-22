<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Categoria;  
use App\Models\Producto;   
use App\Models\ProductoProveedor; 

class ProductoController extends Controller
{
    public function index()
{
    $productos = Producto::with(['categoria', 'proveedor'])->get();
    return view('dashboard.productos', compact('productos'));
}


    public function crearCategoria()
{
    // Retorna la vista para agregar una nueva categoría
    return view('dashboard.agregar_categoria');
}

public function crearProducto()
{
    // Retorna la vista para agregar un nuevo producto
    $categorias = Categoria::all();
    return view('dashboard.agregar_producto', compact('categorias'));
}

public function editar($id)
{
    $producto = Producto::with(['categoria', 'proveedor'])->findOrFail($id);
    $categorias = Categoria::all();
    $proveedores = Proveedor::all();

    return view('dashboard.editar_producto', compact('producto', 'categorias', 'proveedores'));
}


public function actualizar(Request $request, $id)
{
    $producto = Producto::findOrFail($id);
    $producto->update($request->all());

    return redirect()->route('dashboard.productos')->with('success', 'Producto actualizado correctamente.');
}


    public function guardarCategoria(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
        'descripcion' => 'nullable|string',
        'estado' => 'required|string',
    ]);

    \App\Models\Categoria::create([
        'nombre' => $request->nombre,
        'descripcion' => $request->descripcion,
        'estado' => $request->estado,
    ]);

    return redirect()->route('dashboard.productos')->with('success', 'Categoría agregada correctamente.');
}


public function guardar(Request $request)
{
    //dd($request->all());

    $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|integer',
            'valor_medida' => 'nullable|string|max:50',
            'unidad_medida' => 'nullable|string|max:50',
            'proveedor_id' => 'required|integer',
            'precio' => 'required|numeric|min:0',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_final' => 'required|date|after_or_equal:fecha_vigencia_inicio',
        ]);

        // 1️⃣ Guardar producto
        $producto = Producto::create([
            'nombre' => $request->nombre,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'categoria_id' => $request->categoria_id,
            'estado' => 1
        ]);

        // 2️⃣ Guardar relación producto-proveedor con precio y fechas
        ProductoProveedor::create([
            'producto_id' => $producto->id,
            'proveedor_id' => $request->proveedor_id,
            'precio' => $request->precio,
            'fecha_vigencia_inicio' => $request->fecha_vigencia_inicio,
            'fecha_vigencia_final' => $request->fecha_vigencia_final,
            'estado' => 1
        ]);

        return redirect()->route('dashboard.productos')->with('success', 'Producto guardado correctamente');
    }



}
