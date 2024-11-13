<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoPortal extends Model
{
    use HasFactory;    
    use SoftDeletes;
    
    protected $table = 'estado_portals';
    protected $fillable = ['codigo', 'nombre', 'abreviatura'];

    public function solicitudes() {
        return $this->hasMany(Solicitud::class);
    }

}
