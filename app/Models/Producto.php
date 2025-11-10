<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'productos';

    // Campos que pueden asignarse masivamente
    protected $fillable = [
        'nombre',
        'categoria_id',
        'valor_medida',
        'unidad_medida',
        'estado'
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
}
