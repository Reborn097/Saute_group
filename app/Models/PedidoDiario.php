<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'observaciones_ceo',   // ✅ NUEVO
        'preaprobado_por',     // ✅ NUEVO
        'user_id',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin'    => 'date',
        'preaprobado_por' => 'integer', // ✅
        'user_id'         => 'integer', // ✅
        'unidad_operativa_id' => 'integer', // ✅
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

    // ✅ Quién preaprobó (admin/encargado_pedidos)
    public function preaprobadoPor()
    {
        return $this->belongsTo(User::class, 'preaprobado_por');
    }

 

    public static function generarCodigo(): string
    {
        $fecha = Carbon::now();

        $meses = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC',
        ];

        $mes  = $meses[(int)$fecha->month];
        $anio = substr((string)$fecha->year, -2);

        // ✅ solo diferencia: prefijo D
        $prefijo = 'D' . $mes . $anio; // DENE26

        $ultimo = self::where('codigo', 'like', $prefijo . '%')
            ->orderBy('codigo', 'desc')
            ->first();

        $siguiente = 1;

        if ($ultimo) {
            $ultimoNumero = (int) substr((string)$ultimo->codigo, -3);
            $siguiente = $ultimoNumero + 1;
        }

        $consecutivo = str_pad((string)$siguiente, 3, '0', STR_PAD_LEFT);

        return $prefijo . $consecutivo; // DENE26001
    }


    /* =========================
     * Helpers útiles
     * ========================= */

    public function esPan(): bool
    {
        return strtoupper((string)$this->tipo) === 'PAN';
    }

    public function esTortilla(): bool
    {
        return strtoupper((string)$this->tipo) === 'TORTILLA';
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
