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
    // (PARAM!B28:C31). Los plazos y feriados NO van aquí: viven en las
    // tablas parametros_control_internos y feriados porque el negocio los
    // ajusta seguido.
    'control_interno' => [
        'categorias' => [
            'Residencial' => 'RES',
            'Multifamiliar' => 'MULTI',
            'Comercio' => 'COM',
        ],
        'categoria_por_defecto' => 'RES',

        // Código corto de empresa (CYC/CLB), igual que PARAM!B6:B7 del Excel.
        // Turco hizo notar (13/09/2026) que tener esto en un seeder aparte
        // era redundante: la carga del Excel ya identifica a cada Empresa
        // por RUC, así que ProcessExcelJob::asignarCodigoEmpresa() usa este
        // mapeo para completar el código en el momento, en vez de depender
        // de correr un comando aparte (y en el orden correcto). Si se suma
        // una empresa nueva al sistema, agregar su RUC acá.
        'codigos_empresa_por_ruc' => [
            '20604329397' => 'CYC', // C&C PROYECTOS INTEGRALES EN ENERGÍA S.A.C.
            '20610320032' => 'CLB', // CLB INGENIERIA Y PROYECTOS EN ENERGIA SOSTENIBLE S.A.C.
        ],
    ],

];
