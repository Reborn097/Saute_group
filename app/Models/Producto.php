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
        'proveedor_id',  // ✅ nuevo
        'valor_medida',
        'unidad_medida',
        'precio',
        'estado',
    ];

    // Relación con categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Relación con proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
