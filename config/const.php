<?php

return [
    'tipo_documeto' => [

        ['id' => 1, 'name' => 'DNI'],
        ['id' => 2, 'name' => 'RUC'],
        ['id' => 3, 'name' => 'CE'],
    ],


    'cargo' => [
        ['id' => 1, 'name' => 'Supervisor'],
        ['id' => 2, 'name' => 'Empleado'],
    ],


    'tipo_estado' => [
        [
            'id' => 1,
            'name' => 'pendiente',
            'badge' => 'bg-green-100 text-green-700'
        ],
        [
            'id' => 2,
            'name' => 'asignado',
            'badge' => 'bg-brand-100 text-brand-700'
        ],
        [
            'id' => 3,
            'name' => 'Iniciado',
            'badge' => 'bg-amber-100 text-amber-700'
        ],
        [
            'id' => 4,
            'name' => 'finalizado',
            'badge' => 'bg-sky-100 text-sky-700'
        ],
        [
            'id' => 5,
            'name' => 'reasignado',
            'badge' => 'bg-amber-100 text-amber-700'
        ],
        [
            'id' => 6,
            'name' => 'cancelado',
            'badge' => 'bg-red-100 text-red-700'
        ],
        [
            'id' => 7,
            'name' => 'expirado',
            'badge' => 'bg-gray-100 text-gray-700'
        ]
    ],

    // Control Interno (migración de CONTROL INTERNAS - CYC CLB v5.4.xlsm).
    // Mapeo de "Categoría de proyecto" del portal a su código corto
    // (PARAM!B28:C31). Los plazos, feriados y el código CYC/CLB de cada
    // empresa NO van aquí: viven en las tablas parametros_control_internos,
    // feriados y empresas.codigo porque el negocio los ajusta seguido.
    'control_interno' => [
        'categorias' => [
            'Residencial' => 'RES',
            'Multifamiliar' => 'MULTI',
            'Comercio' => 'COM',
        ],
        'categoria_por_defecto' => 'RES',
    ],

];
