<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\PresentacionProveedor;
use App\Models\HistorialPrecio;
use App\Imports\PreciosImport;
use App\Exports\PlantillaPreciosProveedorExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Proveedor;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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

        $proveedor = Proveedor::findOrFail($proveedorId);

        if ((int)$proveedor->estado !== 1) {
            abort(403, 'Tu proveedor está inactivo.');
        }

        return $proveedor;
    }

    /**
     * Mostrar lista de precios.
     * Admin ve todo; proveedor solo ve su catálogo.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $categoriaId = $request->get('categoria_id');
        $proveedorId = $request->get('proveedor_id');
        $esProveedor = Auth::user()->role === 'proveedor';

        $query = PresentacionProveedor::with('presentacion.producto.categoria', 'proveedor', 'historialUltimo')
            ->where('estado', 1)
            ->whereHas('proveedor', fn($sub) => $sub->where('estado', 1));

        // Proveedor: solo sus relaciones
        if ($esProveedor) {
            $proveedor = $this->proveedorActual();
            $query->where('proveedor_id', $proveedor->id);
            $proveedorId = (string) $proveedor->id;
        }

        // Busqueda por nombre de producto
        if ($q !== '') {
            $query->whereHas('presentacion.producto', fn($sub) => $sub->where('nombre', 'like', "%{$q}%"));
        }

        // Filtro por categoria
        if (!empty($categoriaId)) {
            $query->whereHas('presentacion.producto', fn($sub) => $sub->where('categoria_id', (int) $categoriaId));
        }

        // Filtro por proveedor (solo admin/roles no proveedor)
        if (!$esProveedor && !empty($proveedorId)) {
            $query->where('proveedor_id', (int) $proveedorId);
        }

        $relaciones = $query
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->appends($request->query());

        $categorias = Categoria::orderBy('nombre')->get(['id', 'nombre']);
        $proveedores = $esProveedor
            ? collect()
            : Proveedor::activos()->orderBy('nombre')->get(['id', 'nombre']);

        return view('dashboard.precios.index', compact(
            'relaciones',
            'categorias',
            'proveedores',
            'q',
            'categoriaId',
            'proveedorId'
        ));
    }

    /**
     * Formulario para editar un precio.
     */
    public function editar($id)
    {
        $relacion = PresentacionProveedor::with('presentacion.producto', 'proveedor', 'historialUltimo')
            ->findOrFail($id);

        // Proveedor solo puede editar los suyos
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

        // Proveedor solo puede actualizar los suyos
        if (Auth::user()->role === 'proveedor') {
            $proveedor = $this->proveedorActual();
            abort_if($proveedor->id != $relacion->proveedor_id, 403, 'No tienes permiso para modificar este producto.');
        }

        // Validación
        $request->validate([
            'precio' => 'required|numeric|min:0',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_final' => 'nullable|date|after_or_equal:fecha_vigencia_inicio',
        ]);

        $ultimo = $relacion->historialUltimo;
        $precioActual = (float) $relacion->precio_vigente;
        $precioNuevo = (float) $request->precio;

        $cambio = $precioActual !== $precioNuevo
            || ($ultimo && (string) $ultimo->vigencia_inicio !== (string) $request->fecha_vigencia_inicio)
            || ($ultimo && (string) $ultimo->vigencia_fin !== (string) $request->fecha_vigencia_final);

        if ($cambio) {
            // Si no hay historial previo y cambió el precio, guardamos línea base.
            if (!$ultimo && $precioActual !== $precioNuevo) {
                HistorialPrecio::create([
                    'presentacion_proveedor_id' => $relacion->id,
                    'precio' => $precioActual,
                    'vigencia_inicio' => $request->fecha_vigencia_inicio,
                    'vigencia_fin' => $request->fecha_vigencia_final,
                ]);
            }

            HistorialPrecio::create([
                'presentacion_proveedor_id' => $relacion->id,
                'precio' => $precioNuevo,
                'vigencia_inicio' => $request->fecha_vigencia_inicio,
                'vigencia_fin' => $request->fecha_vigencia_final,
            ]);
        }

        $relacion->update([
            'precio_vigente' => $request->precio,
        ]);

        // Redirect por rol
        return redirect()->route(
            Auth::user()->role === 'proveedor' ? 'proveedor.precios' : 'dashboard.precios'
        )->with('success', 'Precio actualizado correctamente.');
    }

    public function comparativaPrecios(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $categoriaId = $request->get('categoria');
        $proveedorId = $request->get('proveedor_id');
        $esProveedor = Auth::user()->role === 'proveedor';

        if ($esProveedor) {
            $proveedor = $this->proveedorActual();
            $proveedorId = (string) $proveedor->id;
        }

        $categorias = Categoria::orderBy('nombre')->get(['id', 'nombre']);
        $proveedores = $esProveedor
            ? collect()
            : Proveedor::activos()->orderBy('nombre')->get(['id', 'nombre']);

        $productos = Producto::with([
                'presentaciones.proveedores' => function ($sub) use ($proveedorId) {
                    $sub->where('estado', 1)
                        ->whereHas('proveedor', fn($p) => $p->where('estado', 1))
                        ->when($proveedorId, fn($q) => $q->where('proveedor_id', (int) $proveedorId))
                        ->orderBy('updated_at', 'desc');
                },
            ])
            ->when($q !== '', fn($query) =>
                $query->where('nombre', 'LIKE', "%{$q}%")
            )
            ->when($categoriaId, fn($query) =>
                $query->where('categoria_id', (int) $categoriaId)
            )
            ->when($proveedorId, function ($query) use ($proveedorId) {
                $query->whereHas('presentaciones.proveedores', function ($sub) use ($proveedorId) {
                    $sub->where('estado', 1)
                        ->where('proveedor_id', (int) $proveedorId)
                        ->whereHas('proveedor', fn($p) => $p->where('estado', 1));
                });
            })
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

            $ultimoHist = $historial->first();
            $anterior = $historial->skip(1)->first();

            // Compatibilidad: si solo hay 1 historial y difiere del precio actual, úsalo como anterior.
            if (!$anterior && $ultimoHist && (float) $ultimoHist->precio !== (float) $actual->precio_vigente) {
                $anterior = $ultimoHist;
            }

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
            'proveedores' => $proveedores,
            'q'           => $q,
            'categoriaId' => $categoriaId,
            'proveedorId' => $proveedorId,
            'productos'   => $productos,
        ]);
    }


    public function formImportarExcel()
    {
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();
        return view('dashboard.precios.subir_excel', compact('proveedores'));
    }

    public function importarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls',
            'proveedor_id' => [
                'required',
                Rule::exists('proveedores', 'id')->where('estado', 1),
            ],
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

    public function descargarPlantillaProveedor(Request $request)
    {
        if (Auth::user()->role === 'proveedor') {
            abort(403, 'Usa la opción de descarga de tu módulo de proveedor.');
        }

        $request->validate([
            'proveedor_id' => [
                'required',
                Rule::exists('proveedores', 'id')->where('estado', 1),
            ],
        ]);

        $proveedor = Proveedor::findOrFail((int) $request->proveedor_id);

        return $this->descargarPlantilla($proveedor);
    }

    public function descargarPlantillaProveedorAutenticado()
    {
        $proveedor = $this->proveedorActual();

        if (!$proveedor) {
            abort(403, 'Solo proveedores pueden descargar esta plantilla.');
        }

        return $this->descargarPlantilla($proveedor);
    }

    private function descargarPlantilla(Proveedor $proveedor)
    {
        $archivo = 'precios_' . Str::slug($proveedor->nombre) . '.xlsx';

        return Excel::download(
            new PlantillaPreciosProveedorExport((int) $proveedor->id),
            $archivo
        );
    }


}


