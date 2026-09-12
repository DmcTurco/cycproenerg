<x-sidebar :links="[
    ['route' => 'employee.home', 'label' => 'Mi Perfil', 'match' => 'employee/home'],
    ['route' => 'employee.client.index', 'label' => 'Gestión de Clientes', 'match' => 'employee/client*'],
    ['route' => 'employee.technicals.index', 'label' => 'Gestión de Técnicos', 'match' => 'employee/technicals*'],
    ['route' => 'employee.advisers.index', 'label' => 'Asesores', 'match' => 'employee/advisers*'],
    ['route' => 'employee.control-interno.index', 'label' => 'Control Interno', 'match' => ['employee/control-interno*', 'employee/solicitudes/*/control-interno']],
]" />
