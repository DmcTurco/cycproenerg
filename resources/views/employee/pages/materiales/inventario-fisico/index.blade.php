@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Inventario Físico</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            @if (session('message'))
                <div class="mb-3 shrink-0 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('message') }}</div>
            @endif

            <div class="mb-3 flex shrink-0 items-center justify-between">
                <p class="text-xs text-gray-500">
                    Cuenta físicamente el almacén y anota el conteo. La diferencia se calcula sola. Queda guardado para siempre, a diferencia del Excel original.
                </p>
                <a href="{{ route('employee.materiales.inventario-fisico.create') }}" class="btn-brand shrink-0 px-3 py-1.5 text-sm">Nuevo conteo</a>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Fecha</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Realizado por</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Ítems contados</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Con diferencia</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Almacenero / Supervisor</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($inventarios as $inventario)
                            <tr>
                                <td class="px-4 py-3 text-gray-600">{{ $inventario->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ $inventario->realizado_por }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $inventario->detalles_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    @php($conDiferencia = $inventario->itemsConDiferencia())
                                    <span class="badge {{ $conDiferencia > 0 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                                        {{ $conDiferencia }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $inventario->nombre_almacenero ?? '—' }} / {{ $inventario->nombre_supervisor ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('employee.materiales.inventario-fisico.show', $inventario) }}" class="btn-icon text-brand-600 hover:bg-brand-50" title="Ver">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('employee.materiales.inventario-fisico.destroy', $inventario) }}" onsubmit="return confirm('¿Eliminar este inventario? Queda en la papelera (soft delete).');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" message="No hay inventarios físicos guardados todavía." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 shrink-0">
                {{ $inventarios->links('pagination::tailwind') }}
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
