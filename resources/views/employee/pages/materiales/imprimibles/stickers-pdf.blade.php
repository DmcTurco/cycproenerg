<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stickers de herramientas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; margin: 0; }
        table.hoja { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.hoja td { width: 33.33%; padding: 4px; vertical-align: top; }
        .sticker { border: 1px dashed #9ca3af; border-radius: 4px; padding: 6px; height: 95px; text-align: center; }
        .sticker .linea1 { font-size: 7.5px; color: #6b7280; letter-spacing: 0.3px; }
        .sticker .codigo { font-size: 13px; font-weight: bold; margin-top: 2px; }
        .sticker .desc { font-size: 8.5px; margin-top: 2px; }
        .sticker .extra { font-size: 8px; color: #374151; margin-top: 1px; }
        .sticker .resp { font-size: 8px; margin-top: 3px; border-top: 1px solid #d1d5db; padding-top: 2px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($paginas as $pagina)
        <table class="hoja">
            @foreach ($pagina->chunk(3) as $fila)
                <tr>
                    @foreach ($fila as $h)
                        <td>
                            <div class="sticker">
                                <div class="linea1">C&amp;C PROENERG</div>
                                <div class="codigo">{{ $h->codigo }}</div>
                                <div class="desc">{{ $h->descripcion }}</div>
                                @if ($h->marca_modelo)
                                    <div class="extra">{{ $h->marca_modelo }}</div>
                                @endif
                                @if ($h->numero_serie)
                                    <div class="extra">SERIE: {{ $h->numero_serie }}</div>
                                @endif
                                <div class="resp">RESP.: {{ $h->responsableActual() ?? '________________' }}</div>
                            </div>
                        </td>
                    @endforeach
                    @for ($i = $fila->count(); $i < 3; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>
        @unless ($loop->last)
            <div class="page-break"></div>
        @endunless
    @endforeach
</body>
</html>
