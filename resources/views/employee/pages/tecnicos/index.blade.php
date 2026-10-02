@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;

    $coloresEstado = [
        \App\Models\PersonaCampo::ESTADO_ACTIVO => 'bg-green-100 text-green-700',
        \App\Models\PersonaCampo::ESTADO_INACTIVO => 'bg-gray-100 text-gray-600',
    ];
    $pestanas = [
        \App\Models\PersonaCampo::TIPO_PERSONAL_DIRECTO => 'Personal directo',
        \App\Models\PersonaCampo::TIPO_CONTRATISTA => 'Contratistas',
    ];
    $tiposDocumento = collect(config('const.tipo_documeto'))->pluck('name', 'id');
    $esPersonalDirecto = $tipo === \App\Models\PersonaCampo::TIPO_PERSONAL_DIRECTO;
@endphp

@section('content')
    <div
        x-data="crudModal({
            baseUrl: '{{ route('employee.technicals.index') }}',
            unwrap: 'persona',
            defaults: { id: null, nombre: '', tipo: '{{ $tipo }}', estado: 'ACTIVO', tipo_documento: '1', numero_documento: '', fecha_nacimiento: '', celular: '', email: '', password: '', tiene_password: false, empresas: [] },
        })"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Personal de Campo</h2>
            <button type="button" @click="openCreate()" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Registrar</button>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    @foreach ($pestanas as $valor => $etiqueta)
                        <a href="{{ route('employee.technicals.index', ['tipo' => $valor] + ($search ? ['search' => $search] : [])) }}"
                            class="rounded-full px-3 py-1.5 text-sm font-medium {{ $tipo === $valor ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $etiqueta }}
                            <span class="ml-1 opacity-75">({{ $conteos[$valor] ?? 0 }})</span>
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('employee.technicals.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="tipo" value="{{ $tipo }}" />
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre, documento o correo"
                        class="w-64 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
                    @if ($search)
                        <a href="{{ route('employee.technicals.index', ['tipo' => $tipo]) }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                    @endif
                </form>
            </div>

            <p class="mb-3 text-xs text-gray-500">
                @if ($esPersonalDirecto)
                    Retira materiales con <strong>vale a costo</strong> y reporta lo <strong>ejecutado</strong>. Puede recibir solicitudes y usar la app.
                @else
                    Retira materiales con <strong>cotización con IGV</strong> (no reporta ejecutado). Puede recibir solicitudes y usar la app.
                @endif
            </p>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Documento</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Nombre</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Estado</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Empresa(s)</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Celular</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">App</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">N° retiros</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Valorizado S/IGV</th>
                            @unless ($esPersonalDirecto)
                                <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Pend. descuento</th>
                            @endunless
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Historial</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Solicitudes</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($personas as $persona)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($persona->numero_documento)
                                        <span class="badge bg-gray-100 text-gray-700">{{ $tiposDocumento[$persona->tipo_documento] ?? 'DOC' }}</span>
                                        <span class="ml-1 font-medium text-gray-700">{{ $persona->numero_documento }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $persona->nombre }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $coloresEstado[$persona->estado] ?? 'bg-gray-100 text-gray-700' }}">{{ $persona->estado }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $persona->empresas->pluck('codigo')->filter()->implode(' / ') ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $persona->celular ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($persona->usaApp())
                                        <span title="Tiene acceso a la app">{{ $persona->email }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">Sin acceso</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $persona->numRetiros() }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($persona->totalValorizado(), 2) }}</td>
                                @unless ($esPersonalDirecto)
                                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($persona->pendienteDescuento(), 2) }}</td>
                                @endunless
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('employee.technicals.record.index', $persona->id) }}"
                                        class="btn-icon" title="Ver historial de solicitudes">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{-- Personal no ACTIVO: en vez de entrar a asignar, modal informativo --}}
                                    <a href="{{ route('employee.technicals.requests.index', $persona->id) }}"
                                        @if ($persona->estaActivo())
                                            class="btn-icon relative" title="Asignar solicitudes"
                                        @else
                                            class="btn-icon relative opacity-50" title="{{ $persona->estado }}: no se le pueden asignar solicitudes"
                                            @click.prevent="window.Swal?.fire({
                                                icon: 'info',
                                                title: 'Personal inactivo',
                                                text: @js($persona->nombre . ' está ' . $persona->estado . ', por eso no se le pueden asignar solicitudes. Cámbielo a ACTIVO para poder asignarle.'),
                                                confirmButtonText: 'Entendido',
                                            })"
                                        @endif>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h6" />
                                        </svg>
                                        <span class="badge absolute -right-1 -top-1 bg-brand-600 text-white">{{ $persona->solicitudes_count }}</span>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="openEdit({{ $persona->id }})" class="btn-icon" title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="remove({{ $persona->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state :colspan="$esPersonalDirecto ? 11 : 12" message="No hay personas registradas en esta pestaña." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 shrink-0">
                {{ $personas->links('pagination::tailwind') }}
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>

        <x-crud-modal>
            @include('employee.pages.tecnicos.form')
        </x-crud-modal>
    </div>
@endsection
