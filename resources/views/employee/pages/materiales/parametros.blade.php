@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $backUrl = route('employee.materiales.index');
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Parámetros — Control de Materiales</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Salir</a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:overflow-y-auto lg:p-4">
            <p class="mb-6 max-w-2xl text-sm text-gray-500">
                Equivalente al bloque PARAMETROS GENERALES de la hoja INICIO del Excel de Control de Materiales.
                Alimentan el catálogo, las cotizaciones y las alertas de precio.
            </p>

            <form method="POST" action="{{ route('employee.materiales.parametros.update') }}" class="max-w-xl space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="form-label" for="igv">IGV</label>
                    <div class="flex items-center gap-2">
                        <input id="igv" name="igv" type="number" min="0" max="100" step="0.01"
                            value="{{ old('igv', $parametros->igv * 100) }}" class="form-input" />
                        <span class="text-sm text-gray-500">%</span>
                    </div>
                    <p class="form-error">{{ $errors->first('igv') }}</p>
                </div>

                <div>
                    <label class="form-label" for="margen_general">Margen general sobre compra</label>
                    <div class="flex items-center gap-2">
                        <input id="margen_general" name="margen_general" type="number" min="0" max="100" step="0.01"
                            value="{{ old('margen_general', $parametros->margen_general * 100) }}" class="form-input" />
                        <span class="text-sm text-gray-500">%</span>
                    </div>
                    <p class="form-error">{{ $errors->first('margen_general') }}</p>
                    <p class="mt-1 text-xs text-gray-400">Se usa cuando un material no tiene su propio margen (columna Margen % del catálogo).</p>
                </div>

                <div>
                    <label class="form-label" for="umbral_alarma_precio">Umbral de alarma de precio</label>
                    <div class="flex items-center gap-2">
                        <input id="umbral_alarma_precio" name="umbral_alarma_precio" type="number" min="0" max="100" step="0.01"
                            value="{{ old('umbral_alarma_precio', $parametros->umbral_alarma_precio * 100) }}" class="form-input" />
                        <span class="text-sm text-gray-500">%</span>
                    </div>
                    <p class="form-error">{{ $errors->first('umbral_alarma_precio') }}</p>
                    <p class="mt-1 text-xs text-gray-400">Variación mínima para que un ingreso (CM-3) dispare la alerta "SUBIO" en vez de "ESTABLE".</p>
                </div>

                <div class="flex justify-end border-t border-gray-100 pt-4">
                    <button type="submit" class="btn-brand">Guardar parámetros</button>
                </div>
            </form>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
