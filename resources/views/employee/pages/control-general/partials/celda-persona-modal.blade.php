{{--
    Celda de una persona elegida de lista (técnico en 3-Habilitación,
    contratista en 2-TC Construidas): muestra el nombre y, si se puede editar,
    un lápiz (o clic en el nombre) que abre el modal de la fila, donde se elige
    de la lista. Variable: $campo ('tecnico_id' | 'contratista_id').
--}}
@php
    $nombre = \Illuminate\Support\Str::beforeLast($campo, '_id');
    $etiquetaPersona = $campo === 'tecnico_id' ? 'técnico' : 'contratista';
@endphp
@if ($editable($campo))
    <td class="px-2 py-1.5 {{ $celdaManual }}">
        <div class="flex items-center gap-1.5">
            <button type="button" @click="abrirModal()" class="max-w-[12rem] flex-1 truncate text-left text-gray-700 hover:text-brand-700"
                :title="f.{{ $nombre }} || 'Seleccionar {{ $etiquetaPersona }}'" :class="f.{{ $nombre }} ? '' : 'text-gray-400'"
                x-text="f.{{ $nombre }} || 'Seleccionar…'"></button>
            <button type="button" @click="abrirModal()" class="btn-icon shrink-0" title="Seleccionar {{ $etiquetaPersona }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
            </button>
        </div>
    </td>
@else
    <td class="max-w-[16rem] truncate px-2 py-1.5 text-gray-600" title="{{ $cg->{$nombre}?->nombre }}">{{ $cg->{$nombre}?->nombre ?? '—' }}</td>
@endif
