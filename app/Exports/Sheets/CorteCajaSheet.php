<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CorteCajaSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private $unidadOperativaId,
        private string $desde,
        private string $hasta
    ) {}

    public function title(): string { return 'Corte caja'; }

    public function headings(): array
    {
        return ['Fecha', 'Efectivo', 'Crédito', 'Total', 'Semana', 'Mes', 'Año', 'Unidad ID'];
    }

    public function collection()
    {
        $desdeStr = Carbon::parse($this->desde)->toDateString();
        $hastaStr = Carbon::parse($this->hasta)->toDateString();

        return DB::table('corte_caja as cc')
            ->select([
                'cc.fecha',
                'cc.cantidad_efectivo',
                'cc.cantidad_credito',
                'cc.total',
                'cc.semana',
                'cc.mes',
                'cc.anio',
                'cc.unidad_id',
            ])
            ->whereBetween('cc.fecha', [$desdeStr, $hastaStr])
            ->when($this->unidadOperativaId, fn($q) => $q->where('cc.unidad_id', (int)$this->unidadOperativaId))
            ->orderBy('cc.fecha', 'desc')
            ->get();
    }
}
