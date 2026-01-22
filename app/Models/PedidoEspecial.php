<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoEspecial extends Model
{
    protected $table = 'pedidos_especiales';

    protected $primaryKey = 'id_pedido_especial';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'solicitud',
        'cotizacion',
        'autorizacion',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'codigo', 'codigo');
    }
}

