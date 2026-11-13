<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'direccion',
        'distrito',
        'provincia',
        'departamento',
        'categoria',
        'telefono',
        'correo',
        'numero_poliza',
        'representante_legal',
        'logo',
        'sello',
    ];

    /**
     * Ruta en disco del logo o del sello (firma y sello del representante
     * legal), o null si no hay o el archivo ya no existe.
     */
    public function rutaArchivo(string $campo): ?string
    {
        $ruta = $this->{$campo};

        return $ruta && Storage::disk('public')->exists($ruta) ? Storage::disk('public')->path($ruta) : null;
    }

}
