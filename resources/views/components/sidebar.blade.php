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
            @php
                $hasChildren = ! empty($link['children']);
                $childActive = $hasChildren
                    ? collect($link['children'])->contains(fn ($child) => request()->is(...(array) $child['match']))
                    : false;
                $isActive = request()->is(...(array) $link['match']) || $childActive;
            @endphp

            @if ($hasChildren)
                <div x-data="{ open: @js($childActive) }">
                    <button
                        type="button"
                        @click="open = !open"
                        @class([
                            'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'text-gray-300 hover:bg-sidenav-hover hover:text-white' => ! $isActive,
                            'bg-brand-600 text-white' => $isActive,
                        ])
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            {!! $link['icon'] ?? '' !!}
                        </svg>
                        <span class="flex-1 text-left">{{ $link['label'] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="open" x-transition x-cloak class="mt-1 space-y-1 pl-4">
                        @foreach ($link['children'] as $child)
                            <a
                                href="{{ route($child['route']) }}"
                                @class([
                                    'flex items-center gap-3 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                    'text-gray-400 hover:bg-sidenav-hover hover:text-white' => ! request()->is(...(array) $child['match']),
                                    'bg-brand-500 text-white' => request()->is(...(array) $child['match']),
                                ])
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    {!! $child['icon'] ?? '' !!}
                                </svg>
                                <span>{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ route($link['route']) }}"
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'text-gray-300 hover:bg-sidenav-hover hover:text-white' => ! $isActive,
                        'bg-brand-600 text-white' => $isActive,
                    ])
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        {!! $link['icon'] ?? '' !!}
                    </svg>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endif
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
