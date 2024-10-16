<?php
namespace App\Helpers;

class TipoDocumentoHelper
{
    public static function obtenerTipoDocumento($valorDocumento)
    {
        $tipos_documento = config('const.tipo_documeto');
        
        $tipo_documento = collect($tipos_documento)->first(function ($tipo) use ($valorDocumento) {
            return $tipo['name'] === trim($valorDocumento);
        });
        
        return $tipo_documento ? $tipo_documento['id'] : null;
    }
}