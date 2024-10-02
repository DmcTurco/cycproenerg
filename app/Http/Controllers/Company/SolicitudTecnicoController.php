<?php

namespace App\Http\Controllers\Company;

use App\Models\Tecnico;
use App\Models\SolicitudTecnico;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudTecnicoController extends Controller
{
    public function index($id) {

        $tecnico = Tecnico::findOrFail($id);
        if ( $tecnico->company_id != Auth::user()->id) {
            abort(403);
        }
        $solicitudes = SolicitudTecnico::where('tecnico_id', $tecnico->id)->orderBy('id', 'desc')->paginate(10);

        return view('company.pages.solicitudesTecnico.index', compact('solicitudes', 'tecnico'));
    }
}
