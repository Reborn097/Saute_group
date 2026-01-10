<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'marca',              // ✅ NUEVO
        'categoria_id',
        'valor_medida',
        'unidad_medida',
        'estado',
    ];

    // ✅ Recomendado: tratar estado como boolean
    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Relación con Categoría
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Relación muchos a muchos con Proveedor (tabla pivote producto_proveedor)
     */
    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'producto_proveedor', 'producto_id', 'proveedor_id')
            ->withPivot('precio', 'fecha_vigencia_inicio', 'fecha_vigencia_final', 'estado')
            ->withTimestamps();
    }

    /**
     * Relaciones directas a la tabla pivote (útil para consultas/ordenamientos)
     */
    public function relaciones()
    {
        return $this->hasMany(ProductoProveedor::class, 'producto_id');
    }
}

