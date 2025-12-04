<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedidoEspecial;
use App\Models\Producto;
use App\Models\Proveedor;

class PedidoEspecialController extends Controller
{
    // FORMULARIO
    public function crear()
    {
        $productos = Producto::with([
            'categoria',
            'proveedores' => function($q){
                $q->select('proveedores.id', 'nombre')
                  ->withPivot('id', 'precio'); 
            }
        ])->get();

        return view('dashboard.crear_pedido_especial', compact('productos'));
    }

    // GUARDAR
    public function guardar(Request $request)
    {
        // ⛔ AÚN NO GUARDES PRODUCTOS ESPECIALES — eso lo hacemos después
        // primero solo validación y guardado de PDFs

        $request->validate([
            'solicitud'   => 'required|file|mimes:pdf|max:8000',
            'cotizacion'  => 'required|file|mimes:pdf|max:8000',
            'autorizacion'=> 'required|file|mimes:pdf|max:8000',
        ]);

        $codigo = "SPE" . rand(10000,99999);

        $solicitud = $request->file('solicitud')->store("pedidos_especiales/$codigo", "public");
        $cotizacion = $request->file('cotizacion')->store("pedidos_especiales/$codigo", "public");
        $autorizacion = $request->file('autorizacion')->store("pedidos_especiales/$codigo", "public");

        PedidoEspecial::create([
            'id_pedido_especial' => $codigo,
            'solicitud' => $solicitud,
            'cotizacion' => $cotizacion,
            'autorizacion' => $autorizacion,
            'codigo' => null,
        ]);

        return back()->with('success', 'Pedido especial registrado correctamente.');
    }
}
