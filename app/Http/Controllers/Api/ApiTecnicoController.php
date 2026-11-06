<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\PersonaCampo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiTecnicoController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $tecnico = PersonaCampo::where('email', $request->email)->first();

        if (!$tecnico || !$tecnico->password || !Hash::check($request->password, $tecnico->password)) {
            Auditoria::registrar('login_fallido', 'App móvil: intento de acceso con ' . $request->email, $tecnico, modulo: 'Acceso');

            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        if ($tecnico->estado !== PersonaCampo::ESTADO_ACTIVO) {
            return response()->json([
                'message' => 'Usuario inactivo'
            ], 403);
        }

        $token = $tecnico->createToken('auth_token')->plainTextToken;

        Auditoria::registrar('login', 'App móvil: ' . $tecnico->nombre . ' inició sesión', $tecnico, modulo: 'Acceso', usuario: $tecnico);

        return response()->json([
            'usuario' => $this->usuarioApp($tecnico),
            // Se mantiene para builds viejos de la app que leen tecnico.nombre.
            'tecnico' => $tecnico,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Auditoria::registrar('logout', 'App móvil: ' . $request->user()->nombre . ' cerró sesión', $request->user(), modulo: 'Acceso', usuario: $request->user());

        return response()->json(['message' => 'Sesión cerrada']);
    }

    
    public function profile(Request $request)
    {
        return response()->json($this->usuarioApp($request->user()));
    }

    /**
     * Lo que la app necesita saber del personal de campo logueado. El TIPO
     * decide qué podrá hacer en la app (PERSONAL DIRECTO reporta ejecutado,
     * CONTRATISTA no), así que viaja siempre.
     */
    private function usuarioApp(PersonaCampo $persona): array
    {
        return [
            'id' => $persona->id,
            'nombre' => $persona->nombre,
            'tipo' => $persona->tipo,
            'estado' => $persona->estado,
            'email' => $persona->email,
            'celular' => $persona->celular,
            'empresas' => $persona->empresas()->get(['empresas.id', 'empresas.codigo', 'empresas.nombre'])
                ->map(fn ($e) => ['id' => $e->id, 'codigo' => $e->codigo, 'nombre' => $e->nombre])
                ->values(),
        ];
    }

    
}
