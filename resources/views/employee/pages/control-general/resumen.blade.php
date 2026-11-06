@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
        $titlePage = 'Control General · Resumen';
    $navSubtitle = 'Lima y Callao · ' . ($ultimaCarga
        ? 'Última carga del portal: ' . $ultimaCarga->created_at->format('d/m/Y H:i')
        : 'Todavía no se cargó ningún Excel del portal');
@endphp

@section('nav_actions')
    <a href="{{ route('employee.control-general.index') }}" class="btn-secondary px-3 py-1.5 text-sm">Ver etapas</a>
@endsection

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex-1 p-3 sm:p-4 lg:min-h-0 lg:overflow-y-auto">
            <p class="mb-4 text-xs text-gray-500">
                Fecha de corte: <strong>{{ now()->format('d/m/Y') }}</strong> ·
                Días límite desde la aprobación: <strong>{{ $diasLimite }}</strong>
                (se cambia en Control General → Parámetros).
            </p>

            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($secciones as $titulo => $filas)
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    <th class="px-4 py-2.5">{{ $titulo }}</th>
                                    <th class="w-20 px-4 py-2.5 text-right">CLB</th>
                                    <th class="w-20 px-4 py-2.5 text-right">C&amp;C</th>
                                    <th class="w-20 px-4 py-2.5 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($filas as $etiqueta => $valores)
                                    <tr class="{{ $loop->first ? 'font-semibold text-gray-900' : 'text-gray-700' }}">
                                        <td class="px-4 py-2 {{ str_starts_with($etiqueta, 'EN DESFASE') && $valores['total'] > 0 ? 'text-red-600' : '' }}">{{ $etiqueta }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums">{{ $valores['CLB'] }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums">{{ $valores['CYC'] }}</td>
                                        <td class="px-4 py-2 text-right font-semibold tabular-nums">{{ $valores['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

            <p class="mt-4 text-xs text-gray-500">
                Solo entran solicitudes de Lima y Callao. Fecha de aprobación = fecha de suscripción de contrato
                (o la de entrega de documentos si falta). Al cargar el Excel del portal, lo que escribiste a mano se conserva.
            </p>
        </div>
    </div>
@endsection
