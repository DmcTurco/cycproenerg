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
        'dni',
        'cargo',
    ];

    public function solicitudes() {
        return $this->Hasmany(SolicitudTecnico::class);
    }

    public function numeroSolicitudes() {
        return $this->solicitudes()->count();
    }
}
