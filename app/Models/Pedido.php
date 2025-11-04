<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'fecha_solicitud',
        'fecha_entrega',
        'estado',
        'user_id',
        'total'
    ];

    /**
     * Generar código único tipo 'feb02250001'
     */
    public static function generarCodigo()
    {
        $fecha = Carbon::now();

        // Mes abreviado (3 letras minúsculas)
        $mes = strtolower($fecha->format('M')); // ene, feb, mar...

        // Semana del mes (1-4)
        $dia = $fecha->day;
        $semana = str_pad(ceil($dia / 7), 2, '0', STR_PAD_LEFT);

        // Últimos 2 dígitos del año
        $anio = substr($fecha->year, -2);

        // Buscar último código del mes y año actual
        $ultimo = self::where('codigo', 'like', "{$mes}{$semana}{$anio}%")
            ->orderBy('id', 'desc')
            ->first();

        $incremento = 1;
        if ($ultimo) {
            $ultimoNumero = intval(substr($ultimo->codigo, -4));
            $incremento = $ultimoNumero + 1;
        }

        $numero = str_pad($incremento, 4, '0', STR_PAD_LEFT);

        return "{$mes}{$semana}{$anio}{$numero}";
    }
}
