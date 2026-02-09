<?php

namespace App\Http\Controllers;

use App\Models\ControlKilometraje;
use App\Models\UnidadOperativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class KilometrajeController extends Controller
{
    private function validarAcceso(): void
    {
        $role = mb_strtolower(trim((string) (auth()->user()->role ?? '')), 'UTF-8');
        $role = preg_replace('/[^a-z0-9]+/i', '_', $role);
        $role = preg_replace('/_+/', '_', $role);
        $role = trim($role, '_');
        abort_unless(in_array($role, ['admin', 'encargado_cafeteria', 'encargado_cocina'], true), 403);
    }

    public function index(Request $request)
    {
        $this->validarAcceso();

        $user = auth()->user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        $unidades = UnidadOperativa::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

        $unidadSel = $request->get('unidad_id');
        if (!$esAdmin) {
            $unidadSel = $user->unidad_operativa_id ?? null;
        }

        $semanaInicio = $request->get('semana_inicio');
        $inicio = $semanaInicio
            ? Carbon::parse($semanaInicio)->startOfDay()
            : Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $fin = (clone $inicio)->addDays(6)->endOfDay();

        $registros = collect();
        if (!empty($unidadSel)) {
            $registros = ControlKilometraje::query()
                ->select([
                    'fecha',
                    'km_inicio',
                    'km_final',
                    'km_recorridos',
                    'diesel_inicio_pct',
                    'diesel_final_pct',
                    'lugares_visitados',
                ])
                ->where('unidad_operativa_id', $unidadSel)
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->get()
                ->keyBy(fn($r) => $r->fecha->toDateString());
        }

        return view('dashboard.kilometraje', [
            'unidades' => $unidades,
            'unidadSel' => $unidadSel,
            'esAdmin' => $esAdmin,
            'inicio' => $inicio,
            'fin' => $fin,
            'registros' => $registros,
        ]);
    }

    public function reporte(Request $request)
    {
        $this->validarAcceso();

        $user = auth()->user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        $unidades = UnidadOperativa::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

        $repDesde = $request->get('rep_desde');
        $repHasta = $request->get('rep_hasta');
        $repUnidad = $request->get('rep_unidad_id');

        $hoy = Carbon::today();
        if (!$repDesde) $repDesde = $hoy->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        if (!$repHasta) $repHasta = $hoy->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        if (!$esAdmin) {
            $repUnidad = $user->unidad_operativa_id ?? null;
        }

        $repDesdeC = Carbon::parse($repDesde)->startOfDay();
        $repHastaC = Carbon::parse($repHasta)->endOfDay();

        if ($repHastaC->lt($repDesdeC)) {
            [$repDesdeC, $repHastaC] = [$repHastaC, $repDesdeC];
        }

        $reporte = DB::table('control_kilometraje as ck')
            ->join('unidades_operativas as u', 'u.id', '=', 'ck.unidad_operativa_id')
            ->select([
                'ck.fecha',
                'ck.km_inicio',
                'ck.km_final',
                'ck.km_recorridos',
                'ck.diesel_inicio_pct',
                'ck.diesel_final_pct',
                'ck.lugares_visitados',
                'u.nombre as unidad_nombre',
            ])
            ->whereBetween('ck.fecha', [$repDesdeC->toDateString(), $repHastaC->toDateString()])
            ->when($repUnidad, fn($q) => $q->where('ck.unidad_operativa_id', (int)$repUnidad))
            ->orderBy('ck.fecha', 'asc')
            ->get();

        $totales = [
            'dias' => $reporte->count(),
            'km_recorridos' => (int) $reporte->sum(fn($r) => (int)($r->km_recorridos ?? 0)),
            'km_inicio_min' => $reporte->min('km_inicio'),
            'km_final_max' => $reporte->max('km_final'),
            'diesel_inicio_prom' => $reporte->avg('diesel_inicio_pct'),
            'diesel_final_prom' => $reporte->avg('diesel_final_pct'),
        ];

        return view('dashboard.kilometraje_reporte', [
            'unidades' => $unidades,
            'esAdmin' => $esAdmin,
            'repDesde' => $repDesdeC->toDateString(),
            'repHasta' => $repHastaC->toDateString(),
            'repUnidad' => $repUnidad,
            'reporte' => $reporte,
            'totales' => $totales,
        ]);
    }

    public function guardarTodo(Request $request)
    {
        $this->validarAcceso();

        $user = Auth::user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        $data = $request->validate([
            'unidad_operativa_id' => ['required', 'integer', 'exists:unidades_operativas,id'],
            'semana_inicio' => ['required', 'date'],
            'rows' => ['required', 'array'],
            'rows.*.fecha' => ['required', 'date'],
            'rows.*.km_inicio' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'rows.*.km_final' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'rows.*.km_recorridos' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'rows.*.diesel_inicio_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.diesel_final_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.lugares_visitados' => ['nullable', 'string', 'max:1000'],
        ]);

        $unidadId = (int) $data['unidad_operativa_id'];

        if (!$esAdmin && (int)($user->unidad_operativa_id ?? 0) !== $unidadId) {
            abort(403);
        }

        $semanaInicio = Carbon::parse($data['semana_inicio'])->startOfDay();
        $semanaFin = (clone $semanaInicio)->addDays(6)->endOfDay();

        $faltantes = [];
        $invalidos = [];

        foreach ($data['rows'] as $r) {
            $fecha = Carbon::parse($r['fecha'])->startOfDay();

            if ($fecha->lt($semanaInicio) || $fecha->gt($semanaFin)) {
                continue;
            }

            $kmInicio = isset($r['km_inicio']) && is_numeric($r['km_inicio']) ? (int)$r['km_inicio'] : null;
            $kmFinal = isset($r['km_final']) && is_numeric($r['km_final']) ? (int)$r['km_final'] : null;
            $dieselInicio = isset($r['diesel_inicio_pct']) && is_numeric($r['diesel_inicio_pct']) ? (float)$r['diesel_inicio_pct'] : null;
            $dieselFinal = isset($r['diesel_final_pct']) && is_numeric($r['diesel_final_pct']) ? (float)$r['diesel_final_pct'] : null;
            $lugares = isset($r['lugares_visitados']) ? trim((string)$r['lugares_visitados']) : null;
            if ($lugares === '') $lugares = null;

            $hayDatos = !is_null($kmInicio) || !is_null($kmFinal) || !is_null($dieselInicio) || !is_null($dieselFinal) || !is_null($lugares);
            if (!$hayDatos) {
                continue;
            }

            $completo = !is_null($kmInicio) && !is_null($kmFinal) && !is_null($dieselInicio) && !is_null($dieselFinal) && !is_null($lugares);
            if (!$completo) {
                $faltantes[] = $fecha->toDateString();
                continue;
            }

            if ($kmFinal < $kmInicio) {
                $invalidos[] = $fecha->toDateString();
            }
        }

        if (!empty($faltantes) || !empty($invalidos)) {
            $msg = '';
            if (!empty($faltantes)) {
                $msg .= 'Faltan datos en: ' . implode(', ', $faltantes) . '. ';
            }
            if (!empty($invalidos)) {
                $msg .= 'Kilometraje final menor al inicio en: ' . implode(', ', $invalidos) . '.';
            }
            return back()->with('error', trim($msg))->withInput();
        }

        DB::transaction(function () use ($data, $unidadId, $semanaInicio, $semanaFin, $user) {
            foreach ($data['rows'] as $r) {
                $fecha = Carbon::parse($r['fecha'])->startOfDay();

                if ($fecha->lt($semanaInicio) || $fecha->gt($semanaFin)) {
                    continue;
                }

                $kmInicio = isset($r['km_inicio']) && is_numeric($r['km_inicio']) ? (int)$r['km_inicio'] : null;
                $kmFinal = isset($r['km_final']) && is_numeric($r['km_final']) ? (int)$r['km_final'] : null;
                $dieselInicio = isset($r['diesel_inicio_pct']) && is_numeric($r['diesel_inicio_pct']) ? (float)$r['diesel_inicio_pct'] : null;
                $dieselFinal = isset($r['diesel_final_pct']) && is_numeric($r['diesel_final_pct']) ? (float)$r['diesel_final_pct'] : null;
                $lugares = isset($r['lugares_visitados']) ? trim((string)$r['lugares_visitados']) : null;
                if ($lugares === '') $lugares = null;

                $hayDatos = !is_null($kmInicio) || !is_null($kmFinal) || !is_null($dieselInicio) || !is_null($dieselFinal) || !is_null($lugares);
                if (!$hayDatos) {
                    continue;
                }

                $kmRec = max(0, $kmFinal - $kmInicio);

                ControlKilometraje::updateOrCreate(
                    [
                        'unidad_operativa_id' => $unidadId,
                        'fecha' => $fecha->toDateString(),
                    ],
                    [
                        'km_inicio' => $kmInicio,
                        'km_final' => $kmFinal,
                        'km_recorridos' => $kmRec,
                        'diesel_inicio_pct' => $dieselInicio,
                        'diesel_final_pct' => $dieselFinal,
                        'lugares_visitados' => $lugares,
                        'user_id' => $user->id,
                    ]
                );
            }
        });

        return redirect()->route('dashboard.kilometraje', [
            'unidad_id' => $unidadId,
            'semana_inicio' => $semanaInicio->toDateString(),
        ])->with('ok', 'Kilometraje guardado.');
    }

    public function exportPdf(Request $request)
    {
        $this->validarAcceso();

        $user = auth()->user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        $repDesde = $request->get('rep_desde');
        $repHasta = $request->get('rep_hasta');
        $repUnidad = $request->get('rep_unidad_id');

        if (!$repDesde || !$repHasta) {
            $hoy = Carbon::today();
            $repDesde = $repDesde ?: $hoy->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $repHasta = $repHasta ?: $hoy->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        }

        if (!$esAdmin) {
            $repUnidad = $user->unidad_operativa_id ?? null;
        }

        $desdeC = Carbon::parse($repDesde)->startOfDay();
        $hastaC = Carbon::parse($repHasta)->endOfDay();

        if ($hastaC->lt($desdeC)) {
            [$desdeC, $hastaC] = [$hastaC, $desdeC];
        }

        $rows = DB::table('control_kilometraje as ck')
            ->join('unidades_operativas as u', 'u.id', '=', 'ck.unidad_operativa_id')
            ->select([
                'ck.fecha',
                'ck.km_inicio',
                'ck.km_final',
                'ck.km_recorridos',
                'ck.diesel_inicio_pct',
                'ck.diesel_final_pct',
                'ck.lugares_visitados',
                'u.nombre as unidad_nombre',
            ])
            ->whereBetween('ck.fecha', [$desdeC->toDateString(), $hastaC->toDateString()])
            ->when($repUnidad, fn($q) => $q->where('ck.unidad_operativa_id', (int)$repUnidad))
            ->orderBy('ck.fecha', 'asc')
            ->get();

        $totales = [
            'dias' => $rows->count(),
            'km_recorridos' => (int) $rows->sum(fn($r) => (int)($r->km_recorridos ?? 0)),
            'km_inicio_min' => $rows->min('km_inicio'),
            'km_final_max' => $rows->max('km_final'),
            'diesel_inicio_prom' => $rows->avg('diesel_inicio_pct'),
            'diesel_final_prom' => $rows->avg('diesel_final_pct'),
        ];

        $unidadNombre = null;
        if ($repUnidad) {
            $unidadNombre = UnidadOperativa::where('id', (int)$repUnidad)->value('nombre');
        }

        $pdf = Pdf::loadView('dashboard.kilometraje_pdf', [
            'desdeStr' => $desdeC->toDateString(),
            'hastaStr' => $hastaC->toDateString(),
            'unidadNombre' => $unidadNombre,
            'rows' => $rows,
            'totales' => $totales,
        ])->setPaper('letter', 'landscape');

        return $pdf->download('kilometraje_' . now()->format('Ymd_His') . '.pdf');
    }
}
