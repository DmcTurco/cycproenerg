@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Imprimibles</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Ver catálogo
            </a>
        </div>

        <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-3 sm:p-4 lg:p-6">

            {{-- Acta de entrega de materiales --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <h3 class="text-sm font-semibold text-gray-900">Acta de entrega de materiales</h3>
                <p class="mt-1 text-xs text-gray-500">
                    Formato en blanco para llenar a mano en campo (N°, código, descripción, cantidad y unidad). Elegir una cuadrilla es opcional: solo
                    pre-llena el encabezado (nombre, empresa y tipo).
                </p>
                <form method="GET" action="{{ route('employee.materiales.imprimibles.acta-materiales') }}" class="mt-3 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Cuadrilla (opcional)</label>
                        <select name="cuadrilla_id" class="w-64 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">— En blanco —</option>
                            @foreach ($cuadrillas as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->tipo }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-gray-800 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-900">
                        Descargar PDF
                    </button>
                </form>
            </div>

            {{-- Acta de entrega de herramientas --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <h3 class="text-sm font-semibold text-gray-900">Acta de entrega de herramientas</h3>
                <p class="mt-1 text-xs text-gray-500">
                    Elegir herramientas pre-llena sus filas (código, descripción, marca/serie, estado) — a diferencia de los materiales, cada herramienta
                    ya tiene identidad única en el catálogo. Sin herramientas elegidas, sale en blanco. Máximo 12 por página; si eliges más, se reparten
                    en varias páginas.
                </p>
                <form method="GET" action="{{ route('employee.materiales.imprimibles.acta-herramientas') }}" class="mt-3 flex flex-col gap-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Cuadrilla (opcional, receptor)</label>
                            <select name="cuadrilla_id" class="w-64 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                <option value="">— En blanco —</option>
                                @foreach ($cuadrillas as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->tipo }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-lg bg-gray-800 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-900">
                            Descargar PDF
                        </button>
                    </div>
                    <div class="max-h-40 overflow-y-auto rounded-lg bg-white p-2 ring-1 ring-gray-200">
                        <div class="grid grid-cols-2 gap-1 sm:grid-cols-3">
                            @foreach ($herramientas as $h)
                                <label class="flex items-center gap-1.5 text-xs text-gray-700">
                                    <input type="checkbox" name="herramientas[]" value="{{ $h->id }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                    {{ $h->codigo }} — {{ $h->descripcion }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            {{-- Stickers de herramientas --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <h3 class="text-sm font-semibold text-gray-900">Stickers de herramientas</h3>
                <p class="mt-1 text-xs text-gray-500">
                    Para imprimir en papel adhesivo A4 y pegar en cada herramienta (código, descripción, marca/serie y responsable actual). 21 por hoja.
                    Sin nada marcado, se generan TODAS las herramientas del catálogo.
                </p>
                <form method="GET" action="{{ route('employee.materiales.imprimibles.stickers') }}" class="mt-3 flex flex-col gap-3">
                    <div class="max-h-40 overflow-y-auto rounded-lg bg-white p-2 ring-1 ring-gray-200">
                        <div class="grid grid-cols-2 gap-1 sm:grid-cols-3">
                            @foreach ($herramientas as $h)
                                <label class="flex items-center gap-1.5 text-xs text-gray-700">
                                    <input type="checkbox" name="herramientas[]" value="{{ $h->id }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                    {{ $h->codigo }} — {{ $h->descripcion }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="self-start rounded-lg bg-gray-800 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-900">
                        Descargar stickers PDF
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
