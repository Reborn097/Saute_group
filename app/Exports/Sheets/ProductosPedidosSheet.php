<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductosPedidosSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private $unidadOperativaId,
        private string $desde,
        private string $hasta
    ) {}

    public function title(): string { return 'Productos pedidos'; }

    public function headings(): array
    {
        return ['Fecha', 'Producto', 'Cantidad total', 'Precio promedio', 'Total'];
    }

    public function collection()
    {
        $desdeStr = Carbon::parse($this->desde)->toDateString();
        $hastaStr = Carbon::parse($this->hasta)->toDateString();

        $usersTieneUnidadOperativa = Schema::hasColumn('users', 'unidad_operativa_id');
        $usersTieneUnidad = Schema::hasColumn('users', 'unidad_id');

        $q = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->join('producto_proveedor as pp', 'pp.id', '=', 'd.producto_proveedor_id')
            ->join('productos as pr', 'pr.id', '=', 'pp.producto_id')
            ->selectRaw("
                DATE(p.created_at) as fecha,
                pr.nombre as producto,
                SUM(COALESCE(d.cantidad_aprobada, d.cantidad_solicitada, 0)) as cantidad_total,
                AVG(COALESCE(d.precio_unitario, 0)) as precio_promedio,
                SUM(COALESCE(d.subtotal, 0)) as total
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

        return $q->groupBy(DB::raw('DATE(p.created_at)'), 'pr.nombre')
            ->orderBy(DB::raw('DATE(p.created_at)'), 'desc')
            ->orderBy('pr.nombre', 'asc')
            ->get();
    }
}
