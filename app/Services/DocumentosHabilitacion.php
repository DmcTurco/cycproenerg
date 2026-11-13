<?php

namespace App\Services;

use App\Models\ControlGeneral;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

/**
 * Documentos de habilitación de una solicitud (ver config/documentos_habilitacion.php):
 * arma los datos que comparten todos (cliente, ubicación, instalador, empresa
 * instaladora), mezcla con lo que se llenó a mano (control_generales.datos_documentos)
 * y genera cada uno en PDF, o todos juntos en un ZIP.
 */
class DocumentosHabilitacion
{
    public function catalogo(): array
    {
        return config('documentos_habilitacion.documentos');
    }

    public function existe(string $clave): bool
    {
        return array_key_exists($clave, $this->catalogo());
    }

    /**
     * Lo que usan las vistas PDF. Lo guardado a mano se completa con valores
     * por defecto tomados de la solicitud (p. ej. las filas según los puntos).
     */
    public function contexto(ControlGeneral $cg): array
    {
        $cg->loadMissing([
            'tecnico',
            'solicitud.solicitante', 'solicitud.ubicacion', 'solicitud.proyecto',
            'solicitud.instalacion', 'solicitud.empresa',
        ]);

        $s = $cg->solicitud;
        abort_unless($s, 404);

        $direccion = trim((string) $s->ubicacion?->direccion);
        // Igual que el Excel: el piso es el carácter que sigue a "PISO " y el
        // departamento el último de la dirección (solo si hay "piso").
        $piso = $dpto = '';
        if (preg_match('/piso\s+(\S)/iu', $direccion, $m)) {
            $piso = $m[1];
            $dpto = mb_substr($direccion, -1);
        }

        $cliente = $s->solicitante;
        $documento = trim((string) $cliente?->numero_documento);
        $tipoInstalacion = mb_strtolower((string) $s->instalacion?->tipo_instalacion);
        $puntos = (int) $s->instalacion?->numero_puntos_instalacion;
        $empresaModelo = $s->empresa;

        return [
            'cg' => $cg,
            's' => $s,
            'cliente' => $cliente,
            'documento' => $documento,
            // Un RUC (11 dígitos) es persona jurídica: va en Razón social / R.U.C.
            'esRuc' => strlen($documento) === 11,
            'ubi' => $s->ubicacion,
            'direccion' => $direccion,
            'piso' => $piso,
            'dpto' => $dpto,
            'instalador' => $cg->tecnico,
            'empresaModelo' => $empresaModelo,
            'empresa' => $this->empresa($empresaModelo),
            'esComercial' => $s->proyecto?->categoria_proyecto === 'Comercio',
            'aLaVista' => str_contains($tipoInstalacion, 'vista'),
            'empotrada' => str_contains($tipoInstalacion, 'empotrad'),
            'puntos' => $puntos,
            'datos' => $this->datos($cg, $puntos, $tipoInstalacion),
            'logo' => $empresaModelo?->rutaArchivo('logo'),
            'sello' => $empresaModelo?->rutaArchivo('sello'),
            'cfg' => config('documentos_habilitacion'),
        ];
    }

    private function empresa($e): array
    {
        return [
            'razon_social' => $e?->nombre,
            'ruc' => $e?->numero_documento,
            'registro' => $e?->registro_gas_natural,
            'categoria' => $e?->categoria,
            'direccion' => $e?->direccion,
            'distrito' => $e?->distrito,
            'provincia' => $e?->provincia,
            'departamento' => $e?->departamento,
            'telefono' => $e?->telefono,
            'correo' => $e?->correo,
            'poliza' => $e?->numero_poliza,
            'representante' => $e?->representante_legal,
        ];
    }

    /** Lo guardado a mano más los valores por defecto. */
    public function datos(ControlGeneral $cg, ?int $puntos = null, string $tipoInstalacion = ''): array
    {
        $guardado = (array) $cg->datos_documentos;
        $filasPuntos = (int) config('documentos_habilitacion.filas_puntos');
        $filasItems = (int) config('documentos_habilitacion.filas_items');

        if ($puntos === null) {
            $cg->loadMissing('solicitud.instalacion');
            $puntos = (int) $cg->solicitud?->instalacion?->numero_puntos_instalacion;
            $tipoInstalacion = mb_strtolower((string) $cg->solicitud?->instalacion?->tipo_instalacion);
        }

        $instDefecto = str_contains($tipoInstalacion, 'empotrad') ? 'EMPOTRADO' : (str_contains($tipoInstalacion, 'vista') ? 'A LA VISTA' : '');
        $items = $guardado['items'] ?? collect(range(1, min(max($puntos, 0), $filasItems)))
            ->map(fn () => ['inst' => $instDefecto, 'tipo' => 'A', 'potencia' => ''])->all();

        return [
            'fecha' => $guardado['fecha'] ?? null,
            'lugar' => $guardado['lugar'] ?? null,
            'n_certificado' => $guardado['n_certificado'] ?? null,
            'n_instalacion' => $guardado['n_instalacion'] ?? null,
            'material' => $guardado['material'] ?? 'PEALPE',
            'tipo' => $guardado['tipo'] ?? 'A',
            'potencia_nominal' => $guardado['potencia_nominal'] ?? null,
            'items' => array_values($items),
            'puntos' => array_values($guardado['puntos'] ?? []),
            'anexos' => array_values($guardado['anexos'] ?? []),
        ];
    }

    /**
     * Datos del sistema que faltan para que el documento salga completo
     * (se avisan en la pantalla, para que se carguen donde corresponde).
     */
    public function faltantes(array $ctx): array
    {
        $falta = [];

        if (!$ctx['instalador']) {
            $falta[] = 'No hay técnico elegido en Control General: el instalador sale en blanco.';
        } else {
            $sin = collect(['numero_documento' => 'DNI', 'numero_registro' => 'N° de registro', 'categoria' => 'categoría'])
                ->filter(fn ($_, $campo) => blank($ctx['instalador']->{$campo}))->values();
            if ($sin->isNotEmpty()) {
                $falta[] = 'Al instalador ' . $ctx['instalador']->nombre . ' le falta: ' . $sin->implode(', ') . ' (Personal de campo).';
            }
        }

        if (!$ctx['empresaModelo']) {
            $falta[] = 'La solicitud no tiene empresa instaladora.';
        } else {
            $e = $ctx['empresaModelo'];
            $sin = collect(['direccion' => 'dirección', 'categoria' => 'categoría', 'telefono' => 'teléfono', 'correo' => 'correo', 'numero_poliza' => 'N° de póliza'])
                ->filter(fn ($_, $campo) => blank($e->{$campo}))->values();
            if ($sin->isNotEmpty()) {
                $falta[] = 'A la empresa ' . ($e->codigo ?: $e->nombre) . ' le falta: ' . $sin->implode(', ') . ' (Empresas).';
            }
            if (!$ctx['logo']) {
                $falta[] = 'La empresa no tiene logo (Empresas).';
            }
            if (!$ctx['sello']) {
                $falta[] = 'La empresa no tiene firma y sello del representante legal (Empresas): el certificado sale sin sello.';
            }
        }

        return $falta;
    }

    /** PDF (contenido binario) de un documento. */
    public function pdf(string $clave, ControlGeneral $cg): string
    {
        $doc = $this->catalogo()[$clave];

        return Pdf::loadView('employee.pages.control-general.documentos.pdf.' . $doc['vista'], $this->contexto($cg))
            ->setPaper('a4')
            ->output();
    }

    public function nombreArchivo(string $clave, ControlGeneral $cg): string
    {
        $s = $cg->solicitud ?? $cg->load('solicitud')->solicitud;
        $codigo = $s?->numero_suministro ?: $s?->numero_solicitud;

        return $this->catalogo()[$clave]['archivo'] . ' ' . $codigo . '.pdf';
    }

    /**
     * ZIP con todos los documentos (el de anexos solo si se eligió algún
     * diseño). Devuelve la ruta del archivo temporal.
     */
    public function zip(ControlGeneral $cg): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'docs');
        $zip = new ZipArchive();
        $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $orden = 0;
        foreach (array_keys($this->catalogo()) as $clave) {
            if ($clave === 'anexos' && empty($this->datos($cg)['anexos'])) {
                continue;
            }
            $orden++;
            $zip->addFromString($orden . ' - ' . $this->nombreArchivo($clave, $cg), $this->pdf($clave, $cg));
        }

        $zip->close();

        return $ruta;
    }
}
