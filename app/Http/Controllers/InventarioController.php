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
            'almacen_id' => 'required|exists:almacenes,id',
            'producto_id' => 'required|exists:productos,id',
            'tipo_movimiento' => 'required|in:entrada,salida,ajuste',
            'cantidad' => 'required|numeric|min:0.01',
            'motivo' => 'nullable|string|max:255',
            'lote' => 'nullable|string|max:120',
            'caducidad' => 'nullable|date',
        ]);

        $almacenId = (int)$request->almacen_id;
        $productoId = (int)$request->producto_id;
        $tipo = $request->tipo_movimiento;
        $cantidad = (float)$request->cantidad;

        DB::transaction(function () use ($request, $almacenId, $productoId, $tipo, $cantidad) {

            // 1) Obtener/crear inventario agregado (producto+almacen)
            $inventario = Inventario::firstOrCreate(
                ['almacen_id' => $almacenId, 'producto_id' => $productoId],
                ['cantidad' => 0, 'area_almacen' => null]
            );

            // 2) Calcular nueva cantidad total según tipo
            $nuevaCantidad = $inventario->cantidad;

            if ($tipo === 'entrada') {
                $nuevaCantidad += $cantidad;
            } elseif ($tipo === 'salida') {
                $nuevaCantidad -= $cantidad;
                if ($nuevaCantidad < 0) {
                    abort(422, 'No hay suficiente inventario para hacer la salida.');
                }
            } elseif ($tipo === 'ajuste') {
                // ajuste = setear a una cantidad final (cantidad = nueva)
                $nuevaCantidad = $cantidad;
            }

            $inventario->cantidad = $nuevaCantidad;
            $inventario->save();

            // 3) Si viene lote o caducidad => manejar inventario_caducidades
            $lote = $request->filled('lote') ? trim($request->lote) : null;
            $cad = $request->filled('caducidad') ? $request->caducidad : null;

            $usarCaducidad = ($lote !== null) || ($cad !== null);

            if ($usarCaducidad) {

                // En ajustes, por simplicidad, lo dejamos solo en inventario agregado.
                // (Si quieres ajuste también por lote, se puede extender)
                if ($tipo === 'ajuste') {
                    // no tocar lotes en ajuste, para evitar inconsistencias
                } else {
                    $row = InventarioCaducidad::firstOrCreate(
                        [
                            'inventario_id' => $inventario->id,
                            'producto_id' => $productoId,
                            'almacen_id' => $almacenId,
                            'lote' => $lote,
                            'caducidad' => $cad,
                        ],
                        ['cantidad' => 0]
                    );

                    if ($tipo === 'entrada') {
                        $row->cantidad += $cantidad;
                    } else { // salida
                        $row->cantidad -= $cantidad;
                        if ($row->cantidad < 0) {
                            abort(422, 'No hay suficiente cantidad en ese lote/caducidad.');
                        }
                    }

                    $row->save();
                }
            }

            // 4) Registrar Kardex SIEMPRE
            Kardex::create([
                'inventario_id' => $inventario->id,
                'producto_id' => $productoId,
                'user_id' => Auth::id(),
                'cantidad' => $cantidad,
                'tipo_movimiento' => $tipo,
                'motivo' => $request->motivo,
                'fecha_movimiento' => now(),
            ]);
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }

    public function kardex(Request $request)
    {
        $almacenId = $request->get('almacen_id');
        $productoId = $request->get('producto_id');

        $almacenes = Almacen::orderBy('nombre')->get();
        $productos = Producto::orderBy('nombre')->get();

        $query = Kardex::with(['producto', 'usuario', 'inventario.almacen'])
            ->orderBy('fecha_movimiento', 'desc');

        if ($almacenId) {
            $query->whereHas('inventario', fn($q) => $q->where('almacen_id', $almacenId));
        }

        if ($productoId) {
            $query->where('producto_id', $productoId);
        }

        $movimientos = $query->paginate(50);

        return view('dashboard.inventarios.kardex', compact(
            'movimientos', 'almacenes', 'productos', 'almacenId', 'productoId'
        ));
    }

    public function caducidades(Request $request)
    {
        $almacenId = $request->get('almacen_id');

        $almacenes = Almacen::orderBy('nombre')->get();

        $query = InventarioCaducidad::with(['producto', 'almacen'])
            ->orderBy('caducidad', 'asc');

        if ($almacenId) {
            $query->where('almacen_id', $almacenId);
        }

        $caducidades = $query->paginate(50);

        return view('dashboard.inventarios.caducidades', compact('caducidades', 'almacenes', 'almacenId'));
    }
}
