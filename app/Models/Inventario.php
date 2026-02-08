<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductoPresentacion;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $fillable = [
        'producto_id',
        'presentacion_id',
        'almacen_id',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'float',
    ];

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

    public function kardex()
    {
        return $this->hasMany(Kardex::class, 'inventario_id');
    }

    public function caducidades()
    {
        return $this->hasMany(InventarioCaducidad::class, 'inventario_id');
    }
}
