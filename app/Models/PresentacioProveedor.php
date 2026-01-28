<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PresentacionProveedor extends Model
{
    protected $table = 'presentacion_proveedor';

    protected $fillable = [
        'presentacion_id',
        'proveedor_id',
        'precio_vigente',
        'estado',
    ];

    protected $casts = [
        'precio_vigente' => 'decimal:2',
        'estado' => 'boolean',
    ];

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(ProductoPresentacion::class, 'presentacion_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialPrecio::class, 'presentacion_proveedor_id');
    }

    // Precio vigente real (source of truth)
    public function precioVigenteHistorial(): HasOne
    {
        return $this->hasOne(HistorialPrecio::class, 'presentacion_proveedor_id')
            ->whereNull('vigencia_fin')
            ->latest('vigencia_inicio');
    }
}
