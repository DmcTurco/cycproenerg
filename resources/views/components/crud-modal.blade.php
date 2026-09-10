@props(['maxWidth' => 'lg'])

<x-modal :max-width="$maxWidth">
    <x-slot:title>
        <span x-text="mode === 'edit' ? 'Editar' : 'Registrar'"></span>
    </x-slot:title>

    <form @submit.prevent="submit()" class="space-y-4">
        {{ $slot }}
    </form>

    <x-slot:footer>
        <button type="button" @click="open = false" class="btn-secondary">Cancelar</button>
        <button type="button" @click="submit()" :disabled="submitting" class="btn-brand">
            <span x-show="!submitting" x-text="mode === 'edit' ? 'Actualizar' : 'Guardar'"></span>
            <span x-show="submitting" x-cloak>Guardando...</span>
        </button>
    </x-slot:footer>
</x-modal>
