<?php

/**
 * Documentos de habilitación de cada solicitud (Control General, etapa
 * Habilitación). Reemplazan a los libros "ACTAS DE HABILITACION *.xlsm" y a las
 * plantillas Word "DECLARACION JURADA …". Cada uno es una vista PDF en
 * resources/views/employee/pages/control-general/documentos/pdf/.
 *
 * `campos` dice qué datos manuales (los que el sistema todavía no tiene) pide
 * el formulario de cada documento; se guardan en control_generales.datos_documentos.
 */
return [

    'documentos' => [
        'acta' => [
            'titulo' => 'Acta de habilitación (F-GEA-007)',
            'vista' => 'acta',
            'archivo' => 'Acta de habilitacion',
            'campos' => ['n_instalacion', 'material', 'puntos'],
        ],
        'dj_capacitacion' => [
            'titulo' => 'DJ de capacitación y tenencia del gasodoméstico',
            'vista' => 'dj-capacitacion',
            'archivo' => 'DJ capacitacion y tenencia',
            'campos' => ['fecha'],
        ],
        'dj_normas' => [
            'titulo' => 'DJ de cumplimiento de normas técnicas y de seguridad',
            'vista' => 'dj-normas',
            'archivo' => 'DJ normas tecnicas',
            'campos' => ['fecha', 'lugar'],
        ],
        'certificado' => [
            'titulo' => 'Certificado de garantías de la instalación interna',
            'vista' => 'certificado',
            'archivo' => 'Certificado de garantias',
            'campos' => ['n_certificado', 'fecha', 'material', 'tipo', 'potencia_nominal', 'items'],
        ],
        'anexos' => [
            'titulo' => 'Anexo: diseño típico',
            'vista' => 'anexos',
            'archivo' => 'Anexo diseno tipico',
            'campos' => ['anexos'],
        ],
    ],

    // Páginas de diseño típico (resources/documentos/anexos). Se eligen en el formulario.
    'anexos' => [
        'residencial_a1' => ['titulo' => 'Residencial A.1 — 1 punto (hasta 12 kWh)', 'archivo' => 'residencial_a1.png'],
        'residencial_b1' => ['titulo' => 'Residencial B.1 — 2 puntos', 'archivo' => 'residencial_b1.png'],
        'comercio_1p' => ['titulo' => 'Comercio CU-LI-1P — 1 punto', 'archivo' => 'comercio_1p.png'],
        'comercio_2p' => ['titulo' => 'Comercio CU-LI-2P — 2 puntos', 'archivo' => 'comercio_2p.png'],
        'comercio_3a' => ['titulo' => 'Comercio RM-LI-3A — 3 puntos', 'archivo' => 'comercio_3a.png'],
    ],

    'materiales' => ['PEALPE', 'COBRE', 'ACERO'],
    'tipos_instalacion' => ['A LA VISTA', 'EMPOTRADO', 'PROY'],
    'tipos' => ['A', 'B', 'C'],

    // Filas de las tablas (como los formatos).
    'filas_puntos' => 5,
    'filas_items' => 5,
];
