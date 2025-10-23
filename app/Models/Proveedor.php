<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'telefono',
        'calle',
        'colonia',
        'codigo_postal',
        'num_direccion',
        'rfc',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_proveedor', 'proveedor_id', 'producto_id')
                    ->withPivot(['precio', 'fecha_vigencia_inicio', 'fecha_vigencia_final', 'estado']);
    }

}
