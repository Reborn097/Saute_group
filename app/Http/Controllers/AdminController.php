<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // Aquí decides qué vista se muestra al entrar a /dashboard/admin
        return view('dashboard.admin');
    }
}
