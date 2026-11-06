{{-- Celda con lista fija. Variables: $campo, $opciones, $vacio (texto de la opción vacía), $confirmarEn + $confirmar opcionales. --}}
@if ($editable($campo))
    <td class="px-2 py-1 {{ $celdaManual }}">
        <select class="{{ $select }}"
            x-on:change="cambiar('{{ $campo }}', $event, @js(isset($confirmar) ? ['valores' => $confirmarEn ?? [], 'mensaje' => $confirmar] : null))">
            <option value="" :selected="!f.{{ $campo }}">{{ $vacio ?? '—' }}</option>
            @foreach ($opciones as $opcion)
                <option value="{{ $opcion }}" :selected="f.{{ $campo }} === @js($opcion)">{{ $opcion }}</option>
            @endforeach
        </select>
    </td>
@else
    <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->{$campo} ?? '—' }}</td>
@endif
