<?php

namespace App\Http\Controllers;

use App\Models\ComensalRegistro;
use App\Models\UnidadOperativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComensalesController extends Controller
{
    private function unidadesPermitidasParaUsuario($user)
    {
        $role = (string)($user->role ?? '');

        if ($role === 'admin') {
            return UnidadOperativa::orderBy('nombre')->get();
        }

        if ($role === \App\Models\User::ROLE_RESPONSABLE_UNIDADES) {
            return $user->unidadesAsignadas()->orderBy('nombre')->get();
        }

        return UnidadOperativa::query()
            ->when((int)($user->unidad_operativa_id ?? 0) > 0, fn ($q) => $q->where('id', (int)$user->unidad_operativa_id))
            ->orderBy('nombre')
            ->get();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $unidades = $this->unidadesPermitidasParaUsuario($user);

        $anio = (int) ($request->get('anio') ?? now()->year);
        $mes  = (int) ($request->get('mes')  ?? now()->month);
        $unidadId = $request->get('unidad_id');

        if (!$unidadId && $unidades->isNotEmpty()) {
            $unidadId = $unidades->first()->id;
        }

        if ($unidadId && !$user->puedeAccederUnidadOperativa((int)$unidadId)) {
            abort(403);
        }

        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $fin    = (clone $inicio)->endOfMonth()->endOfDay();

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

    public function guardar(Request $request)
    {
        $data = $request->validate([
            'unidad_operativa_id' => ['required', 'integer', 'exists:unidades_operativas,id'],
            'fecha'               => ['required', 'date'],
            'cantidad'            => ['required', 'integer', 'min:0', 'max:5000'],
            'anio'                => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes'                 => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $unidadId = (int)$data['unidad_operativa_id'];
        if (!Auth::user()->puedeAccederUnidadOperativa($unidadId)) {
            abort(403);
        }

        ComensalRegistro::updateOrCreate(
            [
                'unidad_operativa_id' => $unidadId,
                'fecha'               => Carbon::parse($data['fecha'])->toDateString(),
            ],
            [
                'cantidad' => (int)$data['cantidad'],
            ]
        );

        return redirect()
            ->route('dashboard.comensales', [
                'unidad_id' => $unidadId,
                'anio'      => $data['anio'],
                'mes'       => $data['mes'],
            ])
            ->with('ok', 'Registro guardado.');
    }

    public function guardarTodo(Request $request)
    {
        $data = $request->validate([
            'unidad_operativa_id' => ['required', 'integer', 'exists:unidades_operativas,id'],
            'anio'                => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes'                 => ['required', 'integer', 'min:1', 'max:12'],
            'cantidades'          => ['nullable', 'array'],
            'cantidades.*'        => ['nullable', 'integer', 'min:0', 'max:5000'],
        ]);

        $unidadId   = (int) $data['unidad_operativa_id'];
        $anio       = (int) $data['anio'];
        $mes        = (int) $data['mes'];
        $cantidades = $data['cantidades'] ?? [];

        if (!Auth::user()->puedeAccederUnidadOperativa($unidadId)) {
            abort(403);
        }

        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $finMes    = (clone $inicioMes)->endOfMonth()->endOfDay();

        foreach ($cantidades as $fecha => $cantidad) {
            try {
                $f = Carbon::parse($fecha)->startOfDay();
            } catch (\Exception $e) {
                continue;
            }

            if ($f->lt($inicioMes) || $f->gt($finMes)) {
                continue;
            }

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

        $inicio = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $fin    = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString();

        $dias = [];
        for ($i = 0; $i < 5; $i++) $dias[] = Carbon::parse($inicio)->addDays($i)->toDateString();

        $cantidades = $request->input('cantidades', []);

        foreach ($dias as $fecha) {
            if (!array_key_exists($fecha, $cantidades)) {
                return back()->with('error', 'Faltan campos por capturar.')->withInput();
            }
            if (!is_numeric($cantidades[$fecha]) || (float)$cantidades[$fecha] < 0) {
                return back()->with('error', 'Cantidad invalida en ' . $fecha)->withInput();
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
                        'created_at' => now(),
                    ]
                );
            }
        });

        return redirect()->route('dashboard.home')
            ->with('success', 'Semana pasada registrada correctamente.');
    }
}
