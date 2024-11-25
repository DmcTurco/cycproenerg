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
            'badge' => 'bg-gradient-success'
        ],
        [
            'id' => 2,
            'name' => 'asignado',
            'badge' => 'bg-gradient-primary'
        ],
        [
            'id' => 3,
            'name' => 'Iniciado',
            'badge' => 'bg-gradient-warning '
        ],
        [
            'id' => 4,
            'name' => 'finalizado',
            'badge' => 'bg-gradient-info'
        ],
        [
            'id' => 5,
            'name' => 'reasignado',
            'badge' => 'bg-gradient-warning'
        ],
        [
            'id' => 6,
            'name' => 'cancelado',
            'badge' => 'bg-gradient-danger'
        ],
        [
            'id' => 7,
            'name' => 'expirado',
            'badge' => 'bg-gradient-secondary'
        ]
    ]

];
