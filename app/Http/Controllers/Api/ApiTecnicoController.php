<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
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

        $tecnico = Tecnico::where('email', $request->email)->first();

        if (!$tecnico || !Hash::check($request->password, $tecnico->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
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
