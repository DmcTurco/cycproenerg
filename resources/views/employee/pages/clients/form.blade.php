<div
    x-data="excelUploader({
        uploadUrl: '{{ route('employee.change') }}',
        progressUrl: '{{ url('employee/check-progress/:id') }}',
    })"
    class="grid flex-1 grid-cols-1 gap-4 lg:grid-cols-3"
>
    <div class="flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white lg:col-span-2">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-sm font-semibold text-gray-900">Carga de archivo Excel</h3>

            <template x-if="selectedFile">
                <span class="inline-flex max-w-full items-center gap-1.5 truncate rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span class="truncate" x-text="selectedFile?.name"></span>
                    <span class="shrink-0">· <span x-text="previewTotalRows"></span> filas</span>
                </span>
            </template>

            <button type="button" x-show="selectedFile" x-cloak @click="cancelPreview()" :disabled="status === 'uploading' || status === 'processing'"
                class="ml-auto text-xs font-semibold text-brand-600 hover:text-brand-700 disabled:opacity-50">
                Cambiar
            </button>
        </div>

        <div
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onFileDropped($event)"
            :class="dragging ? 'bg-brand-50' : ''"
            class="flex min-h-80 flex-1 flex-col p-4 transition-colors"
        >
            <div x-show="status === 'idle'" class="flex flex-1 flex-col items-center justify-center text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3 3m3-3l3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-900">Arrastra aquí tu archivo, o haz clic para elegirlo</p>
                <p class="mt-1 text-xs text-gray-500">El archivo debe ser un Excel válido (.xls, .xlsx)</p>
                <button type="button" @click="pickFile()" class="btn-brand mt-4">Seleccionar archivo</button>
            </div>

            <div x-show="status === 'preview'" x-cloak class="flex flex-1 flex-col">
                <p class="mb-2 text-xs font-medium text-gray-500">Columnas a importar:</p>
                <div class="mb-3 flex flex-nowrap gap-1.5 overflow-x-auto pb-2">
                    <template x-for="(header, index) in previewHeaders" :key="index">
                        <span class="shrink-0 whitespace-nowrap rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700" x-text="header"></span>
                    </template>
                </div>

                <div class="flex-1 overflow-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="sticky top-0 bg-brand-50">
                            <tr>
                                <th class="whitespace-nowrap px-2 py-1.5 text-left font-semibold text-brand-700">#</th>
                                <template x-for="(header, index) in previewHeaders" :key="index">
                                    <th class="whitespace-nowrap px-2 py-1.5 text-left font-semibold text-brand-700" x-text="header"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <template x-for="(row, rowIndex) in previewRows" :key="rowIndex">
                                <tr>
                                    <td class="whitespace-nowrap px-2 py-1.5 text-gray-400" x-text="rowIndex + 1"></td>
                                    <template x-for="(cell, cellIndex) in row" :key="cellIndex">
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600" x-text="cell"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p x-show="previewTotalRows > previewRows.length" x-cloak class="mt-2 text-xs text-gray-400">
                    Mostrando <span x-text="previewRows.length"></span> de <span x-text="previewTotalRows"></span> filas.
                </p>
            </div>

            <div x-show="status === 'uploading' || status === 'processing' || status === 'success' || status === 'error'" x-cloak class="flex flex-1 flex-col justify-center">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            <span x-show="status === 'uploading' || status === 'processing'">Procesando archivo...</span>
                            <span x-show="status === 'success'" x-cloak>Datos cargados</span>
                            <span x-show="status === 'error'" x-cloak>Error en el proceso</span>
                        </p>
                        <p class="text-xs text-gray-500" x-text="message"></p>
                    </div>

                    <a href="{{ route('employee.client.index') }}" x-show="status === 'success'" x-cloak class="btn-secondary">
                        Ver clientes
                    </a>
                    <button type="button" x-show="status === 'error'" x-cloak @click="reset()" class="btn-secondary">
                        Reintentar
                    </button>
                </div>

                <div x-show="status === 'processing' || status === 'uploading'" x-cloak class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full bg-brand-600 transition-all" :style="`width: ${progress}%`"></div>
                </div>

                <p x-show="status === 'error'" x-cloak x-text="errorMessage" class="mt-2 text-sm text-red-600"></p>
            </div>
        </div>

        <input type="file" x-ref="fileInput" @change="onFileSelected($event)" accept=".xls,.xlsx" class="hidden" />
    </div>

    <div class="flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white lg:col-span-1">
        <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v-6.75m3.75 6.75V6.75m3.75 10.5v-3.75M6 21h12a2.25 2.25 0 002.25-2.25V5.25A2.25 2.25 0 0018 3H6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006 21z" />
            </svg>
            <h3 class="text-sm font-semibold text-gray-900">Resultado de la carga</h3>
        </div>

        <div class="flex flex-1 flex-col p-4">
            <div class="grid grid-cols-2 gap-2 text-center">
                <div>
                    <p class="text-xl font-semibold text-gray-900" x-text="total"></p>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Total</p>
                </div>
                <div>
                    <p class="text-xl font-semibold text-green-600" x-text="created"></p>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Nuevos</p>
                </div>
                <div>
                    <p class="text-xl font-semibold text-brand-600" x-text="updated"></p>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Actualizados</p>
                </div>
                <div>
                    <p class="text-xl font-semibold" :class="failed > 0 ? 'text-red-600' : 'text-gray-300'" x-text="failed"></p>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Omitidos</p>
                </div>
            </div>

            <div x-show="status === 'idle'" class="flex flex-1 flex-col items-center justify-center gap-2 py-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                <p class="text-sm font-semibold text-gray-500">Aún no hay nada que mostrar</p>
                <p class="text-xs text-gray-400">Selecciona un archivo a la izquierda y aquí verás cuántos registros se crearon o actualizaron.</p>
            </div>

            <div x-show="status === 'preview'" x-cloak class="flex flex-1 flex-col items-center justify-center gap-3 py-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-semibold text-gray-700">Listo para guardar</p>
                <p class="text-xs text-gray-400">Al confirmar aparecerá el detalle: cuántas filas se crearon y cuántas se actualizaron.</p>
                <button type="button" @click="confirmUpload()" class="btn-brand mt-1">Confirmar carga</button>
            </div>

            <div x-show="status === 'uploading' || status === 'processing' || status === 'success' || status === 'error'" x-cloak class="mt-6 border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-500" x-text="message"></p>
            </div>
        </div>
    </div>
</div>
