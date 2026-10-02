<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable
{
    use Auditable;

    protected string $auditModuloNombre = 'Usuario';

    use HasApiTokens, HasFactory, Notifiable;

    // Roles y permisos (spatie/laravel-permission) del guard "employee";
    // qué permiso exige cada pantalla/acción: config/permisos.php.
    use HasRoles;

    protected string $guard_name = 'employee';

    /**
     * El login (Fortify, lowercase_usernames) busca el correo en
     * minúsculas y PostgreSQL distingue mayúsculas: si se guardara
     * "Jlucero@…" nunca podría entrar. Se guarda siempre en minúsculas.
     */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = $value === null ? null : mb_strtolower(trim($value), 'UTF-8');
    }

    public function esAdministrador(): bool
    {
        return $this->hasRole(config('permisos.rol_administrador'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
