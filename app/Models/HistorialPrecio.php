<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPrecio extends Model
{
    protected $table = 'historial_precios';

    protected $fillable = [
        'presentacion_proveedor_id',
        'precio',
        'vigencia_inicio',
        'vigencia_fin',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'vigencia_inicio' => 'datetime',
        'vigencia_fin' => 'datetime',
    ];

    public function presentacionProveedor(): BelongsTo
    {
        return $this->belongsTo(PresentacionProveedor::class, 'presentacion_proveedor_id');
    }
}
