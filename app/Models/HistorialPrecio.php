<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialPrecio extends Model
{
    use HasFactory;

    protected $table = 'historial_precio';

    protected $fillable = [
        'producto_proveedor_id',
        'precio',
        'fecha_vigencia_inicio',
        'fecha_vigencia_final',
    ];

    public function productoProveedor()
    {
        return $this->belongsTo(ProductoProveedor::class, 'producto_proveedor_id');
    }
}
