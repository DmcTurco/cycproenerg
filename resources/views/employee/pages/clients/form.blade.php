<div
    x-data="excelUploader({
        uploadUrl: '{{ route('employee.change') }}',
        progressUrl: '{{ url('employee/check-progress/:id') }}',
    })"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <button type="button" @click="pickFile()" :disabled="status === 'uploading' || status === 'processing'"
                class="btn-icon h-12 w-12 bg-green-50 text-green-700 hover:bg-green-100 disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </button>
            <div>
                <p class="text-sm font-semibold text-gray-900">
                    <span x-show="status === 'idle'">Portal de carga de Excel</span>
                    <span x-show="status === 'uploading' || status === 'processing'" x-cloak>Procesando archivo...</span>
                    <span x-show="status === 'success'" x-cloak>Datos cargados</span>
                    <span x-show="status === 'error'" x-cloak>Error en el proceso</span>
                </p>
                <p class="text-xs text-gray-500" x-show="status === 'idle'">Haz clic en el ícono para seleccionar un archivo (.xls, .xlsx)</p>
                <p class="text-xs text-gray-500" x-show="status !== 'idle'" x-cloak x-text="message"></p>
            </div>
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

    <input type="file" x-ref="fileInput" @change="onFileSelected($event)" accept=".xls,.xlsx" class="hidden" />
</div>
