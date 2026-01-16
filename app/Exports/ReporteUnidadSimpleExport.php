<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ReporteUnidadSimpleExport implements FromArray
{
    public function __construct(public array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }
}
