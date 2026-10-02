<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y su matriz de permisos (config/permisos.php). El rol
 * Administrador no se edita ni se elimina: tiene todo siempre.
 */
class RolController extends Controller
{
    public function index()
    {
        return view('employee.pages.roles.index', [
            'roles' => Role::where('guard_name', Permisos::GUARD)->withCount(['users', 'permissions'])->orderBy('name')->get(),
            'totalPermisos' => count(Permisos::todos()),
            'administrador' => config('permisos.rol_administrador'),
        ]);
    }

    public function create()
    {
        return $this->formulario(new Role(['guard_name' => Permisos::GUARD]));
    }

    public function edit(Role $rol)
    {
        abort_if($this->esAdministrador($rol), 403, 'El rol Administrador tiene todos los permisos y no se edita.');

        return $this->formulario($rol);
    }

    public function store(Request $request)
    {
        return $this->guardar($request, new Role(['guard_name' => Permisos::GUARD]));
    }

    public function update(Request $request, Role $rol)
    {
        abort_if($this->esAdministrador($rol), 403, 'El rol Administrador tiene todos los permisos y no se edita.');

        return $this->guardar($request, $rol);
    }

    public function destroy(Role $rol)
    {
        $error = match (true) {
            $this->esAdministrador($rol) => 'El rol Administrador no se puede eliminar.',
            $rol->users()->exists() => "El rol {$rol->name} tiene usuarios asignados: cámbieles el rol antes de eliminarlo.",
            default => null,
        };
        if ($error) {
            return back()->withErrors(['rol' => $error]);
        }

        $antes = $rol->permissions()->pluck('name')->all();
        $nombre = $rol->name;
        $rol->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Auditoria::registrar('eliminado', "Rol {$nombre}", null, ['rol' => $nombre, 'permisos' => $antes], null, 'Rol');

        return redirect()->route('employee.roles.index')->with('message', "Rol {$nombre} eliminado.");
    }

    private function formulario(Role $rol)
    {
        return view('employee.pages.roles.form', [
            'rol' => $rol,
            'modulos' => config('permisos.modulos'),
            'marcados' => old('permisos', $rol->exists ? $rol->permissions()->pluck('name')->all() : []),
        ]);
    }

    private function guardar(Request $request, Role $rol)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('roles', 'name')->where('guard_name', Permisos::GUARD)->ignore($rol->id),
                Rule::notIn([config('permisos.rol_administrador')]),
            ],
            'permisos' => 'array',
            'permisos.*' => ['string', Rule::in(Permisos::todos())],
        ], [
            'name.not_in' => 'Ese nombre está reservado para el rol de acceso total.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
        ], ['name' => 'nombre del rol']);

        $permisos = $data['permisos'] ?? [];

        // Sin "ver", las demás acciones del módulo no sirven (no se puede
        // abrir la pantalla): se marca "ver" automáticamente.
        foreach ($permisos as $permiso) {
            $ver = explode('.', $permiso)[0] . '.ver';
            if (in_array($ver, Permisos::todos(), true) && !in_array($ver, $permisos, true)) {
                $permisos[] = $ver;
            }
        }

        DB::transaction(function () use ($rol, $data, $permisos) {
            $nuevo = !$rol->exists;
            $antes = $nuevo ? [] : $rol->permissions()->pluck('name')->all();
            $nombreAntes = $rol->name;

            $rol->name = $data['name'];
            $rol->save();
            // Permisos que falten (si se agregaron módulos después del seeder).
            foreach ($permisos as $permiso) {
                Permission::firstOrCreate(['name' => $permiso, 'guard_name' => Permisos::GUARD]);
            }
            $rol->syncPermissions($permisos);

            Auditoria::registrar(
                $nuevo ? 'creado' : 'actualizado',
                "Rol {$rol->name}" . ($nuevo ? '' : ($nombreAntes !== $rol->name ? " (antes {$nombreAntes})" : '')),
                $rol,
                $nuevo ? null : ['nombre' => $nombreAntes, 'permisos_quitados' => array_values(array_diff($antes, $permisos))],
                ['nombre' => $rol->name, $nuevo ? 'permisos' : 'permisos_agregados' => array_values($nuevo ? $permisos : array_diff($permisos, $antes))],
                'Rol',
            );
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('employee.roles.index')->with('message', "Rol {$rol->name} guardado.");
    }

    private function esAdministrador(Role $rol): bool
    {
        return $rol->name === config('permisos.rol_administrador');
    }
}
