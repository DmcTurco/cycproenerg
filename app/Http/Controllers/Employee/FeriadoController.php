<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Feriado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeriadoController extends Controller
{
    public function index()
    {
        $feriados = Feriado::orderBy('fecha')->get();

        return response()->json(['feriados' => $feriados]);
    }

    public function store(Request $request)
    {
        $feriadoId = $request->id;

        $rules = [
            'fecha' => 'required|date|unique:feriados,fecha' . ($feriadoId ? ",$feriadoId" : ''),
            'descripcion' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['fecha', 'descripcion']);

        if ($feriadoId) {
            $feriado = Feriado::findOrFail($feriadoId);
            $feriado->update($data);
            $message = 'Feriado actualizado.';
        } else {
            $feriado = Feriado::create($data);
            $message = 'Feriado agregado.';
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.control-interno.parametros.edit'),
        ]);
    }

    public function edit(Feriado $feriado)
    {
        return response()->json([
            'feriado' => [
                'id' => $feriado->id,
                // El <input type="date"> solo acepta el formato Y-m-d; el
                // modelo castea fecha a Carbon y por defecto se serializa en
                // JSON como fecha+hora ISO, lo que dejaba el campo vacío al
                // editar.
                'fecha' => $feriado->fecha->format('Y-m-d'),
                'descripcion' => $feriado->descripcion,
            ],
        ]);
    }

    public function destroy(Feriado $feriado)
    {
        $feriado->delete();

        session()->flash('message', 'Feriado eliminado.');
        return redirect()->route('employee.control-interno.parametros.edit');
    }
}
