<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlKilometraje extends Model
{
    protected $table = 'control_kilometraje';

    protected $fillable = [
        'unidad_operativa_id',
        'fecha',
        'km_inicio',
        'km_final',
        'km_recorridos',
        'diesel_inicio_pct',
        'diesel_final_pct',
        'lugares_visitados',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'km_inicio' => 'integer',
        'km_final' => 'integer',
        'km_recorridos' => 'integer',
        'diesel_inicio_pct' => 'decimal:2',
        'diesel_final_pct' => 'decimal:2',
    ];

    public function unidad()
    {
        return $this->belongsTo(UnidadOperativa::class, 'unidad_operativa_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
