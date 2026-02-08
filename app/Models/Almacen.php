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
        'unidad_id',
        'es_cedis',
    ];

    protected $casts = [
        'es_cedis' => 'boolean',
    ];

    public function unidad()
    {
        return $this->belongsTo(UnidadOperativa::class, 'unidad_id');
    }

    public function isCedis(): bool
    {
        if ((bool) ($this->es_cedis ?? false)) {
            return true;
        }

        return mb_strtolower((string) ($this->tipo ?? '')) === 'cedis';
    }
}
