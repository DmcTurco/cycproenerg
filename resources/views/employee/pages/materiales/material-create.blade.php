@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $backUrl = route('employee.materiales.index', ['tab' => 'materiales']);
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Agregar materiales</h2>
            <a href="{{ $backUrl }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex-1 overflow-auto p-3 sm:p-4 lg:p-6">
            {{-- Dos columnas: a la izquierda uno por uno, a la derecha carga desde Excel --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
            {{-- UNO POR UNO: mismo formulario y endpoint que el modal de edición --}}
            <section class="rounded-xl border border-gray-200 p-4">
                <h3 class="mb-1 text-sm font-semibold text-gray-900">Registrar uno por uno</h3>
                <p class="mb-4 text-xs text-gray-500">Para agregar un material a la vez.</p>
                <form
                    x-data="crudModal({
                        baseUrl: '{{ route('employee.materiales.items.index') }}',
                        defaults: { id: null, codigo: '', descripcion: '', unidad: '', precio_base: '', margen_pct: '', stock_inicial: 0, stock_minimo: 0, factor_metros_por_unidad: 1, ultimo_precio_ingresos: null },
                    })"
                    @submit.prevent="submit()"
                    class="space-y-4"
                >
                    @include('employee.pages.materiales.material-form')

                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ $backUrl }}" class="btn-secondary">Cancelar</a>
                        <button type="submit" class="btn-brand" :disabled="submitting">
                            <span x-show="!submitting">Guardar material</span>
                            <span x-show="submitting" x-cloak>Guardando...</span>
                        </button>
                    </div>
                </form>
            </section>

            {{-- DESDE EXCEL: descargar plantilla, llenarla y subirla --}}
            <section class="flex flex-col gap-4 rounded-xl border border-gray-200 p-4">
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-gray-900">Cargar varios desde Excel</h3>
                    <p class="text-xs text-gray-500">Para agregar muchos materiales de una sola vez.</p>
                </div>
                @if (session('import_errores'))
                    <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                        <p class="mb-1 font-semibold">No se cargó ningún material. Corrija estos errores y vuelva a subir el archivo:</p>
                        <ul class="max-h-60 list-inside list-disc overflow-auto">
                            @foreach (session('import_errores') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="mb-1 text-sm font-semibold text-gray-900">1. Descargue la plantilla</p>
                    <p class="mb-3 text-sm text-gray-500">
                        Llene la hoja <strong>MATERIALES</strong> desde la fila 2, una fila por material. Obligatorios: Código, Descripción, Unidad y Precio base.
                        La hoja <strong>INSTRUCCIONES</strong> trae un ejemplo.
                    </p>
                    <a href="{{ route('employee.materiales.items.plantilla') }}" class="btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Descargar plantilla
                    </a>
                </div>

                <form method="POST" action="{{ route('employee.materiales.items.importar') }}" enctype="multipart/form-data"
                    x-data="{ archivo: '', enviando: false }" @submit="enviando = true"
                    class="rounded-lg bg-gray-50 p-4">
                    @csrf
                    <p class="mb-1 text-sm font-semibold text-gray-900">2. Suba el archivo lleno</p>
                    <p class="mb-3 text-sm text-gray-500">Si alguna fila tiene un error no se carga ninguna, y se le indica qué corregir en cada fila.</p>

                    <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 bg-white px-4 py-6 text-center hover:border-brand-400 hover:bg-brand-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span class="text-sm text-gray-600" x-text="archivo || 'Haga clic para elegir el Excel (.xlsx)'"></span>
                        <input type="file" name="archivo" accept=".xlsx,.xls" class="hidden"
                            @change="archivo = $event.target.files[0]?.name || ''" />
                    </label>
                    @error('archivo')
                        <p class="form-error">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="btn-brand" :disabled="!archivo || enviando">
                            <span x-show="!enviando">Cargar materiales</span>
                            <span x-show="enviando" x-cloak>Cargando...</span>
                        </button>
                    </div>
                </form>
            </section>
            </div>
        </div>
    </div>
@endsection
