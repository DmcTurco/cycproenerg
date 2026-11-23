<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Documento subido a mano para una solicitud de Control General. */
class ControlGeneralArchivo extends Model
{
    protected $table = 'control_general_archivos';

    protected $fillable = ['control_general_id', 'nombre', 'nombre_original', 'ruta', 'mime', 'tamano', 'subido_por'];

    protected static function booted(): void
    {
        static::deleted(fn (self $a) => Storage::disk('local')->delete($a->ruta));
    }

    public function controlGeneral()
    {
        return $this->belongsTo(ControlGeneral::class);
    }

    /** Se puede mostrar dentro del modal (PDF o imagen). */
    public function esVisible(): bool
    {
        return $this->mime === 'application/pdf' || str_starts_with((string) $this->mime, 'image/');
    }
}
