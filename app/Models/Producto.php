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
        'categoria_id', 
        'valor_medida',
        'unidad_medida',
        'estado'
    ];

    // Relación con categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Relación con proveedor
    public function proveedores()
{
    return $this->belongsToMany(Proveedor::class, 'producto_proveedor', 'producto_id', 'proveedor_id')
                ->withPivot(['precio', 'fecha_vigencia_inicio', 'fecha_vigencia_final', 'estado']);
}


}
