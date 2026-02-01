<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductoPresentacion;

class InventarioCaducidad extends Model
{
    protected $table = 'inventario_caducidades';

    protected $fillable = [
        'inventario_id',
        'producto_id',
        'presentacion_id',
        'almacen_id',
        'cantidad',
        'caducidad',
        'lote',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'caducidad' => 'date',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function presentacion()
    {
        return $this->belongsTo(ProductoPresentacion::class, 'presentacion_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}
