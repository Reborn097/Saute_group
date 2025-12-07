<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CorteCaja;
use App\Models\UnidadOperativa;
use Carbon\Carbon;

class CorteCajaController extends Controller
{
    // ===================== VISTA PRINCIPAL =====================
    public function index(Request $request)
    {
        $mes  = (int)($request->input('mes', now()->month));
        $anio = (int)($request->input('anio', now()->year));

        // Unidades / locales
        $unidades = UnidadOperativa::orderBy('nombre')->get();

        $localId = (int)$request->input('local', $unidades->first()->id ?? 0);

        // Construir matriz de semanas / días
        [$semanas, $totalesMes] = $this->buildMatrix($anio, $mes, $localId);

        return view('dashboard.corte_caja', [
            'mes'        => $mes,
            'anio'       => $anio,
            'unidades'   => $unidades,
            'localId'    => $localId,
            'semanas'    => $semanas,
            'totalesMes' => $totalesMes,
        ]);
    }

    // ===================== OBTENER DATOS PARA AJAX =====================
    public function obtenerDatos($anio, $mes, $localId)
    {
        $anio    = (int)$anio;
        $mes     = (int)$mes;
        $localId = (int)$localId;

        [$semanas, $totalesMes] = $this->buildMatrix($anio, $mes, $localId);

        return view('dashboard.partials.corte_caja_tabla', [
            'semanas'    => $semanas,
            'totalesMes' => $totalesMes,
        ]);
    }

    // ===================== GUARDAR / ACTUALIZAR DÍA =====================
    public function guardar(Request $request)
    {
        $data = $request->validate([
            'fecha'             => 'required|date',
            'cantidad_efectivo' => 'nullable|numeric|min:0',
            'cantidad_credito'  => 'nullable|numeric|min:0',
            'unidad_id'         => 'required|integer|exists:unidades,id',
        ]);

        $fecha  = Carbon::parse($data['fecha']);
        $ef     = $data['cantidad_efectivo'] ?? 0;
        $cr     = $data['cantidad_credito'] ?? 0;
        $total  = $ef + $cr;

        CorteCaja::updateOrCreate(
            [
                'fecha'     => $fecha->toDateString(),
                'unidad_id' => $data['unidad_id'],
            ],
            [
                'cantidad_efectivo' => $ef,
                'cantidad_credito'  => $cr,
                'total'             => $total,
                'semana'            => $fecha->weekOfMonth,
                'mes'               => $fecha->month,
                'anio'              => $fecha->year,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Corte de caja guardado correctamente.',
        ]);
    }

    // ==========================================================
    //   FUNCIÓN PRIVADA: CONSTRUIR MATRIZ (SEMANAS REALES)
    // ==========================================================
    private function buildMatrix(int $anio, int $mes, int $localId): array
    {
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes    = (clone $inicioMes)->endOfMonth();

        // Para que siempre empiece en lunes y termine en domingo
        $inicioGrid = (clone $inicioMes)->startOfWeek(Carbon::MONDAY);
        $finGrid    = (clone $finMes)->endOfWeek(Carbon::SUNDAY);

        $cursor     = $inicioGrid->copy();
        $semanas    = [];
        $totalesMes = [
            'efectivo' => 0,
            'credito'  => 0,
            'total'    => 0,
        ];

        while ($cursor <= $finGrid) {

            $diasSemana          = [];
            $totalEfSemana       = 0;
            $totalCrSemana       = 0;
            $totalSemana         = 0;

            for ($i = 0; $i < 7; $i++) {
                $fecha = $cursor->copy();
                $dentroMes = $fecha->month == $mes;

                $registro = null;
                $ef = 0;
                $cr = 0;
                $tot = 0;

                if ($dentroMes && $localId) {
                    $registro = CorteCaja::whereDate('fecha', $fecha->toDateString())
                        ->where('unidad_id', $localId)
                        ->first();

                    if ($registro) {
                        $ef  = (float)$registro->cantidad_efectivo;
                        $cr  = (float)$registro->cantidad_credito;
                        $tot = $ef + $cr;
                    }
                }

                if ($dentroMes) {
                    $totalEfSemana       += $ef;
                    $totalCrSemana       += $cr;
                    $totalSemana         += $tot;

                    $totalesMes['efectivo'] += $ef;
                    $totalesMes['credito']  += $cr;
                    $totalesMes['total']    += $tot;
                }

                $diasSemana[] = [
                    'fecha'      => $fecha,
                    'dentroMes'  => $dentroMes,
                    'efectivo'   => $ef,
                    'credito'    => $cr,
                    'total'      => $tot,
                ];

                $cursor->addDay();
            }

            $semanas[] = [
                'dias'           => $diasSemana,
                'total_efectivo' => $totalEfSemana,
                'total_credito'  => $totalCrSemana,
                'total_semana'   => $totalSemana,
            ];
        }

        return [$semanas, $totalesMes];
    }
}
