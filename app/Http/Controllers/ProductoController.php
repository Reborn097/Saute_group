<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;  
use App\Models\Producto;    

class ProductoController extends Controller
{
    public function index()
{
    // Aquí podrías obtener los productos desde la base de datos
    // por ahora solo mostramos la vista vacía.
    return view('dashboard.productos');
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
        'valor_medida' => 'nullable|string|max:50',
        'unidad_medida' => 'nullable|string|max:50',
        'precio' => 'nullable|numeric',
    ]);

    \App\Models\Producto::create([
        'nombre' => $request->nombre,
        'categoria_id' => $request->categoria_id,
        'valor_medida' => $request->valor_medida,
        'unidad_medida' => $request->unidad_medida,
        'precio' => $request->precio,
    ]);

    return redirect()->route('dashboard.productos')->with('success', 'Producto agregado correctamente.');
}


}
