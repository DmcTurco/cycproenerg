<?php

/*
|--------------------------------------------------------------------------
| Roles y permisos del panel de empleados
|--------------------------------------------------------------------------
|
| Fuente única de verdad:
|   - "modulos": lo que se ve en la matriz de la pantalla Roles. Cada
|     permiso se llama "modulo.accion" (ej. "ingresos.crear").
|   - "rutas": qué permiso exige cada ruta (patrón de nombre de ruta, se
|     evalúa en orden y gana la PRIMERA coincidencia). Lo aplica el
|     middleware App\Http\Middleware\VerificarPermiso a todo el panel, y lo
|     usa el menú lateral para ocultar lo que el usuario no puede abrir.
|     Las rutas que no aparecen acá (Mi Perfil) quedan libres para todos.
|   - "{modulo}.guardar": los formularios en modal usan un solo endpoint
|     "store" para crear y editar; el middleware lo traduce a
|     "{modulo}.editar" si el request trae id, o "{modulo}.crear" si no.
|
| El rol "Administrador" tiene todo siempre (ver AppServiceProvider).
|
*/

return [

    'rol_administrador' => 'Administrador',

    'modulos' => [
        'clientes' => ['label' => 'Clientes (solicitudes)', 'acciones' => ['ver' => 'Ver', 'cargar_excel' => 'Cargar Excel del portal']],
        'personal' => ['label' => 'Personal de campo', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar', 'asignar' => 'Asignar solicitudes']],
        'asesores' => ['label' => 'Asesores', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'control_interno' => ['label' => 'Control Interno', 'acciones' => ['ver' => 'Ver', 'editar' => 'Editar datos manuales', 'parametros' => 'Parámetros y feriados']],
        'catalogo' => ['label' => 'Materiales: catálogo', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar', 'parametros' => 'Parámetros']],
        'ingresos' => ['label' => 'Materiales: ingresos', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'cotizaciones' => ['label' => 'Materiales: cotizaciones y vales', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar', 'descontar' => 'Marcar descontado']],
        'ejecutados' => ['label' => 'Materiales: ejecutado', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'eliminar' => 'Eliminar']],
        'entregas' => ['label' => 'Materiales: entregas de herramientas', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'reportes_materiales' => ['label' => 'Materiales: resumen e imprimibles', 'acciones' => ['ver' => 'Ver']],
        'inventario' => ['label' => 'Materiales: inventario físico', 'acciones' => ['ver' => 'Ver', 'crear' => 'Crear', 'eliminar' => 'Eliminar']],
        'cierres' => ['label' => 'Materiales: cierre de mes', 'acciones' => ['ver' => 'Ver', 'cerrar' => 'Cerrar el mes']],
        'auditoria' => ['label' => 'Auditoría', 'acciones' => ['ver' => 'Ver']],
        'usuarios' => ['label' => 'Usuarios y roles', 'acciones' => ['gestionar' => 'Administrar usuarios y roles']],
    ],

    'rutas' => [
        // Clientes / solicitudes
        'employee.client.excel' => 'clientes.cargar_excel',
        'employee.change' => 'clientes.cargar_excel',
        'employee.check-progress' => 'clientes.cargar_excel',
        'employee.client.*' => 'clientes.ver',
        'employee.getFullSolicitudDetails' => 'clientes.ver',
        'employee.solicitudes.detalle' => 'clientes.ver',

        // Personal de campo
        'employee.technicals.requests.*' => 'personal.asignar',
        'employee.technicals.record.index' => 'personal.ver',
        'employee.technicals.record.show' => 'personal.ver',
        'employee.technicals.record.*' => 'personal.editar',
        'employee.technicals.store' => 'personal.guardar',
        'employee.technicals.destroy' => 'personal.eliminar',
        'employee.technicals.*' => 'personal.ver',

        // Asesores
        'employee.advisers.store' => 'asesores.guardar',
        'employee.advisers.create' => 'asesores.crear',
        'employee.advisers.update' => 'asesores.editar',
        'employee.advisers.destroy' => 'asesores.eliminar',
        'employee.advisers.*' => 'asesores.ver',

        // Control Interno
        'employee.control-interno.solicitudes.manual.update' => 'control_interno.editar',
        'employee.control-interno.parametros.*' => 'control_interno.parametros',
        'employee.control-interno.feriados.*' => 'control_interno.parametros',
        'employee.control-interno.*' => 'control_interno.ver',
        'employee.solicitudes.control-interno' => 'control_interno.ver',

        // Materiales: catálogo
        'employee.materiales.parametros.*' => 'catalogo.parametros',
        'employee.materiales.items.store' => 'catalogo.guardar',
        'employee.materiales.items.create' => 'catalogo.crear',
        'employee.materiales.items.plantilla' => 'catalogo.crear',
        'employee.materiales.items.importar' => 'catalogo.crear',
        'employee.materiales.items.destroy' => 'catalogo.eliminar',
        'employee.materiales.herramientas.store' => 'catalogo.guardar',
        'employee.materiales.herramientas.destroy' => 'catalogo.eliminar',
        'employee.materiales.items.*' => 'catalogo.ver',
        'employee.materiales.herramientas.*' => 'catalogo.ver',
        'employee.materiales.index' => 'catalogo.ver',

        // Materiales: ingresos
        'employee.materiales.ingresos.store' => 'ingresos.guardar',
        'employee.materiales.ingresos.destroy' => 'ingresos.eliminar',
        'employee.materiales.ingresos.*' => 'ingresos.ver',

        // Materiales: cotizaciones y vales
        'employee.materiales.cotizaciones.create' => 'cotizaciones.crear',
        'employee.materiales.cotizaciones.store' => 'cotizaciones.crear',
        'employee.materiales.cotizaciones.edit' => 'cotizaciones.editar',
        'employee.materiales.cotizaciones.update' => 'cotizaciones.editar',
        'employee.materiales.cotizaciones.descontar' => 'cotizaciones.descontar',
        'employee.materiales.resumen.cerrar' => 'cotizaciones.descontar',
        'employee.materiales.cotizaciones.destroy' => 'cotizaciones.eliminar',
        'employee.materiales.cotizaciones.*' => 'cotizaciones.ver',

        // Materiales: ejecutado
        'employee.materiales.ejecutados.create' => 'ejecutados.crear',
        'employee.materiales.ejecutados.store' => 'ejecutados.crear',
        'employee.materiales.ejecutados.destroy' => 'ejecutados.eliminar',
        'employee.materiales.ejecutados.*' => 'ejecutados.ver',

        // Materiales: entregas de herramientas
        'employee.materiales.entregas.store' => 'entregas.guardar',
        'employee.materiales.entregas.destroy' => 'entregas.eliminar',
        'employee.materiales.entregas.*' => 'entregas.ver',

        // Materiales: resumen e imprimibles
        'employee.materiales.resumen.*' => 'reportes_materiales.ver',
        'employee.materiales.imprimibles.*' => 'reportes_materiales.ver',

        // Materiales: inventario físico
        'employee.materiales.inventario-fisico.create' => 'inventario.crear',
        'employee.materiales.inventario-fisico.store' => 'inventario.crear',
        'employee.materiales.inventario-fisico.destroy' => 'inventario.eliminar',
        'employee.materiales.inventario-fisico.*' => 'inventario.ver',

        // Materiales: cierre de mes
        'employee.materiales.cierres.store' => 'cierres.cerrar',
        'employee.materiales.cierres.*' => 'cierres.ver',

        // Auditoría, usuarios y roles
        'employee.auditoria.*' => 'auditoria.ver',
        'employee.usuarios.*' => 'usuarios.gestionar',
        'employee.roles.*' => 'usuarios.gestionar',
    ],
];
