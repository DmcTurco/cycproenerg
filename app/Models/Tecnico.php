<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Tecnico extends Authenticatable
{
    use HasApiTokens;
    use HasFactory, SoftDeletes;

    protected $table = 'tecnicos';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'tipo_documento',
        'numero_documento_identificacion',
        'cargo',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function solicitudes()
    {
        return $this->belongsToMany(Solicitud::class, 'solicitud_tecnico', 'tecnico_id', 'solicitud_id')
            ->withTimestamps()
            ->whereNull('solicitud_tecnico.deleted_at');
    }
}
