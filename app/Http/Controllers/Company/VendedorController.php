<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VendedorController extends Controller
{

    public function index()
    {
        $vendedores = Vendedor::where('company_id', Auth::user()->id)->paginate(10);
        return view('company.pages.vendedores.index', compact('vendedores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendedor $vendedor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vendedor $vendedor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vendedor $vendedor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendedor $vendedor)
    {
        //
    }
}
