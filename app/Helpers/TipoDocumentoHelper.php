<?php
namespace App\Helpers;

class TipoDocumentoHelper
{
    private static $tiposDocumento = null;
    private static $tiposCargo = null;

    private static function getTiposDocumento()
    {
        if (self::$tiposDocumento === null) {
            self::$tiposDocumento = collect(config('const.tipo_documeto'));
        }
        return self::$tiposDocumento;
    }

    private static function getTipoCargo(){
        if (self::$tiposCargo === null) {
            self::$tiposCargo = collect(config('const.cargo'));
        }
        return self::$tiposCargo;
    }

    public static function getTypeDocument($valorDocumento)
    {
        if (empty($valorDocumento)) return null;
        
        return self::getTiposDocumento()
            ->firstWhere('name', trim($valorDocumento))['id'] ?? null;
    }

    public static function getTypeDocumentName($id)
    {
        if (empty($id)) return 'N/A';
        
        return self::getTiposDocumento()
            ->firstWhere('id', $id)['name'] ?? 'N/A';
    }

    public static function getTypeCargoName($id)
    {
        if (empty($id)) return 'N/A';
        
        return self::getTipoCargo()
            ->firstWhere('id', $id)['name'] ?? 'N/A';
    }
}