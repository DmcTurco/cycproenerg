<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estado extends Model
{
    use HasFactory;    
    use SoftDeletes;
    
    protected $table = 'estados';
    protected $fillable = ['codigo', 'nombre', 'abreviatura'];

    public function solicitudes() {
        return $this->hasMany(Solicitud::class);
    }

}
