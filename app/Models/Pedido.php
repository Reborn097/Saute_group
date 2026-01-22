<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    // PK = codigo (string)
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'fecha_solicitud',
        'fecha_entrega',
        'estado',
        'user_id',

        // ✅ NUEVO: el pedido SIEMPRE debe saber para qué unidad es
        'unidad_operativa_id',

        'total',
        'es_especial',
        'preaprobado_por',
        'observaciones',
        'observaciones_ceo',
    ];

    protected $casts = [
        'es_especial' => 'boolean',
        'fecha_solicitud' => 'date',
        'fecha_entrega'   => 'date',
    ];

    /**
     * Si en algún punto usas Route Model Binding por código:
     * route('...', $pedido) -> buscará por 'codigo'
     */
    public function getRouteKeyName()
    {
        return 'codigo';
    }

    /**
     * Formato: ENE26001, ENE26002, ...
     * - Prefijo = MES(3 letras ES) + AA
     * - Consecutivo = 3 dígitos
     *
     * ⚠️ Nota: para evitar duplicados en concurrencia:
     * 1) Debe existir índice UNIQUE en pedidos.codigo (ideal)
     * 2) En el controller, si falla por duplicado, reintentar (lo veremos ahí)
     */
    public static function generarCodigo(): string
    {
        $fecha = Carbon::now();

        // Mes en español (3 letras)
        $meses = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC',
        ];

        $mes  = $meses[(int)$fecha->month];                 // ENE
        $anio = substr((string)$fecha->year, -2);           // 26

        $prefijo = $mes . $anio;                            // ENE26

        // Último pedido con ese prefijo (orden lexicográfico sirve por 3 dígitos)
        $ultimo = self::where('codigo', 'like', $prefijo . '%')
            ->orderBy('codigo', 'desc')
            ->first();

        $siguiente = 1;

        if ($ultimo) {
            $ultimoNumero = (int) substr((string)$ultimo->codigo, -3);
            $siguiente = $ultimoNumero + 1;
        }

        $consecutivo = str_pad((string)$siguiente, 3, '0', STR_PAD_LEFT);

        return $prefijo . $consecutivo; // ENE26001
    }

    // =========================
    // Relaciones
    // =========================

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'codigo', 'codigo')
            ->with(['productoProveedor.producto.categoria', 'productoProveedor.proveedor']);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function preaprobador()
    {
        return $this->belongsTo(User::class, 'preaprobado_por');
    }

    // ✅ NUEVO: unidad operativa del pedido
    public function unidadOperativa()
    {
        return $this->belongsTo(UnidadOperativa::class, 'unidad_operativa_id');
    }
}
