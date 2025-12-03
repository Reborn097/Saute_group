<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = [
        'nombre',
        'tipo',
        'ubicacion',
        'unidad_id'
    ];

    public function unidad()
    {
        return $this->belongsTo(UnidadOperativa::class, 'unidad_id');
    }
}
