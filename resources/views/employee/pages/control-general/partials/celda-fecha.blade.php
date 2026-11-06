{{-- Celda de fecha con calendario. Variable: $campo. --}}
@if ($editable($campo))
    <td class="px-2 py-1 {{ $celdaManual }}">
        <x-date-input class="{{ $input }} min-w-[6.5rem]" value="{{ $cg->{$campo}?->toDateString() }}"
            x-on:input="cambiar('{{ $campo }}', $event)" />
    </td>
@else
    <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->{$campo}?->format('d/m/Y') ?? '—' }}</td>
@endif
