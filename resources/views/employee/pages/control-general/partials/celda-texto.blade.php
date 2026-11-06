{{-- Celda de texto editable (se guarda al salir del campo). Variables: $campo, $lista (datalist o null), $ancho opcional. --}}
@if ($editable($campo))
    <td class="px-2 py-1 {{ $celdaManual }}">
        <input type="text" class="{{ $input }} {{ $ancho ?? '' }}" :value="f.{{ $campo }} ?? ''"
            @if (!empty($lista)) list="cg-lista-{{ $lista }}" @endif
            x-on:change="cambiar('{{ $campo }}', $event)" x-on:keydown.enter="$event.target.blur()" />
    </td>
@else
    <td class="max-w-[16rem] truncate px-2 py-1.5 text-gray-600" title="{{ $cg->{$campo} }}">{{ $cg->{$campo} ?? '—' }}</td>
@endif
