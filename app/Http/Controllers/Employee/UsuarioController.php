<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Employee;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * Usuarios del panel (empleados) y su rol. Mismo patrón crudModal que el
 * resto de catálogos: store crea o actualiza según venga o no el id.
 */
class UsuarioController extends Controller
{
    public function index()
    {
        return view('employee.pages.usuarios.index', [
            'usuarios' => Employee::with('roles')->orderBy('name')->get(),
            'roles' => Role::where('guard_name', Permisos::GUARD)->orderBy('name')->pluck('name'),
        ]);
    }

    public function edit(Employee $usuario)
    {
        return response()->json([
            'usuario' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'password' => '',
                'rol' => $usuario->getRoleNames()->first() ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $usuario = $request->filled('id') ? Employee::findOrFail($request->id) : null;

        // Mismo formato con que se guarda y se busca al iniciar sesión.
        $request->merge(['email' => mb_strtolower(trim((string) $request->email), 'UTF-8')]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email' . ($usuario ? ",{$usuario->id}" : ''),
            'password' => ($usuario ? 'nullable' : 'required') . '|string|min:8',
            'rol' => 'required|exists:roles,name',
        ], [], [
            'name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'rol' => 'rol',
        ]);

        // No dejar el sistema sin ningún administrador.
        $validator->after(function ($v) use ($usuario, $request) {
            $admin = config('permisos.rol_administrador');
            if ($usuario && $usuario->hasRole($admin) && $request->rol !== $admin && $this->cantidadAdministradores() <= 1) {
                $v->errors()->add('rol', 'Es el único Administrador: asigne ese rol a otra persona antes de cambiarle el rol.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, &$usuario) {
            $datos = $request->only(['name', 'email']);
            if ($request->filled('password')) {
                $datos['password'] = $request->password; // cast "hashed" del modelo
            }

            $rolAnterior = $usuario?->getRoleNames()->first();
            $usuario = $usuario ? tap($usuario)->update($datos) : Employee::create($datos);
            $usuario->syncRoles([$request->rol]);

            // syncRoles() no dispara eventos de modelo: se audita a mano.
            if ($rolAnterior !== $request->rol) {
                Auditoria::registrar(
                    'actualizado',
                    "Usuario {$usuario->name}: rol " . ($rolAnterior ? "{$rolAnterior} → " : '') . $request->rol,
                    $usuario,
                    ['rol' => $rolAnterior],
                    ['rol' => $request->rol],
                );
            }
        });

        session()->flash('message', $request->filled('id') ? 'Usuario actualizado.' : 'Usuario creado.');

        return response()->json(['success' => true, 'redirect' => route('employee.usuarios.index')]);
    }

    public function destroy(Employee $usuario)
    {
        $error = match (true) {
            $usuario->is(Auth::guard(Permisos::GUARD)->user()) => 'No puede eliminar su propio usuario.',
            $usuario->esAdministrador() && $this->cantidadAdministradores() <= 1 => 'No se puede eliminar al único Administrador.',
            default => null,
        };
        if ($error) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $usuario->syncRoles([]);
        $usuario->delete();

        session()->flash('message', 'Usuario eliminado.');

        return response()->json(['success' => true]);
    }

    private function cantidadAdministradores(): int
    {
        return Employee::role(config('permisos.rol_administrador'), Permisos::GUARD)->count();
    }
}
