<?php

namespace App\Http\Controllers;

use App\Models\CorteCaja;
use App\Models\UnidadOperativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CorteCajaController extends Controller
{
    public function index(Request $request)
    {
        $unidades = UnidadOperativa::orderBy('nombre')->get();

        $anio = (int) ($request->get('anio') ?? now()->year);
        $mes  = (int) ($request->get('mes')  ?? now()->month);

        // soporta unidad_id o local (por si tu form usa "local")
        $unidadSel = $request->get('unidad_id') ?? $request->get('local');

        // si no viene unidad, usa la primera
        if (!$unidadSel && $unidades->count()) {
            $unidadSel = $unidades->first()->id;
        }

        // rango del mes
        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $fin    = (clone $inicio)->endOfMonth()->endOfDay();

        // grid tipo calendario (lunes-domingo)
        $gridInicio = (clone $inicio)->startOfWeek(Carbon::MONDAY)->startOfDay();
        $gridFin    = (clone $fin)->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $registros = collect();

        if (!empty($unidadSel)) {
            $registros = CorteCaja::where('unidad_id', $unidadSel)
                ->whereBetween('fecha', [$gridInicio->toDateString(), $gridFin->toDateString()])
                ->get()
                ->keyBy(fn($r) => Carbon::parse($r->fecha)->toDateString());
        }

        return view('dashboard.corte_caja', [
            'unidades'   => $unidades,
            'anio'       => $anio,
            'mes'        => $mes,

            // ✅ compatibilidad con blades viejos
            'unidadSel'  => $unidadSel,
            'localSel'   => $unidadSel,

            'inicio'     => $inicio,
            'gridInicio' => $gridInicio,
            'gridFin'    => $gridFin,
            'registros'  => $registros,
        ]);
    }

    // ✅ compatibilidad si existe ruta vieja /guardar
    public function guardar(Request $request)
    {
        return $this->guardarTodo($request);
    }

    public function guardarTodo(Request $request)
    {
        $data = $request->validate([
            'unidad_id' => ['required','integer','exists:unidades_operativas,id'],
            'anio'      => ['required','integer','min:2000','max:2100'],
            'mes'       => ['required','integer','min:1','max:12'],
            'efectivo'  => ['array'],
            'tarjeta'   => ['array'],
        ]);

        $unidadId = (int) $data['unidad_id'];
        $anio     = (int) $data['anio'];
        $mes      = (int) $data['mes'];

        $efectivo = $request->input('efectivo', []);
        $tarjeta  = $request->input('tarjeta', []);

        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes    = (clone $inicioMes)->endOfMonth();

        DB::transaction(function () use ($unidadId, $efectivo, $tarjeta, $inicioMes, $finMes) {

            // recorremos todas las fechas que aparezcan en cualquiera de los dos arreglos
            $fechas = array_unique(array_merge(array_keys($efectivo), array_keys($tarjeta)));

            foreach ($fechas as $fecha) {

                $f = Carbon::parse($fecha);

                // solo fechas del mes seleccionado
                if ($f->lt($inicioMes) || $f->gt($finMes)) {
                    continue;
                }

                $rawEf = $efectivo[$fecha] ?? 0;
                $rawTa = $tarjeta[$fecha] ?? 0;

                $valEf = is_numeric($rawEf) ? (float) $rawEf : 0;
                $valTa = is_numeric($rawTa) ? (float) $rawTa : 0;

                // límites sugeridos
                $valEf = max(0, min(999999, $valEf));
                $valTa = max(0, min(999999, $valTa));

                CorteCaja::updateOrCreate(
                    [
                        'unidad_id' => $unidadId,
                        'fecha'     => $f->toDateString(),
                    ],
                    [
                        'cantidad_efectivo' => $valEf,
                        'cantidad_credito'  => $valTa,
                    ]
                );
            }
        });

        return redirect()->route('dashboard.corte-caja', [
            'anio'      => $anio,
            'mes'       => $mes,
            'unidad_id' => $unidadId,
        ])->with('ok', 'Corte de caja guardado.');
    }
    

    public function forzarSemanaPasada()
    {
        $user = Auth::user();
        abort_unless(($user->role ?? '') === 'encargado_cafeteria', 403);

        $unidadId = $user->unidad_operativa_id ?? null;
        abort_if(!$unidadId, 403, 'Tu usuario no tiene unidad operativa asignada.');

        // Semana pasada (Lun-Vie)
        $inicio = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $fin    = Carbon::parse($inicio)->addDays(4)->toDateString();

        $dias = [];
        for ($i = 0; $i < 5; $i++) {
            $dias[] = Carbon::parse($inicio)->addDays($i)->toDateString();
        }

        // Registros existentes (keyBy fecha)
        $existentes = DB::table('corte_caja')
            ->where('unidad_id', $unidadId)
            ->whereIn('fecha', $dias)
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->fecha)->toDateString());

        // Días faltantes
        $faltantes = [];
        foreach ($dias as $f) {
            if (!$existentes->has($f)) $faltantes[] = $f;
        }

        // ✅ Lo que tu vista espera: $rows (array de arrays)
        $rows = [];
        foreach ($dias as $f) {
            $row = $existentes->get($f);

            $ef = $row->cantidad_efectivo ?? 0;
            $cr = $row->cantidad_credito ?? 0;

            $rows[] = [
                'fecha' => $f,
                'cantidad_efectivo' => (float)$ef,
                'cantidad_credito'  => (float)$cr,
                'total' => (float)$ef + (float)$cr,
            ];
        }

        return view('dashboard.corte_caja_forzar', compact(
            'inicio', 'fin', 'dias', 'existentes', 'faltantes', 'rows'
        ));
    }


public function forzarGuardarSemanaPasada(Request $request)
    {
        $user = Auth::user();
        abort_unless(($user->role ?? '') === 'encargado_cafeteria', 403);

        $unidadId = (int)($user->unidad_operativa_id ?? 0);
        // si en users tu campo se llama unidad_id:
        // $unidadId = (int)($user->unidad_id ?? 0);

        abort_if(!$unidadId, 403, 'Tu usuario no tiene unidad asignada.');

        // ✅ OJO: el form manda rows[...] (como tu vista)
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:5'],
            'rows.*.fecha' => ['required', 'date'],
            'rows.*.cantidad_efectivo' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'rows.*.cantidad_credito'  => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        foreach ($data['rows'] as $r) {
            $fecha = Carbon::parse($r['fecha'])->toDateString();

            $ef = (float)($r['cantidad_efectivo'] ?? 0);
            $cr = (float)($r['cantidad_credito'] ?? 0);
            if ($ef < 0) $ef = 0; if ($ef > 99999) $ef = 99999;
            if ($cr < 0) $cr = 0; if ($cr > 99999) $cr = 99999;

            $total = $ef + $cr;

            // semana/mes/año (tu tabla tiene semana, mes, anio)
            $dt = Carbon::parse($fecha);
            $semana = (int)$dt->isoWeek(); // o weekOfYear si así lo manejas
            $mes    = (int)$dt->month;
            $anio   = (int)$dt->year;

            CorteCaja::updateOrCreate(
                [
                    'unidad_id' => $unidadId,
                    'fecha'     => $fecha,
                ],
                [
                    'cantidad_efectivo' => $ef,
                    'cantidad_credito'  => $cr,
                    'total'             => $total,
                    'semana'            => $semana,
                    'mes'               => $mes,
                    'anio'              => $anio,
                ]
            );
        }

        return redirect()->route('dashboard.home')
            ->with('success', 'Semana pasada registrada correctamente.');
    }
    

}
