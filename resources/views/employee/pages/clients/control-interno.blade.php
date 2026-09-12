@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)
@php($backUrl = url()->previous(route('employee.control-interno.index')))

@section('content')
    <div
        x-data="controlInternoDetail({
            controlInternoUrl: '{{ route('employee.control-interno.solicitudes.manual.show', $id) }}',
        })"
        x-init="load()"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center gap-3 bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Control Interno</h2>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
            <div x-show="loading" class="flex flex-1 flex-col items-center justify-center gap-3 py-16">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600"></div>
                <p class="text-sm text-gray-500">Cargando Control Interno...</p>
            </div>

            <div x-show="!loading && loadError" x-cloak class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">No se pudo cargar Control Interno</p>
                <p class="max-w-sm text-xs text-gray-500" x-text="errorMessage"></p>
                <button type="button" @click="load()" class="btn-secondary mt-1">Reintentar</button>
            </div>

            <div x-show="!loading && !loadError" x-cloak class="space-y-6">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Fase actual</p>
                        <p class="mt-1 flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <span x-text="ci.fase"></span>
                            <span x-show="ci.indicadores?.nuevo" x-cloak class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-semibold text-brand-700">NUEVO</span>
                        </p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Semáforo</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="semaforoClase()" x-text="ci.indicadores?.semaforo || '—'"></span>
                        </p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Fecha de ingreso a GENERAL</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="ci.fecha_ingreso_general || 'No especificado'"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400" x-text="ci.indicadores?.desface_label || 'Desface'"></p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">
                            <span x-text="ci.indicadores?.desface_dias ?? '—'"></span>
                            <span x-show="ci.indicadores?.desface_dias !== null && ci.indicadores?.desface_dias !== undefined" x-cloak class="text-xs font-normal text-gray-400">días</span>
                        </p>
                    </div>
                </div>

                <div x-show="ci.indicadores?.dias_habiles !== null && ci.indicadores?.dias_habiles !== undefined" x-cloak
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Días hábiles (portal)</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="ci.indicadores?.dias_habiles"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Fuera de plazo (portal)</p>
                        <p class="mt-1 text-sm font-semibold" :class="ci.indicadores?.fuera_de_plazo ? 'text-red-600' : 'text-gray-900'" x-text="ci.indicadores?.fuera_de_plazo ? 'SÍ' : 'NO'"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Trimestre (portal)</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="ci.indicadores?.trimestre || '—'"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Semana (portal)</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="ci.indicadores?.semana_inicio || '—'"></p>
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-sm font-semibold text-gray-700">Columnas manuales</p>
                    <p class="mb-4 text-xs text-gray-400">Estas 3 las llena el staff a mano — la carga de Excel del portal nunca las sobrescribe.</p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label" for="ci_fecha_construccion">F. Construcción (control)</label>
                            <input id="ci_fecha_construccion" type="date" x-model="ci.fecha_construccion_control" class="form-input" />
                            <p class="form-error" x-show="errors.fecha_construccion_control" x-text="errors.fecha_construccion_control"></p>
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" x-model="ci.marcado_para_anular"
                                    class="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                                Marcar para anular
                            </label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label" for="ci_observacion">Observación</label>
                        <textarea id="ci_observacion" x-model="ci.observacion_control" rows="3" class="form-input" placeholder="Notas del staff (opcional)"></textarea>
                        <p class="form-error" x-show="errors.observacion_control" x-text="errors.observacion_control"></p>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="save()" :disabled="saving" class="btn-brand">
                            <span x-show="!saving">Guardar Control Interno</span>
                            <span x-show="saving" x-cloak>Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
