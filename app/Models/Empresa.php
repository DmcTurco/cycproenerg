<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use Auditable;

    protected string $auditModuloNombre = 'Empresa';

    use HasFactory;
    use SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'codigo',
        'nombre',
        'registro_gas_natural',
    ];

}
