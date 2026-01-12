<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorTarjeta extends Model
{
    protected $table = 'proveedor_tarjetas';

    protected $fillable = [
        'proveedor_id',
        'tipo',
        'alias',
        'banco',
        'titular',
        'clabe',
        'cuenta',
        'tarjeta',
        'activa',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
