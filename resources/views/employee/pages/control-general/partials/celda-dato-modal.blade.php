{{--
    Celda de solo texto para un campo que ahora se edita desde el modal de
    Asesor/Cuadrilla/Observaciones (ver celda-observacion-modal). Reactiva a
    "f" para que se actualice sola al guardar el modal, sin recargar la fila.
    Variable: $campo.
--}}
<td class="px-2 py-1.5 {{ $celdaManual }}">
    <span class="block max-w-[10rem] truncate text-gray-700" :title="f.{{ $campo }}" x-text="f.{{ $campo }} || '—'"></span>
</td>
