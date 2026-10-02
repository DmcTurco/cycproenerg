<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'tecnico' => $tecnico,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    
    public function profile()
    {
        return response()->json(auth()->user());
    }

    
}
