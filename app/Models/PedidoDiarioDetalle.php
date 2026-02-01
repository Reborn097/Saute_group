<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductoPresentacion;

class PedidoDiarioDetalle extends Model
{
    use HasFactory;

    protected $table = 'pedido_diario_detalles';

    protected $fillable = [
        'pedido_diario_id',
        'fecha',
        'producto_id',
        'presentacion_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    /* =========================
     * Relaciones
     * ========================= */

    public function pedido()
    {
        return $this->belongsTo(PedidoDiario::class, 'pedido_diario_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function presentacion()
    {
        return $this->belongsTo(ProductoPresentacion::class, 'presentacion_id');
    }

    /* =========================
     * Boot (auto-cálculo)
     * ========================= */

    protected static function booted()
    {
        static::saving(function ($detalle) {
            $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;
        });
    }
}
