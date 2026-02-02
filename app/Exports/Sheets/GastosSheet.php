<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class GastosSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private $unidadOperativaId,
        private string $desde,
        private string $hasta
    ) {}

    public function title(): string { return 'Gastos'; }

    public function headings(): array
    {
        return ['Semana', 'Total gasto'];
    }

    public function collection()
    {
        $desdeStr = Carbon::parse($this->desde)->toDateString();
        $hastaStr = Carbon::parse($this->hasta)->toDateString();

        $usersTieneUnidadOperativa = Schema::hasColumn('users', 'unidad_operativa_id');
        $usersTieneUnidad = Schema::hasColumn('users', 'unidad_id');

        $q = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->selectRaw("
                YEARWEEK(p.created_at, 1) as semana,
                MIN(DATE(p.created_at)) as semana_inicio,
                MAX(DATE(p.created_at)) as semana_fin,
                SUM(COALESCE(d.subtotal,0)) as total_gasto
            ")
            ->whereBetween(DB::raw('DATE(p.created_at)'), [$desdeStr, $hastaStr]);

        if ($this->unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $q->join('users as u', 'u.id', '=', 'p.user_id')
                  ->where('u.unidad_operativa_id', (int)$this->unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $q->join('users as u', 'u.id', '=', 'p.user_id')
                  ->where('u.unidad_id', (int)$this->unidadOperativaId);
            }
        }

        return $q->groupBy(DB::raw('YEARWEEK(p.created_at, 1)'))
            ->orderBy(DB::raw('YEARWEEK(p.created_at, 1)'), 'desc')
            ->get()
            ->map(function ($row) {
                $row->semana = $row->semana_inicio . ' - ' . $row->semana_fin;
                unset($row->semana_inicio, $row->semana_fin);
                return $row;
            });
    }
}
