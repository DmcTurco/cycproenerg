<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    public function index($tecnicoId) 
    {
        $tecnico = Tecnico::findOrFail($tecnicoId);
        return view('employee.pages.tecnicos.historial.index', compact('tecnico'));
    }
}
