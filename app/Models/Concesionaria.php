<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Concesionaria extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'concesionarias';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
    ];

}
