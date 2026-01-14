<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kardex extends Model
{
    protected $table = 'kardex';

    protected $fillable = [
        'cantidad',
        'fecha_movimiento',
        'tipo_movimiento', // entrada|salida|ajuste
        'motivo',
        'producto_id',
        'inventario_id',
        'user_id',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'fecha_movimiento' => 'datetime',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
