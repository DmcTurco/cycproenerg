@extends('employee.layouts.user_type.auth')

@section('content')
    <div class="card">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Asesores</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Tipo de documento</th>
                        <th class="px-4 py-3">N° Documento</th>
                        <th class="px-4 py-3">Cargo</th>
                        <th class="px-4 py-3 text-center">Solicitudes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($asesores as $asesor)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $asesor->nombre }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $asesor->tipo_documento }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $asesor->numero_documento_identificacion }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $asesor->cargo }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('employee.technicals.requests.index', $asesor->id) }}" class="btn-icon" title="Ver solicitudes">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="5" message="No existen asesores registrados." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $asesores->links('pagination::tailwind') }}
        </div>
    </div>
@endsection
