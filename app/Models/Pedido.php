<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    // clave primaria = codigo
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'fecha_solicitud',
        'fecha_entrega',
        'estado',
        'user_id',
        'total',
        'es_especial', // 👈 IMPORTANTE
    ];

    protected $casts = [
        'es_especial' => 'boolean',
    ];

    public static function generarCodigo()
    {
        $fecha = Carbon::now();

        $mes = strtolower($fecha->format('M'));
        $dia = $fecha->day;
        $semana = str_pad(ceil($dia / 7), 2, '0', STR_PAD_LEFT);
        $anio = substr($fecha->year, -2);

        $ultimo = self::where('codigo', 'like', "{$mes}{$semana}{$anio}%")
            ->orderBy('codigo', 'desc')
            ->first();

        $incremento = 1;
        if ($ultimo) {
            $ultimoNumero = intval(substr($ultimo->codigo, -4));
            $incremento = $ultimoNumero + 1;
        }

        $numero = str_pad($incremento, 4, '0', STR_PAD_LEFT);

        return "{$mes}{$semana}{$anio}{$numero}";
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'codigo', 'codigo')
            ->with(['productoProveedor.producto.categoria', 'productoProveedor.proveedor']);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
