{{-- Paginador compacto: reemplaza el pagination::tailwind de Laravel en todo el panel. --}}
@if ($paginator->hasPages())
    @php
        $btn = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs font-medium transition';
        $normal = $btn . ' text-gray-600 hover:bg-gray-100 hover:text-gray-900';
        $actual = $btn . ' bg-brand-600 text-white shadow-sm';
        $deshabilitado = $btn . ' cursor-not-allowed text-gray-300';
    @endphp

    <nav role="navigation" aria-label="Paginación" class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-gray-500">
            @if ($paginator->firstItem())
                <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                de <span class="font-medium text-gray-700">{{ $paginator->total() }}</span>
            @else
                {{ $paginator->count() }} resultados
            @endif
        </p>

        <div class="flex items-center gap-0.5">
            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $deshabilitado }}" aria-disabled="true" aria-label="Anterior">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $normal }}" aria-label="Anterior">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @endif

            {{-- Números --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $deshabilitado }}" aria-disabled="true">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $actual }}" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $normal }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Siguiente --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $normal }}" aria-label="Siguiente">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span class="{{ $deshabilitado }}" aria-disabled="true" aria-label="Siguiente">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
