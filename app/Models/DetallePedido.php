<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    use HasFactory;

    protected $table = 'detalle_pedidos';

    protected $fillable = [
        'codigo',
        'producto_proveedor_id',
        'cantidad_solicitada',
        'precio_unitario',
        'subtotal',
    ];

    public function productoProveedor()
    {
        return $this->belongsTo(ProductoProveedor::class, 'producto_proveedor_id');
    }
}
