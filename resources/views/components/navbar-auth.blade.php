@props(['titlePage' => null, 'backUrl' => null, 'titleVariant' => 'default'])

<header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
    <button
        type="button"
        @click="$store.sidebar.toggle()"
        class="btn-icon lg:hidden"
        aria-label="Abrir menú"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    @if ($backUrl)
        <a href="{{ $backUrl }}"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            <span>Volver</span>
        </a>
    @endif

    <h1 @class([
        'flex-1 text-base font-semibold text-gray-900' => $titleVariant === 'default',
        'flex-1 rounded-lg bg-amber-50 px-3 py-1.5 text-sm text-amber-800' => $titleVariant === 'warning',
    ])>{{ $titlePage }}</h1>

    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" class="btn-icon" aria-label="Notificaciones">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </button>
        <div
            x-show="open"
            x-cloak
            @click.outside="open = false"
            x-transition
            class="absolute right-0 mt-2 w-64 rounded-lg bg-white p-4 text-sm text-gray-500 shadow-lg ring-1 ring-gray-200"
        >
            No hay notificaciones nuevas.
        </div>
    </div>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>
    <button
        type="button"
        id="sign-logout"
        onclick="
            Swal.fire({
                title: '¿Cerrar sesión?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        "
        class="btn-icon"
        aria-label="Cerrar sesión"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
    </button>
</header>
