<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\PersonaCampo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Personal de campo: una sola pantalla (con pestañas Personal directo /
 * Contratistas) que reemplaza a "Gestión de Técnicos" y a "Cuadrillas" de
 * Control de Materiales. Mismo patrón crudModal: store() crea o actualiza
 * según venga o no el id.
 */
class PersonaCampoController extends Controller
{
    public function index(Request $request)
    {
        $tipo = $request->query('tipo') === PersonaCampo::TIPO_CONTRATISTA
            ? PersonaCampo::TIPO_CONTRATISTA
            : PersonaCampo::TIPO_PERSONAL_DIRECTO;
        $search = trim((string) $request->query('search'));

        $personas = PersonaCampo::with('empresas')
            ->withCount('solicitudes')
            ->where('tipo', $tipo)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('nombre', "%{$search}%")
                        ->orWhereLike('numero_documento', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->appends($request->query());

        $conteos = PersonaCampo::selectRaw('tipo, COUNT(*) as total')->groupBy('tipo')->pluck('total', 'tipo');
        $empresas = Empresa::orderBy('codigo')->get();

        return view('employee.pages.tecnicos.index', compact('personas', 'tipo', 'search', 'conteos', 'empresas'));
    }

    public function store(Request $request)
    {
        $personaId = $request->id;
        $persona = $personaId ? PersonaCampo::findOrFail($personaId) : null;
        $ignorar = $personaId ? ",$personaId" : '';

        // La contraseña solo se exige si se está dando acceso a la app por
        // primera vez (hay email y la persona todavía no tiene contraseña).
        $pidePassword = $request->filled('email') && !($persona?->password);

        $rules = [
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:' . implode(',', PersonaCampo::tipos()),
            'estado' => 'required|in:' . PersonaCampo::ESTADO_ACTIVO . ',' . PersonaCampo::ESTADO_INACTIVO,
            'tipo_documento' => 'nullable|integer|in:' . collect(config('const.tipo_documeto'))->pluck('id')->implode(','),
            'numero_documento' => 'nullable|string|max:20|unique:personas_campo,numero_documento' . $ignorar,
            'fecha_nacimiento' => 'nullable|date',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:personas_campo,email' . $ignorar,
            'password' => ($pidePassword ? 'required' : 'nullable') . '|string|min:8',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'password.required' => 'Para dar acceso a la app hace falta una contraseña.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['nombre', 'tipo', 'estado', 'tipo_documento', 'numero_documento', 'fecha_nacimiento', 'celular', 'email']);
        $data = array_map(fn ($valor) => $valor === '' ? null : $valor, $data);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Sin email no hay acceso a la app: se limpia también la contraseña.
        if (empty($data['email'])) {
            $data['password'] = null;
        }

        if ($persona) {
            $persona->update($data);
            $message = 'Persona de campo actualizada.';
        } else {
            $persona = PersonaCampo::create($data);
            $message = 'Persona de campo registrada.';
        }

        // Las empresas llegan como "1,2" (form.empresas es un array en
        // Alpine y FormData.append() lo convierte solo con toString()).
        $empresaIds = collect(explode(',', (string) $request->input('empresas', '')))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => $id !== '' && is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $persona->empresas()->sync($empresaIds);

        // Si se quitó el acceso a la app, se cierran sus sesiones abiertas.
        if (!$persona->usaApp()) {
            $persona->tokens()->delete();
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.technicals.index', ['tipo' => $persona->tipo]),
        ]);
    }

    public function edit($id)
    {
        $persona = PersonaCampo::findOrFail($id);

        return response()->json([
            'persona' => [
                'id' => $persona->id,
                'nombre' => $persona->nombre,
                'tipo' => $persona->tipo,
                'estado' => $persona->estado,
                'tipo_documento' => $persona->tipo_documento !== null ? (string) $persona->tipo_documento : '',
                'numero_documento' => $persona->numero_documento,
                'fecha_nacimiento' => optional($persona->fecha_nacimiento)->format('Y-m-d'),
                'celular' => $persona->celular,
                'email' => $persona->email,
                'tiene_password' => filled($persona->password),
                'password' => '',
                'empresas' => $persona->empresas()->pluck('empresas.id')->map(fn ($id) => (string) $id)->all(),
            ],
        ]);
    }

    public function destroy($id)
    {
        $persona = PersonaCampo::findOrFail($id);
        $tipo = $persona->tipo;

        $persona->tokens()->delete();
        $persona->delete();

        session()->flash('message', 'Persona de campo eliminada.');
        return redirect()->route('employee.technicals.index', ['tipo' => $tipo]);
    }
}
