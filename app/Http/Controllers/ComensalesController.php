<?php

namespace App\Http\Controllers;

use App\Models\ComensalRegistro;
use App\Models\UnidadOperativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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


    public function forzarSemanaPasada()
    {
        $user = Auth::user();

        abort_unless(($user->role ?? '') === 'encargado_cocina', 403);

        $unidadId = $user->unidad_operativa_id;
        abort_if(!$unidadId, 403, 'Tu usuario no tiene unidad operativa asignada.');

        // Semana pasada (Lun-Vie)
        $inicio = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $fin    = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString();

        $dias = [];
        for ($i = 0; $i < 5; $i++) {
            $dias[] = Carbon::parse($inicio)->addDays($i)->toDateString();
        }

        $existentes = DB::table('comensales_registros')
            ->where('unidad_operativa_id', $unidadId)
            ->whereBetween('fecha', [$inicio, $fin])
            ->get()
            ->keyBy('fecha');

        $faltantes = array_values(array_filter($dias, fn($f) => !$existentes->has($f)));

        return view('dashboard.comensales_forzar', compact(
            'dias', 'existentes', 'faltantes', 'inicio', 'fin'
        ));
    }

    public function forzarGuardarSemanaPasada(Request $request)
    {
        $user = Auth::user();
        abort_unless(($user->role ?? '') === 'encargado_cocina', 403);

        $unidadId = $user->unidad_operativa_id;
        abort_if(!$unidadId, 403, 'Tu usuario no tiene unidad operativa asignada.');

        // Semana pasada (Lun-Vie)
        $inicio = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $fin    = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString();

        $dias = [];
        for ($i = 0; $i < 5; $i++) $dias[] = Carbon::parse($inicio)->addDays($i)->toDateString();

        $cantidades = $request->input('cantidades', []);

        // ✅ Validación mínima: que existan los 5 keys
        foreach ($dias as $fecha) {
            if (!array_key_exists($fecha, $cantidades)) {
                return back()->with('error', 'Faltan campos por capturar.')->withInput();
            }
            if (!is_numeric($cantidades[$fecha]) || (float)$cantidades[$fecha] < 0) {
                return back()->with('error', 'Cantidad inválida en ' . $fecha)->withInput();
            }
        }

        DB::transaction(function () use ($dias, $cantidades, $unidadId, $user) {
            foreach ($dias as $fecha) {
                $cantidad = (int)$cantidades[$fecha];

                DB::table('comensales_registros')->updateOrInsert(
                    [
                        'unidad_operativa_id' => $unidadId,
                        'fecha' => $fecha,
                    ],
                    [
                        'cantidad' => $cantidad,
                        'user_id' => $user->id,
                        'updated_at' => now(),
                        'created_at' => now(), // si ya existe, MySQL lo ignora en updateOrInsert? (no, en update lo sobrescribe si lo pasas). Si quieres conservarlo, quítalo.
                    ]
                );
            }
        });

        return redirect()->route('dashboard.home')
            ->with('success', 'Semana pasada registrada correctamente.');
    }

}
