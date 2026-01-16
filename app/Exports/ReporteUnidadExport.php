<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReporteUnidadExport implements FromView
{
    public function __construct(
        public int $unidadId,
        public string $desde,
        public string $hasta
    ) {}

    public function view(): View
    {
        // Reutilizamos la vista “pdf” para Excel (sirve perfecto)
        // Solo que para Excel es mejor no usar estilos complejos.
        return view('dashboard.reportes_excel', [
            'unidadSel' => $this->unidadId,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
        ]);
    }
}
