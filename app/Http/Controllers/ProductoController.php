<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Categoria;  
use App\Models\Producto;    

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


public function guardarProducto(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
        'categoria_id' => 'required|exists:categorias,id',
        'proveedor_id' => 'required|exists:proveedores,id', // ✅ Nuevo
        'valor_medida' => 'nullable|string|max:50',
        'unidad_medida' => 'nullable|string|max:50',
        'precio' => 'nullable|numeric',
    ]);

    \App\Models\Producto::create([
        'nombre' => $request->nombre,
        'categoria_id' => $request->categoria_id,
        'proveedor_id' => $request->proveedor_id, // ✅ Nuevo
        'valor_medida' => $request->valor_medida,
        'unidad_medida' => $request->unidad_medida,
        'precio' => $request->precio,
        'estado' => 'Activo',
    ]);

    return redirect()->route('dashboard.productos')
                     ->with('success', 'Producto agregado correctamente.');
}



}
