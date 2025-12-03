<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadOperativa extends Model
{
    protected $table = 'unidades_operativas';

    protected $fillable = [
        'nombre',
        'tipo',
        'ubicacion',
        'responsable_id',
    ];

    // Relación con el encargado
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    // Relación con almacenes
    public function almacenes()
    {
        return $this->hasMany(Almacen::class, 'unidad_id');
    }

    // Relación con pedidos
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'unidad_id');
    }
}

