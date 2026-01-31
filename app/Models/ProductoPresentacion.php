<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoPresentacion extends Model
{
    protected $table = 'producto_presentaciones';

    protected $fillable = [
        'producto_id',
        'descripcion',
        'estado',
        // si tienes otros campos: valor_medida, unidad_medida, etc, agrégalos aquí
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function proveedores(): HasMany
    {
        // tabla: presentacion_proveedor
        return $this->hasMany(PresentacionProveedor::class, 'presentacion_id');
    }

    // opcional bonito: "Coca Cola 600ml - Paquete 6 pzas"
    public function getEtiquetaAttribute(): string
    {
        $base = $this->producto?->nombre ?? 'Producto';
        return trim($base . ' - ' . ($this->descripcion ?? ''));
    }
}
