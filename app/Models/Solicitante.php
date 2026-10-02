<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Solicitante extends Model
{
    use Auditable;

    protected string $auditModuloNombre = 'Solicitante';

    use HasFactory;
    use SoftDeletes;

    protected $table = 'solicitantes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
        'celular',
        'correo_electronico',
        'usuario_fise',
    ];

}
