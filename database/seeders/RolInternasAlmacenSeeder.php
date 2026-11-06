<?php

namespace Database\Seeders;

use App\Support\Permisos;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rol de quien trabaja Internas, TC, Habilitación y Almacén (la usuaria del
 * Excel "Control General CLB - C&C"): Control General, clientes (ver y cargar
 * el Excel del portal) y Materiales sin eliminar, sin parámetros y sin cierre
 * de mes.
 *
 * Este seeder es la fuente de verdad del rol: al volver a correrlo, el rol
 * queda exactamente con estos permisos. Va después de RolesPermisosSeeder
 * (que crea los permisos).
 *
 *   php artisan db:seed --class=RolInternasAlmacenSeeder
 */
class RolInternasAlmacenSeeder extends Seeder
{
    public const ROL = 'Internas, TC, Habilitación y Almacén';

    public const PERMISOS = [
        'clientes.ver', 'clientes.cargar_excel',
        'control_general.ver', 'control_general.editar',
        'catalogo.ver',
        'ingresos.ver', 'ingresos.crear', 'ingresos.editar',
        'cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar', 'cotizaciones.descontar',
        'ejecutados.ver', 'ejecutados.crear',
        'entregas.ver', 'entregas.crear', 'entregas.editar',
        'reportes_materiales.ver',
        'inventario.ver', 'inventario.crear',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = collect(self::PERMISOS)->map(
            fn (string $nombre) => Permission::firstOrCreate(['name' => $nombre, 'guard_name' => Permisos::GUARD])
        );

        Role::firstOrCreate(['name' => self::ROL, 'guard_name' => Permisos::GUARD])->syncPermissions($permisos);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
