<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tecnico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tecnicos';

    protected $fillable = [
        'company_id',
        'nombre',
        'tipo_documento',
        'numero_documento_identificacion',
        'cargo',
    ];

    public function solicitudes() {
        return $this->Hasmany(SolicitanteTecnico::class);
    }

    public function numeroSolicitudes() {
        return $this->solicitudes()->count();
    }
}
