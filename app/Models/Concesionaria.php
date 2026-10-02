<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Concesionaria extends Model
{
    use Auditable;

    protected string $auditModuloNombre = 'Concesionaria';

    use HasFactory;
    use SoftDeletes;

    protected $table = 'concesionarias';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
    ];

}
