<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoProveedor;
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
        $query = ProductoProveedor::with('producto.categoria', 'proveedor');

        // ✅ Proveedor: solo sus relaciones
        if (Auth::user()->role === 'proveedor') {
            $proveedor = $this->proveedorActual();
            $query->where('proveedor_id', $proveedor->id);
        }

        // Búsqueda por nombre de producto
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('producto', fn($sub) => $sub->where('nombre', 'like', "%{$q}%"));
        }

        $relaciones = $query->orderBy('id', 'desc')->paginate(10);

        return view('dashboard.precios.index', compact('relaciones'));
    }

    /**
     * Formulario para editar un precio.
     */
    public function editar($id)
    {
        $relacion = ProductoProveedor::with('producto', 'proveedor')->findOrFail($id);

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
        $relacion = ProductoProveedor::findOrFail($id);

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

        // ✅ Guardar historial (precio anterior)
        HistorialPrecio::create([
            'producto_proveedor_id' => $relacion->id,
            'precio' => $relacion->precio,
            'fecha_vigencia_inicio' => $relacion->fecha_vigencia_inicio,
            'fecha_vigencia_final' => $relacion->fecha_vigencia_final ?? now(),
        ]);

        // ✅ Actualizar precio vigente
        $relacion->update([
            'precio' => $request->precio,
            'fecha_vigencia_inicio' => $request->fecha_vigencia_inicio,
            'fecha_vigencia_final' => $request->fecha_vigencia_final,
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

        $productos = Producto::with(['relaciones' => function ($q) {
            $q->orderBy('fecha_vigencia_inicio', 'desc');
        }])
        ->when($q, fn($query) => $query->where('nombre', 'LIKE', "%{$q}%"))
        ->when($categoriaId, fn($query) => $query->where('categoria_id', $categoriaId))
        ->get();

        $comparativa = $productos->map(function ($producto) {
            $actual = $producto->relaciones->first();

            if (!$actual) {
                return (object)[
                    'producto' => $producto,
                    'actual' => null,
                    'anterior' => null,
                    'variacion' => null,
                ];
            }

            $anterior = HistorialPrecio::where('producto_proveedor_id', $actual->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $variacion = null;
            if ($anterior && $anterior->precio > 0) {
                $variacion = (($actual->precio - $anterior->precio) / $anterior->precio) * 100;
            }

            return (object)[
                'producto' => $producto,
                'actual' => $actual,
                'anterior' => $anterior,
                'variacion' => $variacion,
            ];
        });

        return view('dashboard.precios.comparativa', compact('comparativa', 'categorias', 'q', 'categoriaId'));
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

    /**
     * Ruta proveedor.precios -> reusa la misma vista index, filtrada.
     */
    public function misPrecios(Request $request)
    {
        return $this->index($request);
    }
}
