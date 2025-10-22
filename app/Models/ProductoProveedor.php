<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoProveedor extends Model
{
    use HasFactory;

    protected $table = 'producto_proveedor';
    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'precio',
        'fecha_vigencia_inicio',
        'fecha_vigencia_final',
        'estado'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
