<?php

namespace App\Exports;

use App\Models\PresentacionProveedor;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PlantillaPreciosProveedorExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly int $proveedorId)
    {
    }

    public function headings(): array
    {
        return [
            'producto',
            'presentacion',
            'valor_medida',
            'unidad_medida',
            'precio',
            'fecha_vigencia_inicio',
            'fecha_vigencia_final',
        ];
    }

    public function collection()
    {
        return PresentacionProveedor::query()
            ->with(['presentacion.producto', 'historialUltimo'])
            ->where('proveedor_id', $this->proveedorId)
            ->where('estado', 1)
            ->whereHas('proveedor', fn ($q) => $q->where('estado', 1))
            ->whereHas('presentacion.producto')
            ->orderBy('id')
            ->get()
            ->map(function (PresentacionProveedor $relacion) {
                $presentacion = $relacion->presentacion;
                $producto = $presentacion?->producto;
                $historial = $relacion->historialUltimo;

                $inicio = $historial?->vigencia_inicio
                    ? Carbon::parse($historial->vigencia_inicio)->format('Y-m-d')
                    : now()->format('Y-m-d');

                $fin = $historial?->vigencia_fin
                    ? Carbon::parse($historial->vigencia_fin)->format('Y-m-d')
                    : null;

                return [
                    'producto' => (string) ($producto?->nombre ?? ''),
                    'presentacion' => (string) ($presentacion?->descripcion ?? ''),
                    'valor_medida' => $presentacion?->contenido,
                    'unidad_medida' => (string) ($presentacion?->unidad_contenido ?? ''),
                    'precio' => $relacion->precio_vigente,
                    'fecha_vigencia_inicio' => $inicio,
                    'fecha_vigencia_final' => $fin,
                ];
            });
    }
}
