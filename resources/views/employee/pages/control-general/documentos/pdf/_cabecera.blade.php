{{-- Encabezado: logo de la empresa instaladora (o su nombre) y el título. Variable: $titulo (puede traer <br>). --}}
<table class="cab">
    <tr>
        <td style="width: 24%;">
            @if ($logo)
                <img src="{{ $logo }}" style="height: 72px; max-width: 170px;" alt="">
            @else
                <span class="b">{{ $empresa['razon_social'] }}</span>
            @endif
        </td>
        <td class="titulo">{!! $titulo !!}</td>
    </tr>
</table>
