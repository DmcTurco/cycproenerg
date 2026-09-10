@props(['homeRoute' => null])

<header x-data="{ open: false }" class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
        <a href="{{ $homeRoute ? route($homeRoute) : '/' }}" class="text-lg font-bold text-gray-900">CYC PROENERG</a>
        <button @click="open = !open" class="btn-icon sm:hidden" aria-label="Menú">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
    <nav x-show="open" x-collapse class="border-t border-gray-100 px-4 py-3 sm:hidden">
        <p class="text-sm text-gray-500">Menú</p>
    </nav>
</header>
