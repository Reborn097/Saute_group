<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UnidadOperativa;


class CorteCaja extends Model
{
    protected $table = 'corte_caja';

    protected $fillable = [
        'fecha',
        'cantidad_efectivo',
        'cantidad_credito',
        'total',
        'semana',
        'mes',
        'anio',
        'unidad_id'
    ];
}

