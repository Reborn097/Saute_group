<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'presentacion_id',
        'almacen_id',
        'almacen_destino_id',
        'tipo',
        'cantidad',
        'motivo',
        'referencia',
        'fecha',
        'usuario_id',
        'caducidad',
        'costo_unitario',
        'costo_total',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'fecha' => 'date',
        'caducidad' => 'date',
        'costo_unitario' => 'float',
        'costo_total' => 'float',
    ];
}
