<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitanteTecnico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'solicitante_tecnico';

    protected $fillable = [
        'tecnico_id',
        'solicitante_id',
    ];

}
