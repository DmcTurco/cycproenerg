@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)
@php($backUrl = route('employee.control-interno.index'))

@section('content')
    <div class="flex flex-1 flex-col gap-4 lg:min-h-0 lg:overflow-hidden">

        <div class="flex flex-1 flex-col gap-4 lg:min-h-0 lg:flex-row">

            <div class="flex min-w-0 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200 lg:min-h-0 lg:flex-1">
                <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
                    <h2 class="text-base font-semibold text-white">Parámetros</h2>
                    <a href="{{ route('employee.control-interno.index') }}"
                        class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Salir</a>
                </div>

                <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:overflow-y-auto lg:p-4">
                    <p class="mb-6 text-sm text-gray-500">
                        Plazos y semáforo usados para clasificar las solicitudes — equivalente a la hoja PARAM del
                        Excel de Control Interno. Cambiar un valor aquí afecta los cálculos siguientes, no reescribe
                        fechas ya guardadas.
                    </p>

                    <form method="POST" action="{{ route('employee.control-interno.parametros.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-gray-700">Plazo de construcción</h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="form-label" for="plazo_construccion_dias_habiles">Plazo (días hábiles)</label>
                                    <input id="plazo_construccion_dias_habiles" name="plazo_construccion_dias_habiles" type="number" min="1"
                                        value="{{ old('plazo_construccion_dias_habiles', $parametros->plazo_construccion_dias_habiles) }}" class="form-input" />
                                    <p class="form-error">{{ $errors->first('plazo_construccion_dias_habiles') }}</p>
                                    <p class="mt-1 text-xs text-gray-400">Construida después de este plazo = FUERA DE PLAZO.</p>
                                </div>
                                <div>
                                    <label class="form-label" for="semaforo_verde_dias">Semáforo VERDE hasta (días hábiles)</label>
                                    <input id="semaforo_verde_dias" name="semaforo_verde_dias" type="number" min="1"
                                        value="{{ old('semaforo_verde_dias', $parametros->semaforo_verde_dias) }}" class="form-input" />
                                    <p class="form-error">{{ $errors->first('semaforo_verde_dias') }}</p>
                                </div>
                                <div>
                                    <label class="form-label" for="semaforo_ambar_dias">Semáforo ÁMBAR hasta (días hábiles)</label>
                                    <input id="semaforo_ambar_dias" name="semaforo_ambar_dias" type="number" min="1"
                                        value="{{ old('semaforo_ambar_dias', $parametros->semaforo_ambar_dias) }}" class="form-input" />
                                    <p class="form-error">{{ $errors->first('semaforo_ambar_dias') }}</p>
                                    <p class="mt-1 text-xs text-gray-400">Más de este valor = ROJO.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-3 text-sm font-semibold text-gray-700">Espera de TC (CONSTRUIDO sin TC todavía)</h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="form-label" for="espera_tc_verde_dias">VERDE hasta (días calendario)</label>
                                    <input id="espera_tc_verde_dias" name="espera_tc_verde_dias" type="number" min="1"
                                        value="{{ old('espera_tc_verde_dias', $parametros->espera_tc_verde_dias) }}" class="form-input" />
                                    <p class="form-error">{{ $errors->first('espera_tc_verde_dias') }}</p>
                                </div>
                                <div>
                                    <label class="form-label" for="espera_tc_ambar_dias">ÁMBAR hasta (días calendario)</label>
                                    <input id="espera_tc_ambar_dias" name="espera_tc_ambar_dias" type="number" min="1"
                                        value="{{ old('espera_tc_ambar_dias', $parametros->espera_tc_ambar_dias) }}" class="form-input" />
                                    <p class="form-error">{{ $errors->first('espera_tc_ambar_dias') }}</p>
                                    <p class="mt-1 text-xs text-gray-400">Más de este valor = ROJO.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label" for="meta_ind2">Meta IND 2 (% en plazo)</label>
                            <div class="flex items-center gap-2">
                                <input id="meta_ind2" name="meta_ind2" type="number" min="0" max="100" step="0.01"
                                    value="{{ old('meta_ind2', $parametros->meta_ind2 * 100) }}" class="form-input" />
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                            <p class="form-error">{{ $errors->first('meta_ind2') }}</p>
                            <p class="mt-1 text-xs text-gray-400">Supuesto: ajustar a la meta oficial de la concesionaria.</p>
                        </div>

                        <div>
                            <span class="form-label">Ámbito (departamentos del portal)</span>
                            <div class="flex flex-wrap gap-4 pt-2">
                                @foreach (['LIMA', 'CALLAO'] as $departamento)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="ambito_departamentos[]" value="{{ $departamento }}"
                                            @checked(in_array($departamento, old('ambito_departamentos', $parametros->ambito_departamentos ?? [])))
                                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                        {{ ucfirst(strtolower($departamento)) }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="form-error">{{ $errors->first('ambito_departamentos') }}</p>
                        </div>

                        <div class="flex justify-end border-t border-gray-100 pt-4">
                            <button type="submit" class="btn-brand">Guardar parámetros</button>
                        </div>
                    </form>
                </div>
            </div>

            <div
                x-data="crudModal({
                    baseUrl: '{{ route('employee.control-interno.feriados.index') }}',
                    unwrap: 'feriado',
                    defaults: { id: null, fecha: '', descripcion: '' },
                })"
                class="flex min-w-0 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200 lg:min-h-0 lg:flex-1"
            >
                <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
                    <h2 class="text-base font-semibold text-white">Feriados</h2>
                    <button type="button" @click="openCreate()" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Agregar feriado</button>
                </div>

                <div class="flex flex-1 flex-col overflow-hidden p-3 sm:p-4 lg:min-h-0 lg:p-4">
                    <p class="mb-4 text-sm text-gray-500">Usados para calcular días hábiles. Sin límite de filas (el Excel solo leía hasta la fila 40).</p>

                    <div class="max-h-96 overflow-auto rounded-lg border border-gray-100 lg:max-h-none lg:min-h-0 lg:flex-1">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Fecha</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Descripción</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($feriados as $feriado)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $feriado->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $feriado->descripcion ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="openEdit({{ $feriado->id }})" class="btn-icon" title="Editar feriado">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="remove({{ $feriado->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar feriado">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="3" message="No hay feriados registrados." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-crud-modal>
                    @include('employee.pages.control-interno.feriado-form')
                </x-crud-modal>
            </div>
        </div>

        <div class="shrink-0 rounded-md bg-white px-4 py-3 text-center text-xs text-gray-400 ring-1 ring-gray-200 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
