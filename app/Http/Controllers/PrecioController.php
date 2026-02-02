<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\PresentacionProveedor;
use App\Models\HistorialPrecio;
use App\Imports\PreciosImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Proveedor;

class PrecioController extends Controller
{
    /**
     * Helper: obtener el proveedor ligado al usuario (users.proveedor_id)
     */
    private function proveedorActual(): ?Proveedor
    {
        if (!Auth::check()) return null;
        if (Auth::user()->role !== 'proveedor') return null;

        $proveedorId = Auth::user()->proveedor_id;

        if (!$proveedorId) {
            abort(403, 'Tu usuario no tiene proveedor asignado.');
        }

        return Proveedor::findOrFail($proveedorId);
    }

    /**
     * Mostrar lista de precios.
     * Admin ve todo; proveedor solo ve su catálogo.
     */
    public function index(Request $request)
    {
        $query = PresentacionProveedor::with('presentacion.producto.categoria', 'proveedor', 'historialUltimo');

        // ✅ Proveedor: solo sus relaciones
        if (Auth::user()->role === 'proveedor') {
            $proveedor = $this->proveedorActual();
            $query->where('proveedor_id', $proveedor->id);
        }

        // Búsqueda por nombre de producto
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('presentacion.producto', fn($sub) => $sub->where('nombre', 'like', "%{$q}%"));
        }

        $relaciones = $query
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->appends($request->query());


        return view('dashboard.precios.index', compact('relaciones'));
    }

    /**
     * Formulario para editar un precio.
     */
    public function editar($id)
    {
        $relacion = PresentacionProveedor::with('presentacion.producto', 'proveedor', 'historialUltimo')
            ->findOrFail($id);

        // 🔒 Proveedor solo puede editar los suyos
        if (Auth::user()->role === 'proveedor') {
            $proveedor = $this->proveedorActual();
            abort_if($proveedor->id != $relacion->proveedor_id, 403, 'No tienes permiso para modificar este producto.');
        }

        return view('dashboard.precios.editar', compact('relacion'));
    }

    /**
     * Actualizar precio y registrar historial.
     */
    public function actualizar(Request $request, $id)
    {
        $relacion = PresentacionProveedor::with('historialUltimo')->findOrFail($id);

        // 🔒 Proveedor solo puede actualizar los suyos
        if (Auth::user()->role === 'proveedor') {
            $proveedor = $this->proveedorActual();
            abort_if($proveedor->id != $relacion->proveedor_id, 403, 'No tienes permiso para modificar este producto.');
        }

        // ✅ Validación
        $request->validate([
            'precio' => 'required|numeric|min:0',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_final' => 'nullable|date|after_or_equal:fecha_vigencia_inicio',
        ]);

        $ultimo = $relacion->historialUltimo;
        $cambio = (float) $relacion->precio_vigente !== (float) $request->precio
            || ($ultimo && (string) $ultimo->vigencia_inicio !== (string) $request->fecha_vigencia_inicio)
            || ($ultimo && (string) $ultimo->vigencia_fin !== (string) $request->fecha_vigencia_final);

        if ($cambio) {
            HistorialPrecio::create([
                'presentacion_proveedor_id' => $relacion->id,
                'precio' => $request->precio,
                'vigencia_inicio' => $request->fecha_vigencia_inicio,
                'vigencia_fin' => $request->fecha_vigencia_final,
            ]);
        }

        $relacion->update([
            'precio_vigente' => $request->precio,
        ]);

        // ✅ Redirect por rol
        return redirect()->route(
            Auth::user()->role === 'proveedor' ? 'proveedor.precios' : 'dashboard.precios'
        )->with('success', 'Precio actualizado correctamente.');
    }

    public function comparativaPrecios(Request $request)
    {
    $q = $request->q;
    $categoriaId = $request->categoria;

    $categorias = Categoria::orderBy('nombre')->get();

    $productos = Producto::with([
            'presentaciones.proveedores' => function ($q) {
                $q->orderBy('updated_at', 'desc');
            },
        ])
        ->when($q, fn($query) =>
            $query->where('nombre', 'LIKE', "%{$q}%")
        )
        ->when($categoriaId, fn($query) =>
            $query->where('categoria_id', $categoriaId)
        )
        ->orderBy('nombre')
        ->paginate(10)
        ->appends($request->query());

    $comparativa = $productos->map(function ($producto) {
        $actual = $producto->presentaciones
            ->flatMap(function ($presentacion) {
                return $presentacion->proveedores->map(function ($pp) use ($presentacion) {
                    $pp->setRelation('presentacion', $presentacion);
                    return $pp;
                });
            })
            ->sortByDesc(function ($pp) {
                return $pp->updated_at ?? $pp->created_at;
            })
            ->first();

        if (!$actual) {
            return (object)[
                'producto'     => $producto,
                'presentacion' => null,
                'actual'       => null,
                'anterior'     => null,
                'variacion'    => null,
            ];
        }

        $historial = HistorialPrecio::where('presentacion_proveedor_id', $actual->id)
            ->orderBy('vigencia_inicio', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        $anterior = $historial->skip(1)->first();

        $variacion = null;
        if ($anterior && $anterior->precio > 0 && $actual->precio_vigente !== null) {
            $variacion = (($actual->precio_vigente - $anterior->precio) / $anterior->precio) * 100;
        }

        return (object)[
            'producto'     => $producto,
            'presentacion' => $actual->presentacion,
            'actual'       => $actual,
            'anterior'     => $anterior,
            'variacion'    => $variacion,
        ];
    });

    return view('dashboard.precios.comparativa', [
        'comparativa' => $comparativa,
        'categorias'  => $categorias,
        'q'           => $q,
        'categoriaId' => $categoriaId,
        'productos'   => $productos, // 🔥 PARA PAGINACIÓN
    ]);
}


    public function formImportarExcel()
    {
        $proveedores = Proveedor::all();
        return view('dashboard.precios.subir_excel', compact('proveedores'));
    }

    public function importarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls',
            'proveedor_id' => 'required|exists:proveedores,id'
        ]);

        $import = new PreciosImport($request->proveedor_id);

        Excel::import($import, $request->file('archivo'));

        return back()->with([
            'success'      => 'Archivo procesado correctamente.',
            'actualizados' => $import->actualizados,
            'errores'      => $import->errores,
        ]);
    }

    public function misPrecios(Request $request)
    {
        return $this->index($request);
    }

    public function formImportarExcelProveedor()
    {
        return view('dashboard.precios.subir_excel');
    }

    public function importarExcelProveedor(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls',
        ]);

        $proveedorId = auth()->user()->proveedor_id;

        if (!$proveedorId) {
            abort(403, 'Tu usuario no tiene proveedor asignado.');
        }

        $import = new PreciosImport($proveedorId);

        Excel::import($import, $request->file('archivo'));

        return back()->with([
            'success'      => 'Archivo procesado correctamente.',
            'actualizados' => $import->actualizados,
            'errores'      => $import->errores,
        ]);
    }


}
