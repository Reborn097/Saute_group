<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_RESPONSABLE_UNIDADES = 'responsable_de_unidades';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
    'name',
    'email',
    'username',          // ✅ AÑADIR
    'password',
    'role',
    'unidad_operativa_id',
    'proveedor_id'
];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadOperativa::class, 'unidad_operativa_id');
    }

    public function unidadesAsignadas(): BelongsToMany
    {
        return $this->belongsToMany(
            UnidadOperativa::class,
            'unidad_operativa_user',
            'user_id',
            'unidad_operativa_id'
        )->withTimestamps();
    }

    public function esResponsableDeUnidades(): bool
    {
        return (string)($this->role ?? '') === self::ROLE_RESPONSABLE_UNIDADES;
    }

    public function unidadOperativaIdsAsignadas(): array
    {
        if (!$this->esResponsableDeUnidades()) {
            return [];
        }

        return $this->unidadesAsignadas()
            ->pluck('unidades_operativas.id')
            ->map(fn ($id) => (int)$id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    public function puedeAccederUnidadOperativa(int $unidadId): bool
    {
        if ($unidadId <= 0) {
            return false;
        }

        if ((string)($this->role ?? '') === 'admin') {
            return true;
        }

        if ($this->esResponsableDeUnidades()) {
            return in_array($unidadId, $this->unidadOperativaIdsAsignadas(), true);
        }

        return (int)($this->unidad_operativa_id ?? 0) === $unidadId;
    }

    public function proveedor()
{
    return $this->belongsTo(\App\Models\Proveedor::class, 'proveedor_id');
}



}
