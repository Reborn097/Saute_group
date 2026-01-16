<?php

namespace App\Exports;

use App\Http\Controllers\ReportesController;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportesExport implements WithMultipleSheets
{
    public function __construct(
        private $unidadOperativaId,
        private string $desde,
        private string $hasta
    ) {}

    public function sheets(): array
    {
        return [
            new Sheets\ProductosPedidosSheet($this->unidadOperativaId, $this->desde, $this->hasta),
            new Sheets\GastosSheet($this->unidadOperativaId, $this->desde, $this->hasta),
            new Sheets\ComensalesSheet($this->unidadOperativaId, $this->desde, $this->hasta),
            new Sheets\CorteCajaSheet($this->unidadOperativaId, $this->desde, $this->hasta),
        ];
    }
}
