<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ComensalesSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private $unidadOperativaId,
        private string $desde,
        private string $hasta
    ) {}

    public function title(): string { return 'Comensales'; }

    public function headings(): array
    {
        return ['Fecha', 'Total comensales'];
    }

    public function collection()
    {
        $desdeStr = Carbon::parse($this->desde)->toDateString();
        $hastaStr = Carbon::parse($this->hasta)->toDateString();

        return DB::table('comensales_registros as cr')
            ->selectRaw("cr.fecha, SUM(COALESCE(cr.cantidad,0)) as total_comensales")
            ->whereBetween('cr.fecha', [$desdeStr, $hastaStr])
            ->when($this->unidadOperativaId, fn($q) => $q->where('cr.unidad_operativa_id', (int)$this->unidadOperativaId))
            ->groupBy('cr.fecha')
            ->orderBy('cr.fecha', 'desc')
            ->get();
    }
}
