<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoDiario extends Model
{
    use HasFactory;

    protected $table = 'pedidos_diarios';

    protected $fillable = [
        'codigo',
        'unidad_operativa_id',
        'tipo', // PAN | TORTILLA
        'semana_inicio',
        'semana_fin',
        'estado',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin'    => 'date',
    ];

    /* =========================
     * Relaciones
     * ========================= */

    public function detalles()
    {
        return $this->hasMany(PedidoDiarioDetalle::class, 'pedido_diario_id');
    }

    public function unidadOperativa()
    {
        return $this->belongsTo(UnidadOperativa::class, 'unidad_operativa_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* =========================
     * Helpers útiles
     * ========================= */

    public function esPan(): bool
    {
        return $this->tipo === 'PAN';
    }

    public function esTortilla(): bool
    {
        return $this->tipo === 'TORTILLA';
    }

    public function getTotalAttribute(): float
    {
        // Si ya viene cargada la relación, suma en memoria.
        if ($this->relationLoaded('detalles')) {
            return (float) $this->detalles->sum('subtotal');
        }

        // Si no, suma por query.
        return (float) $this->detalles()->sum('subtotal');
    }

}
