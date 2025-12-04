<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoEspecial extends Model
{
    protected $table = 'pedidos_especiales';

    protected $primaryKey = 'id_pedido_especial';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_pedido_especial',
        'solicitud',
        'cotizacion',
        'autorizacion',
        'codigo'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'codigo', 'codigo');
    }
}
