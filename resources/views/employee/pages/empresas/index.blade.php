@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $titlePage = 'Empresas';
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-100 px-4 py-3">
            <p class="text-sm text-gray-600">
                Datos, logo y firma y sello de cada empresa instaladora. Salen en los documentos de habilitación
                (acta, declaraciones juradas y certificado de garantías).
            </p>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($empresas as $empresa)
                    @php
                        $faltan = collect(['direccion' => 'dirección', 'categoria' => 'categoría', 'telefono' => 'teléfono', 'correo' => 'correo', 'numero_poliza' => 'póliza'])
                            ->filter(fn ($_, $campo) => blank($empresa->{$campo}))->values();
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $empresa->nombre ?? 'Sin nombre' }}</p>
                                <p class="text-xs text-gray-500">
                                    RUC {{ $empresa->numero_documento ?? '—' }}
                                    @if ($empresa->codigo) · <span class="font-medium">{{ $empresa->codigo }}</span> @endif
                                </p>
                            </div>
                            @if (\App\Support\Permisos::puedeRuta('employee.empresas.update'))
                                <a href="{{ route('employee.empresas.edit', $empresa) }}" class="btn-secondary shrink-0 px-3 py-1 text-sm">Editar</a>
                            @endif
                        </div>

                        <div class="mt-3 flex items-center gap-3">
                            <div class="flex h-16 w-28 items-center justify-center rounded border border-gray-100 bg-gray-50">
                                @if ($empresa->rutaArchivo('logo'))
                                    <img src="{{ route('employee.empresas.imagen', [$empresa, 'logo']) }}" alt="Logo" class="max-h-14 max-w-[6.5rem]">
                                @else
                                    <span class="text-xs text-gray-400">Sin logo</span>
                                @endif
                            </div>
                            <div class="flex h-16 w-28 items-center justify-center rounded border border-gray-100 bg-gray-50">
                                @if ($empresa->rutaArchivo('sello'))
                                    <img src="{{ route('employee.empresas.imagen', [$empresa, 'sello']) }}" alt="Firma y sello" class="max-h-14 max-w-[6.5rem]">
                                @else
                                    <span class="text-xs text-gray-400">Sin sello</span>
                                @endif
                            </div>
                        </div>

                        @if ($faltan->isNotEmpty())
                            <p class="mt-3 text-xs text-amber-700">Falta: {{ $faltan->implode(', ') }}.</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Todavía no hay empresas. Se crean al cargar el Excel del portal.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
