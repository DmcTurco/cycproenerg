{{-- Celda Sí / No. Variables: $campo, $confirmarEn + $confirmar opcionales. --}}
@if ($editable($campo))
    <td class="px-2 py-1 {{ $celdaManual }}">
        <select class="{{ $select }}" :class="f.{{ $campo }} === '1' ? 'font-semibold text-green-700' : ''"
            x-on:change="cambiar('{{ $campo }}', $event, @js(isset($confirmar) ? ['valores' => $confirmarEn ?? [], 'mensaje' => $confirmar] : null))">
            <option value="0" :selected="f.{{ $campo }} !== '1'">No</option>
            <option value="1" :selected="f.{{ $campo }} === '1'">Sí</option>
        </select>
    </td>
@else
    <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->{$campo} ? 'Sí' : 'No' }}</td>
@endif
