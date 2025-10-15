<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        // Aquí cargará la vista de administración de productos
        return view('dashboard.productos');
    }
}
