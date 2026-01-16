<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorteCaja extends Model
{
    protected $table = 'corte_caja';

    protected $fillable = [
        'fecha',
        'cantidad_efectivo',
        'cantidad_credito',
        'unidad_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad_efectivo' => 'decimal:2',
        'cantidad_credito' => 'decimal:2',
    ];

    public function unidadOperativa()
    {
        return $this->belongsTo(UnidadOperativa::class, 'unidad_id');
    }
}
