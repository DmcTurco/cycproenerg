@props(['maxWidth' => 'md'])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-lg',
    'lg' => 'sm:max-w-2xl',
    'xl' => 'sm:max-w-4xl',
][$maxWidth] ?? 'sm:max-w-lg';
@endphp

<div
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 bg-gray-900/50"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition
            @click.outside="open = false"
            {{ $attributes->merge(['class' => "relative w-full $maxWidthClass rounded-2xl bg-white p-6 shadow-xl"]) }}
        >
            <button
                type="button"
                @click="open = false"
                class="btn-icon absolute right-4 top-4"
                aria-label="Cerrar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            @isset($title)
                <h3 class="mb-4 pr-8 text-lg font-semibold text-gray-900">{{ $title }}</h3>
            @endisset

            <div>
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="mt-6 flex justify-end gap-3">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
