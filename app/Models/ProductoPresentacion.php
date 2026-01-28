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
        'contenido',
        'unidad_contenido',
        'unidad_base',
        'estado',
    ];

    protected $casts = [
        'contenido' => 'decimal:3',
        'estado' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(PresentacionProveedor::class, 'presentacion_id');
    }

    // Para pintar bonito en vistas
    public function getEtiquetaAttribute(): string
    {
        // ej: "Bote 3.4 kg" o "Paquete 25 pzas"
        $partes = [$this->descripcion];

        if (!empty($this->contenido) && !empty($this->unidad_contenido)) {
            $partes[] = trim($this->contenido . ' ' . $this->unidad_contenido);
        }

        return implode(' | ', $partes);
    }
}
