<template x-teleport="body">
    <div x-show="ubicacion.open" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4 backdrop-blur-sm"
        @click.self="closeUbicacion()">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Detalles de Ubicación</h3>
                <button type="button" @click="closeUbicacion()" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 grid grid-cols-3 gap-3 text-sm">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Departamento</p>
                    <p class="mt-1 font-medium text-gray-900" x-text="ubicacion.departamento"></p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Provincia</p>
                    <p class="mt-1 font-medium text-gray-900" x-text="ubicacion.provincia"></p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Distrito</p>
                    <p class="mt-1 font-medium text-gray-900" x-text="ubicacion.distrito"></p>
                </div>
            </div>

            <div x-show="ubicacion.hasCoords" x-cloak class="overflow-hidden rounded-lg border border-gray-200">
                <iframe :src="ubicacion.embedUrl" class="h-72 w-full" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <div x-show="!ubicacion.hasCoords" x-cloak class="flex h-40 flex-col items-center justify-center gap-2 rounded-lg bg-gray-50 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                </svg>
                <p class="text-sm font-medium text-gray-500">Sin coordenadas disponibles</p>
            </div>
        </div>
    </div>
</template>
