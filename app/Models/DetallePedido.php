<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'detalle_pedidos';

    // Campos que se pueden llenar de forma masiva
    protected $fillable = [
        'codigo',
        'producto_proveedor_id',
        'precio_unitario',
        'cantidad_solicitada',
        'cantidad_aprobada',
        'motivos_cambios',
    ];

    // Relación con el pedido (por el campo 'codigo')
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'codigo', 'codigo');
    }

    // Relación con el producto_proveedor
    public function productoProveedor()
    {
        return $this->belongsTo(ProductoProveedor::class, 'producto_proveedor_id');
    }

    // Relación directa con producto (por medio del modelo ProductoProveedor)
    public function producto()
    {
        return $this->hasOneThrough(
            Producto::class,
            ProductoProveedor::class,
            'id',                // Clave foránea en producto_proveedor
            'id',                // Clave primaria en productos
            'producto_proveedor_id', // Clave foránea en detalle_pedidos
            'producto_id'        // Clave foránea en producto_proveedor
        );
    }
}
