@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $backUrl = route('employee.control-general.index');
    $titlePage = 'Control General · Parámetros';
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex-1 p-4 sm:p-6 lg:min-h-0 lg:overflow-y-auto">
            <form method="POST" action="{{ route('employee.control-general.parametros.update') }}" class="max-w-xl space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="form-label" for="dias_limite_aprobacion">Días límite desde la aprobación</label>
                    <input id="dias_limite_aprobacion" name="dias_limite_aprobacion" type="number" min="1" max="365"
                        value="{{ old('dias_limite_aprobacion', $parametros->dias_limite_aprobacion) }}" class="form-input w-32" />
                    <p class="form-error">{{ $errors->first('dias_limite_aprobacion') }}</p>
                    <p class="mt-1 text-xs text-gray-500">Días calendario. Una solicitud en 1. Aprobadas con más días que esto sale EN DESFASE.</p>
                </div>

                <div>
                    <span class="form-label">Provincias que entran al control</span>
                    <div class="mt-1 flex gap-4">
                        @foreach (['LIMA', 'CALLAO'] as $provincia)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="ambito_departamentos[]" value="{{ $provincia }}"
                                    @checked(in_array($provincia, old('ambito_departamentos', $parametros->ambito_departamentos ?? [])))
                                    class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                {{ ucfirst(strtolower($provincia)) }}
                            </label>
                        @endforeach
                    </div>
                    <p class="form-error">{{ $errors->first('ambito_departamentos') }}</p>
                    <p class="mt-1 text-xs text-gray-500">Las solicitudes de otras provincias no se agregan ni se actualizan al cargar el Excel del portal.</p>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <button type="submit" class="btn-brand">Guardar parámetros</button>
                </div>
            </form>
        </div>
    </div>
@endsection
