@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $backUrl = route('employee.empresas.index');
    $titlePage = 'Empresa · ' . ($empresa->codigo ?: $empresa->nombre);
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex-1 p-4 sm:p-6 lg:min-h-0 lg:overflow-y-auto">
            <form method="POST" action="{{ route('employee.empresas.update', $empresa) }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $empresa->nombre }}</p>
                    <p class="text-xs text-gray-500">RUC {{ $empresa->numero_documento }}. El nombre y el RUC vienen del portal y no se editan.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ([
                        'registro_gas_natural' => ['N° de registro de la empresa', 'text'],
                        'categoria' => ['Categoría (ej. IG 3)', 'text'],
                        'direccion' => ['Dirección', 'text'],
                        'distrito' => ['Distrito', 'text'],
                        'provincia' => ['Provincia', 'text'],
                        'departamento' => ['Departamento', 'text'],
                        'telefono' => ['Teléfono', 'text'],
                        'correo' => ['Correo', 'email'],
                        'numero_poliza' => ['N° de póliza', 'text'],
                        'representante_legal' => ['Representante legal', 'text'],
                    ] as $campo => [$etiqueta, $tipo])
                        <div @class(['sm:col-span-2' => $campo === 'direccion'])>
                            <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                            <input id="{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" value="{{ old($campo, $empresa->{$campo}) }}" class="form-input w-full" />
                            <p class="form-error">{{ $errors->first($campo) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-6 border-t border-gray-100 pt-4 sm:grid-cols-2">
                    @foreach (['logo' => 'Logo', 'sello' => 'Firma y sello del representante legal'] as $campo => $etiqueta)
                        <div>
                            <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                            <div class="mb-2 flex h-24 items-center justify-center rounded border border-gray-100 bg-gray-50">
                                @if ($empresa->rutaArchivo($campo))
                                    <img src="{{ route('employee.empresas.imagen', [$empresa, $campo]) }}" alt="{{ $etiqueta }}" class="max-h-20 max-w-full">
                                @else
                                    <span class="text-xs text-gray-400">Sin imagen</span>
                                @endif
                            </div>
                            <input id="{{ $campo }}" name="{{ $campo }}" type="file" accept="image/png,image/jpeg" class="block w-full text-sm text-gray-600" />
                            <p class="form-error">{{ $errors->first($campo) }}</p>
                            @if ($empresa->{$campo})
                                <label class="mt-1 inline-flex items-center gap-2 text-xs text-gray-500">
                                    <input type="checkbox" name="quitar_{{ $campo }}" value="1" class="rounded border-gray-300" /> Quitar la imagen actual
                                </label>
                            @endif
                            <p class="mt-1 text-xs text-gray-500">PNG o JPG, hasta 2 MB.</p>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <button type="submit" class="btn-brand">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endsection
