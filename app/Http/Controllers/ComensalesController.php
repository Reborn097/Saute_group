<?php

namespace App\Http\Controllers;

use App\Models\ComensalRegistro;
use App\Models\UnidadOperativa;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComensalesController extends Controller
{
    public function index(Request $request)
    {
        $unidades = UnidadOperativa::orderBy('nombre')->get();

        $anio = (int) ($request->get('anio') ?? now()->year);
        $mes  = (int) ($request->get('mes')  ?? now()->month);
        $unidadId = $request->get('unidad_id');

        // Rango del mes
        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $fin    = (clone $inicio)->endOfMonth()->endOfDay();

        // Para dibujar tabla tipo calendario: arrancar en lunes
        $gridInicio = (clone $inicio)->startOfWeek(Carbon::MONDAY)->startOfDay();
        $gridFin    = (clone $fin)->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $registros = collect();

        if (!empty($unidadId)) {
            $registros = ComensalRegistro::where('unidad_operativa_id', $unidadId)
                ->whereBetween('fecha', [$gridInicio->toDateString(), $gridFin->toDateString()])
                ->get()
                ->keyBy(fn($r) => $r->fecha->toDateString());
        }

        return view('dashboard.comensales', [
            'unidades'   => $unidades,
            'anio'       => $anio,
            'mes'        => $mes,
            'unidadSel'  => $unidadId,
            'inicio'     => $inicio,
            'gridInicio' => $gridInicio,
            'gridFin'    => $gridFin,
            'registros'  => $registros,
        ]);
    }

    // ✅ Guardado individual (por si lo sigues usando en otras pruebas)
    public function guardar(Request $request)
    {
        $data = $request->validate([
            'unidad_operativa_id' => ['required','integer','exists:unidades_operativas,id'],
            'fecha'               => ['required','date'],
            'cantidad'            => ['required','integer','min:0','max:5000'],
            'anio'                => ['required','integer','min:2000','max:2100'],
            'mes'                 => ['required','integer','min:1','max:12'],
        ]);

        ComensalRegistro::updateOrCreate(
            [
                'unidad_operativa_id' => $data['unidad_operativa_id'],
                'fecha'               => Carbon::parse($data['fecha'])->toDateString(),
            ],
            [
                'cantidad' => (int) $data['cantidad'],
            ]
        );

        return redirect()
            ->route('dashboard.comensales', [
                'unidad_id' => $data['unidad_operativa_id'],
                'anio'      => $data['anio'],
                'mes'       => $data['mes'],
            ])
            ->with('ok', 'Registro guardado.');
    }

    // ✅ Guardar TODO (un solo botón)
    public function guardarTodo(Request $request)
    {
        $data = $request->validate([
            'unidad_operativa_id' => ['required','integer','exists:unidades_operativas,id'],
            'anio'                => ['required','integer','min:2000','max:2100'],
            'mes'                 => ['required','integer','min:1','max:12'],
            'cantidades'          => ['nullable','array'],
            'cantidades.*'        => ['nullable','integer','min:0','max:5000'],
        ]);

        $unidadId   = (int) $data['unidad_operativa_id'];
        $anio       = (int) $data['anio'];
        $mes        = (int) $data['mes'];
        $cantidades = $data['cantidades'] ?? [];

        // Solo aceptamos guardar fechas dentro del mes seleccionado
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $finMes    = (clone $inicioMes)->endOfMonth()->endOfDay();

        foreach ($cantidades as $fecha => $cantidad) {
            // Seguridad: ignora keys raras
            try {
                $f = Carbon::parse($fecha)->startOfDay();
            } catch (\Exception $e) {
                continue;
            }

            // Ignorar fechas fuera del mes seleccionado
            if ($f->lt($inicioMes) || $f->gt($finMes)) {
                continue;
            }

            // Si viene vacío, lo tomamos como 0 (ajusta si prefieres "no tocar" esos días)
            $cantidad = (int) ($cantidad ?? 0);

            ComensalRegistro::updateOrCreate(
                [
                    'unidad_operativa_id' => $unidadId,
                    'fecha'               => $f->toDateString(),
                ],
                [
                    'cantidad'            => $cantidad,
                ]
            );
        }

        return redirect()
            ->route('dashboard.comensales', [
                'unidad_id' => $unidadId,
                'anio'      => $anio,
                'mes'       => $mes,
            ])
            ->with('ok', 'Registros guardados.');
    }
}
