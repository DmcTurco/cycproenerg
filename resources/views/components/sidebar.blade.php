@props(['links' => [], 'brand' => 'CYC PROENERG'])

<aside
    :class="{ 'translate-x-0': $store.sidebar.open, '-translate-x-full': !$store.sidebar.open }"
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-sidenav -translate-x-full transition-transform duration-200 ease-in-out lg:translate-x-0"
>
    <div class="flex h-16 items-center px-6">
        <span class="text-lg font-bold text-white">{{ $brand }}</span>
    </div>
    <hr class="border-white/10">
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-300 transition hover:bg-sidenav-hover hover:text-white',
                    'bg-brand-600 text-white hover:bg-brand-600' => request()->is($link['match']),
                ])
            >
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>

<div
    x-show="$store.sidebar.open"
    x-cloak
    @click="$store.sidebar.open = false"
    x-transition.opacity
    class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
></div>
