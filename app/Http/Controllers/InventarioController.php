<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\InventarioCaducidad;
use App\Models\Kardex;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $almacenId = $request->get('almacen_id');

        $almacenes = Almacen::orderBy('nombre')->get();

        $query = Inventario::with(['producto.categoria', 'almacen']);

        if ($almacenId) {
            $query->where('almacen_id', $almacenId);
        }

        $inventarios = $query->orderBy('almacen_id')
            ->orderBy('producto_id')
            ->get();

        return view('dashboard.inventarios.index', compact('inventarios', 'almacenes', 'almacenId'));
    }

    public function movimientoForm()
    {
        $almacenes = Almacen::orderBy('nombre')->get();
        $productos = Producto::orderBy('nombre')->get();

        return view('dashboard.inventarios.movimiento', compact('almacenes', 'productos'));
    }

    public function movimientoStore(Request $request)
    {
        $request->validate([
            'almacen_id'       => 'required|exists:almacenes,id',
            'producto_id'      => 'required|exists:productos,id',
            'tipo_movimiento'  => 'required|in:entrada,salida,ajuste',
            'cantidad'         => 'required|numeric|min:0.01',
            'motivo'           => 'nullable|string|max:255',
            'lote'             => 'nullable|string|max:120',
            'caducidad'        => 'nullable|date',
        ]);

        $almacenId  = (int) $request->almacen_id;
        $productoId = (int) $request->producto_id;
        $tipo       = (string) $request->tipo_movimiento;
        $cantidad   = (float) $request->cantidad;

        // Normalizar lote/caducidad
        $lote = $request->filled('lote') ? trim((string)$request->lote) : null;
        $cad  = $request->filled('caducidad') ? $request->caducidad : null;

        // Solo tocar inventario_caducidades si hay lote o caducidad y NO es ajuste
        $usarCaducidades = (($lote !== null) || ($cad !== null)) && $tipo !== 'ajuste';

        DB::transaction(function () use (
            $request, $almacenId, $productoId, $tipo, $cantidad, $lote, $cad, $usarCaducidades
        ) {
            /**
             * 1) Inventario AGREGADO (producto + almacen)
             */
            $inventario = Inventario::firstOrCreate(
                ['almacen_id' => $almacenId, 'producto_id' => $productoId],
                ['cantidad' => 0, 'area_almacen' => null, 'caducidad' => null]
            );

            $cantidadActual = (float) $inventario->cantidad;
            $nuevaCantidad  = $cantidadActual;

            if ($tipo === 'entrada') {
                $nuevaCantidad = $cantidadActual + $cantidad;
            } elseif ($tipo === 'salida') {
                $nuevaCantidad = $cantidadActual - $cantidad;

                if ($nuevaCantidad < 0) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'No hay suficiente inventario para hacer la salida (inventario general).',
                    ]);
                }
            } elseif ($tipo === 'ajuste') {
                // Ajuste = setear a la cantidad final (no tocar lotes/caducidades)
                $nuevaCantidad = $cantidad;
            }

            $inventario->cantidad = $nuevaCantidad;
            $inventario->save();

            /**
             * 2) Inventario por CADUCIDAD/LOTE (inventario_caducidades)
             *    NOTA: tu tabla NO tiene inventario_id -> NO lo uses.
             */
            if ($usarCaducidades) {
                $row = InventarioCaducidad::firstOrCreate(
                    [
                        'producto_id' => $productoId,
                        'almacen_id'  => $almacenId,
                        'lote'        => $lote,
                        'caducidad'   => $cad,
                    ],
                    ['cantidad' => 0]
                );

                $rowCantidadActual = (float) $row->cantidad;

                if ($tipo === 'entrada') {
                    $row->cantidad = $rowCantidadActual + $cantidad;
                } else { // salida
                    $row->cantidad = $rowCantidadActual - $cantidad;

                    if ($row->cantidad < 0) {
                        throw ValidationException::withMessages([
                            'cantidad' => 'No hay suficiente cantidad en ese lote/caducidad.',
                        ]);
                    }
                }

                $row->save();
            }

            /**
             * 3) Kardex SIEMPRE
             */
            Kardex::create([
                'inventario_id'    => $inventario->id,
                'producto_id'      => $productoId,
                'user_id'          => Auth::id(),
                'cantidad'         => $cantidad,
                'tipo_movimiento'  => $tipo,
                'motivo'           => $request->motivo,
                'fecha_movimiento' => now(),
            ]);
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }

    public function kardex(Request $request)
    {
        $almacenId  = $request->get('almacen_id');
        $productoId = $request->get('producto_id');

        $almacenes = Almacen::orderBy('nombre')->get();
        $productos = Producto::orderBy('nombre')->get();

        $query = Kardex::with(['producto', 'usuario', 'inventario.almacen'])
            ->orderBy('fecha_movimiento', 'desc');

        if ($almacenId) {
            $query->whereHas('inventario', fn ($q) => $q->where('almacen_id', $almacenId));
        }

        if ($productoId) {
            $query->where('producto_id', $productoId);
        }

        $movimientos = $query->paginate(50);

        return view('dashboard.inventarios.kardex', compact(
            'movimientos',
            'almacenes',
            'productos',
            'almacenId',
            'productoId'
        ));
    }

    public function caducidades(Request $request)
    {
        $almacenId = $request->get('almacen_id');

        $almacenes = Almacen::orderBy('nombre')->get();

        $query = InventarioCaducidad::with(['producto', 'almacen'])
            ->orderByRaw("caducidad IS NULL") // nulls al final
            ->orderBy('caducidad', 'asc');

        if ($almacenId) {
            $query->where('almacen_id', $almacenId);
        }

        $caducidades = $query->paginate(50);

        return view('dashboard.inventarios.caducidades', compact('caducidades', 'almacenes', 'almacenId'));
    }
}
