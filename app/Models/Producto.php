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
        'marca',
        'categoria_id',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Relación con Categoría
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * ✅ NUEVO MODELO CORRECTO:
     * Producto -> muchas Presentaciones
     * (tabla: producto_presentaciones)
     */
    public function presentaciones()
    {
        return $this->hasMany(ProductoPresentacion::class, 'producto_id');
    }

    /**
     * ✅ Acceso indirecto a proveedores (a través de PresentacionProveedor)
     * Esto no es "belongsToMany" directo, porque el precio depende de PRESENTACIÓN.
     */
    public function presentacionProveedores()
    {
        return $this->hasManyThrough(
            PresentacionProveedor::class,     // tabla final
            ProductoPresentacion::class,      // tabla intermedia
            'producto_id',                    // FK en producto_presentaciones
            'presentacion_id',                // FK en presentacion_proveedor
            'id',                             // PK en productos
            'id'                              // PK en producto_presentaciones
        );
    }

    /**
     * (Opcional) Si quieres obtener proveedores únicos del producto:
     * $producto->proveedoresUnicos()
     */
    public function proveedoresUnicos()
    {
        // devuelve un query builder (no colección directa)
        return Proveedor::query()
            ->whereIn('id', $this->presentacionProveedores()->select('proveedor_id'));
    }

    /**
     * -------------------------
     * LEGACY (solo si aún existe tu tabla vieja producto_proveedor)
     * -------------------------
     * Úsalo temporalmente mientras migras vistas/controladores.
     *
     * public function proveedoresLegacy()
     * {
     *     return $this->belongsToMany(Proveedor::class, 'producto_proveedor', 'producto_id', 'proveedor_id')
     *         ->withPivot('precio', 'fecha_vigencia_inicio', 'fecha_vigencia_final', 'estado')
     *         ->withTimestamps();
     * }
     *
     * public function relacionesLegacy()
     * {
     *     return $this->hasMany(ProductoProveedor::class, 'producto_id');
     * }
     */
}
