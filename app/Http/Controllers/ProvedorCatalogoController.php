<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\ProductoProveedor;
use Illuminate\Http\Request;

class ProveedorCatalogoController extends Controller
{
    private function proveedorActual(): Proveedor
    {
        // ✅ Ajusta esto a tu proyecto:
        // Opción 1 (recomendada): proveedores.user_id
        return Proveedor::where('user_id', auth()->id())
            ->where('estado', 1)
            ->firstOrFail();

        // Si NO tienes user_id, dime cómo lo ligas y lo ajusto.
    }

    public function index(Request $request)
    {
        $proveedor = $this->proveedorActual();
        $q = $request->q;

        // Mis productos (pivote) con datos del producto
        $mis = ProductoProveedor::with('producto')
            ->where('proveedor_id', $proveedor->id)
            ->when($q, function ($query) use ($q) {
                $query->whereHas('producto', fn($p) => $p->where('nombre', 'like', "%{$q}%"));
            })
            ->orderByDesc('id')
            ->get();

        // Catálogo para ofertar nuevos (evita duplicados)
        $catalogo = Producto::query()
            ->when($q, fn($qq) => $qq->where('nombre', 'like', "%{$q}%"))
            ->orderBy('nombre')
            ->get();

        return view('dashboard.proveedor.catalogo', compact('proveedor', 'mis', 'catalogo', 'q'));
    }

    public function ofertar(Request $request)
    {
        $proveedor = $this->proveedorActual();

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'precio' => 'required|numeric|min:0',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_final' => 'required|date|after_or_equal:fecha_vigencia_inicio',
            'estado' => 'required|boolean',
        ]);

        // ✅ Evitar duplicado del mismo producto para este proveedor (si ya existe, actualiza)
        $pp = ProductoProveedor::where('proveedor_id', $proveedor->id)
            ->where('producto_id', $request->producto_id)
            ->first();

        if ($pp) {
            $pp->update([
                'precio' => $request->precio,
                'fecha_vigencia_inicio' => $request->fecha_vigencia_inicio,
                'fecha_vigencia_final' => $request->fecha_vigencia_final,
                'estado' => $request->estado,
            ]);
        } else {
            ProductoProveedor::create([
                'proveedor_id' => $proveedor->id,
                'producto_id' => $request->producto_id,
                'precio' => $request->precio,
                'fecha_vigencia_inicio' => $request->fecha_vigencia_inicio,
                'fecha_vigencia_final' => $request->fecha_vigencia_final,
                'estado' => $request->estado,
            ]);
        }

        return back()->with('success', 'Producto ofertado/actualizado en tu catálogo.');
    }

    public function actualizar(Request $request, ProductoProveedor $pp)
    {
        $proveedor = $this->proveedorActual();

        // 🔒 Seguridad: solo puede editar SU registro_attach
        abort_if($pp->proveedor_id !== $proveedor->id, 403);

        $request->validate([
            'precio' => 'required|numeric|min:0',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_final' => 'required|date|after_or_equal:fecha_vigencia_inicio',
            'estado' => 'required|boolean',
        ]);

        $pp->update([
            'precio' => $request->precio,
            'fecha_vigencia_inicio' => $request->fecha_vigencia_inicio,
            'fecha_vigencia_final' => $request->fecha_vigencia_final,
            'estado' => $request->estado,
        ]);

        return back()->with('success', 'Producto actualizado.');
    }

    public function eliminar(ProductoProveedor $pp)
    {
        $proveedor = $this->proveedorActual();
        abort_if($pp->proveedor_id !== $proveedor->id, 403);

        $pp->delete();

        return back()->with('success', 'Producto removido de tu catálogo.');
    }
}
