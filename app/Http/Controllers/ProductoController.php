<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\PresentacionProveedor;
use App\Models\HistorialPrecio;

class ProductoController extends Controller
{
    private function normalizarPresentaciones(Request $request): array
    {
        $presentaciones = $request->input('presentaciones');

        if (is_array($presentaciones) && count($presentaciones) > 0) {
            return $presentaciones;
        }

        // Fallback legacy: construir una presentación Default con proveedores viejos
        $proveedores = $request->input('proveedores', []);
        return [[
            'descripcion' => 'Default',
            'contenido' => $request->input('valor_medida'),
            'unidad_contenido' => $request->input('unidad_medida'),
            'unidad_base' => $request->input('unidad_medida'),
            'estado' => 1,
            'proveedores' => $proveedores,
        ]];
    }
    /**
     * 🔹 Listado de productos (PAGINADO + BUSCADOR + FILTROS por categoría y proveedor)
     * GET /dashboard/productos?q=&categoria_id=&proveedor_id=
     */
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));
        $categoriaId = $request->get('categoria_id');
        $proveedorId = $request->get('proveedor_id');

        $query = ProductoPresentacion::query()
            ->with([
                'producto.categoria:id,nombre',
                'proveedores' => function ($q) {
                    $q->with(['proveedor:id,nombre'])
                      ->select('id','presentacion_id','proveedor_id','precio_vigente','estado')
                      ->where('estado', 1)
                      ->orderByDesc('id');
                },
            ])
            ->orderBy('producto_id')
            ->orderBy('descripcion');

        // 🔎 búsqueda por nombre / marca / unidad_medida
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereHas('producto', function ($qp) use ($q) {
                    $qp->where('nombre', 'like', "%{$q}%")
                       ->orWhere('marca', 'like', "%{$q}%");
                })
                ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        // 🧩 filtro por categoría
        if (!empty($categoriaId)) {
            $query->whereHas('producto', function ($qp) use ($categoriaId) {
                $qp->where('categoria_id', $categoriaId);
            });
        }

        // 🏷️ filtro por proveedor (productos que tengan ese proveedor en pivote)
        if (!empty($proveedorId)) {
            $query->whereHas('proveedores', function ($p) use ($proveedorId) {
                $p->where('proveedor_id', $proveedorId);
            });
        }

        // ✅ paginado (ajusta el 10 a tu gusto)
        $presentaciones = $query->paginate(10)->appends($request->query());

        // ✅ catálogos para los filtros en la vista
        $categorias = Categoria::orderBy('nombre', 'asc')->get(['id', 'nombre']);
        $proveedores = Proveedor::orderBy('nombre', 'asc')->get(['id', 'nombre']);

        return view('dashboard.productos', compact('presentaciones', 'categorias', 'proveedores', 'q', 'categoriaId', 'proveedorId'));
    }

    /** 🔹 Vista para crear categoría */
    public function crearCategoria()
    {
        return view('dashboard.agregar_categoria');
    }

    /** 🔹 Vista para crear producto */
    public function crearProducto()
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('dashboard.agregar_producto', compact('categorias', 'proveedores'));
    }

    /** 🔹 Vista para editar producto */
    public function editar($id)
    {
        $producto = Producto::with(['categoria', 'presentaciones', 'presentaciones.proveedores.proveedor'])->findOrFail($id);
        $categorias = Categoria::orderBy('nombre')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('dashboard.editar_producto', compact('producto', 'categorias', 'proveedores'));
    }

    /** 🔹 Actualizar producto y sus proveedores (SIN DETACH + con historial) */
    public function actualizar(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca'  => 'nullable|string|max:255',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'valor_medida' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'estado' => 'required|boolean',

            'presentaciones' => 'nullable|array|min:1',
            'presentaciones.*.id' => 'nullable|integer',
            'presentaciones.*.descripcion' => 'required|string|max:150',
            'presentaciones.*.contenido' => 'nullable|numeric|min:0',
            'presentaciones.*.unidad_contenido' => 'nullable|string|max:20',
            'presentaciones.*.unidad_base' => 'nullable|string|max:20',
            'presentaciones.*.estado' => 'nullable|boolean',
            'presentaciones.*.proveedores' => 'required|array|min:1',
            'presentaciones.*.proveedores.*.id' => 'required|integer|exists:proveedores,id',
            'presentaciones.*.proveedores.*.precio' => 'required|numeric|min:0',
            'presentaciones.*.proveedores.*.fecha_vigencia_inicio' => 'nullable|date',
            'presentaciones.*.proveedores.*.fecha_vigencia_final' => 'nullable|date|after_or_equal:presentaciones.*.proveedores.*.fecha_vigencia_inicio',
        ]);

        // ✅ 1) Actualizar producto
        $producto->update([
            'nombre' => $request->nombre,
            'marca'  => $request->marca,
            'categoria_id' => $request->categoria_id,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'estado' => $request->estado,
        ]);

        $presentacionesInput = $this->normalizarPresentaciones($request);

        $presentacionIds = collect($presentacionesInput)
            ->pluck('id')
            ->filter()
            ->map(fn($v) => (int)$v)
            ->values()
            ->all();

        if (!empty($presentacionIds)) {
            ProductoPresentacion::where('producto_id', $producto->id)
                ->whereNotIn('id', $presentacionIds)
                ->update(['estado' => 0]);
        }

        foreach ($presentacionesInput as $pres) {
            $presId = isset($pres['id']) ? (int)$pres['id'] : null;

            $presentacion = null;
            if ($presId) {
                $presentacion = ProductoPresentacion::where('producto_id', $producto->id)
                    ->where('id', $presId)
                    ->first();
            }

            if (!$presentacion) {
                $presentacion = new ProductoPresentacion();
                $presentacion->producto_id = $producto->id;
            }

            $presentacion->descripcion = $pres['descripcion'] ?? 'Default';
            $presentacion->contenido = $pres['contenido'] ?? null;
            $presentacion->unidad_contenido = $pres['unidad_contenido'] ?? null;
            $presentacion->unidad_base = $pres['unidad_base'] ?? ($pres['unidad_contenido'] ?? null);
            $presentacion->estado = isset($pres['estado']) ? (int)$pres['estado'] : 1;
            $presentacion->save();

            $proveedores = $pres['proveedores'] ?? [];
            $proveedoresIds = collect($proveedores)->pluck('id')->toArray();

            PresentacionProveedor::where('presentacion_id', $presentacion->id)
                ->whereNotIn('proveedor_id', $proveedoresIds)
                ->update(['estado' => 0]);

            foreach ($proveedores as $prov) {
                $ppActual = PresentacionProveedor::where('presentacion_id', $presentacion->id)
                    ->where('proveedor_id', $prov['id'])
                    ->first();

                $pp = PresentacionProveedor::updateOrCreate(
                    [
                        'presentacion_id' => $presentacion->id,
                        'proveedor_id' => $prov['id'],
                    ],
                    [
                        'precio_vigente' => $prov['precio'],
                        'estado' => 1,
                    ]
                );

                $cambio = !$ppActual
                    || (float) $ppActual->precio_vigente !== (float) $prov['precio']
                    || (int) $ppActual->estado !== 1;

                if ($cambio) {
                    HistorialPrecio::create([
                        'presentacion_proveedor_id' => $pp->id,
                        'precio' => $prov['precio'],
                        'vigencia_inicio' => $prov['fecha_vigencia_inicio'] ?? null,
                        'vigencia_fin' => $prov['fecha_vigencia_final'] ?? null,
                    ]);
                }
            }
        }

        return redirect()
            ->route('dashboard.productos')
            ->with('success', '✅ Producto actualizado sin romper historial.');
    }

    /** 🔹 Guardar nueva categoría */
    public function guardarCategoria(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|integer',
        ]);

        Categoria::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado,
        ]);

        return redirect()->route('dashboard.productos')
            ->with('success', 'Categoría agregada correctamente.');
    }

    /** 🔹 Guardar nuevo producto con múltiples proveedores (+ historial) */
    public function guardar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca'  => 'nullable|string|max:255',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'valor_medida' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'presentaciones' => 'nullable|array|min:1',
            'presentaciones.*.descripcion' => 'required|string|max:150',
            'presentaciones.*.contenido' => 'nullable|numeric|min:0',
            'presentaciones.*.unidad_contenido' => 'nullable|string|max:20',
            'presentaciones.*.unidad_base' => 'nullable|string|max:20',
            'presentaciones.*.estado' => 'nullable|boolean',
            'presentaciones.*.proveedores' => 'required|array|min:1',
            'presentaciones.*.proveedores.*.id' => 'required|integer|exists:proveedores,id',
            'presentaciones.*.proveedores.*.precio' => 'required|numeric|min:0',
            'presentaciones.*.proveedores.*.fecha_vigencia_inicio' => 'nullable|date',
            'presentaciones.*.proveedores.*.fecha_vigencia_final' => 'nullable|date|after_or_equal:presentaciones.*.proveedores.*.fecha_vigencia_inicio',
        ]);

        $producto = Producto::create([
            'nombre' => $request->nombre,
            'marca'  => $request->marca,
            'valor_medida' => $request->valor_medida,
            'unidad_medida' => $request->unidad_medida,
            'categoria_id' => $request->categoria_id,
            'estado' => 1,
        ]);

        $presentacionesInput = $this->normalizarPresentaciones($request);

        foreach ($presentacionesInput as $pres) {
            $presentacion = ProductoPresentacion::create([
                'producto_id' => $producto->id,
                'descripcion' => $pres['descripcion'] ?? 'Default',
                'contenido' => $pres['contenido'] ?? null,
                'unidad_contenido' => $pres['unidad_contenido'] ?? null,
                'unidad_base' => $pres['unidad_base'] ?? ($pres['unidad_contenido'] ?? null),
                'estado' => isset($pres['estado']) ? (int)$pres['estado'] : 1,
            ]);

            foreach (($pres['proveedores'] ?? []) as $prov) {
                $pp = PresentacionProveedor::create([
                    'presentacion_id' => $presentacion->id,
                    'proveedor_id' => $prov['id'],
                    'precio_vigente' => $prov['precio'],
                    'estado' => 1,
                ]);

                HistorialPrecio::create([
                    'presentacion_proveedor_id' => $pp->id,
                    'precio' => $prov['precio'],
                    'vigencia_inicio' => $prov['fecha_vigencia_inicio'] ?? null,
                    'vigencia_fin' => $prov['fecha_vigencia_final'] ?? null,
                ]);
            }
        }

        return redirect()
            ->route('dashboard.productos')
            ->with('success', '✅ Producto guardado correctamente con proveedores e historial.');
    }

}
