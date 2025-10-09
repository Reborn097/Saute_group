<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ComprasController extends Controller
{
    public function index()
    {
        return view('dashboard.compras');
    }
}
