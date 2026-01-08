<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'proveedores';

    // Campos que pueden asignarse masivamente
    protected $fillable = [
        'nombre',
        'nombre_contacto',
        'telefono_contacto',
        'telefono',
        'calle',
        'colonia',
        'codigo_postal',
        'num_direccion',
        'rfc'
    ];

    /**
     * Relación muchos a muchos con Producto (tabla pivote producto_proveedor)
     */
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_proveedor', 'proveedor_id', 'producto_id')
                    ->withPivot('precio', 'fecha_vigencia_inicio', 'fecha_vigencia_final', 'estado')
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
