<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresentacionProveedor extends Model
{
    protected $table = 'presentacion_proveedor';

    protected $fillable = [
        'presentacion_id',
        'proveedor_id',
        'precio_vigente',
        'estado',
    ];

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(ProductoPresentacion::class, 'presentacion_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}

