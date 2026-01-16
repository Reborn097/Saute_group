<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComensalRegistro extends Model
{
    protected $table = 'comensales_registros';

    protected $fillable = [
        'unidad_operativa_id',
        'fecha',
        'cantidad',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
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
