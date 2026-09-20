<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de entrega de herramientas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 14px; margin: 0 0 2px 0; text-align: center; }
        .muted { color: #6b7280; text-align: center; font-size: 9.5px; }
        .campos { margin-top: 16px; width: 100%; border-collapse: collapse; }
        .campos td { padding: 4px 0; font-size: 11px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .items th { background: #1d4ed8; color: #fff; padding: 5px 4px; font-size: 9.5px; text-align: left; }
        .items td { padding: 7px 4px; border-bottom: 1px solid #e5e7eb; font-size: 10px; height: 18px; }
        .items td.num, .items th.num { text-align: center; width: 30px; }
        .nota { margin-top: 10px; font-size: 9px; color: #6b7280; }
        .firmas { margin-top: 40px; width: 100%; border-collapse: collapse; }
        .firmas td { padding: 4px 0; font-size: 10.5px; border-top: 1px solid #9ca3af; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($paginas as $pagina)
        <h1>FORMATO DE ENTREGA DE HERRAMIENTAS</h1>
        <div class="muted">C&amp;C PROYECTOS INTEGRALES EN ENERGIA S.A.C. · RUC 20604329397</div>

        <table class="campos">
            <tr>
                <td style="width: 50%;"><strong>FECHA:</strong> ______ / ______ / ________</td>
                <td><strong>ENTREGADO A:</strong> {{ $cuadrilla->nombre ?? '' }} _________________________________</td>
            </tr>
            <tr>
                <td><strong>CUADRILLA:</strong> {{ $cuadrilla->nombre ?? '' }} ______________________________</td>
                <td><strong>FECHA PREVISTA DE DEVOLUCIÓN:</strong> ______ / ______ / ________</td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="num">N°</th>
                    <th style="width: 65px;">Código (HER-)</th>
                    <th>Descripción</th>
                    <th style="width: 100px;">Marca / N° serie</th>
                    <th style="width: 70px;">Estado</th>
                    <th style="width: 110px;">Observación</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 12; $i++)
                    @php($h = $pagina->get($i))
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        <td>{{ $h?->codigo }}</td>
                        <td>{{ $h?->descripcion }}</td>
                        <td>{{ trim(($h?->marca_modelo ?? '') . ($h?->numero_serie ? ' / ' . $h->numero_serie : '')) }}</td>
                        <td>{{ $h?->estado }}</td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <p class="nota">COMPROMISO: el receptor declara recibir las herramientas en el estado indicado y se compromete a devolverlas al almacén. En caso de pérdida o deterioro por mal uso, se descontará según el valor del catálogo. Registrar también el movimiento en Entregas (CM-7).</p>

        <table class="firmas">
            <tr>
                <td style="width: 50%;">ENTREGÓ (Almacén): ____________________ &nbsp; FIRMA: __________</td>
                <td>RECIBÍ CONFORME: ____________________ &nbsp; DNI: __________ &nbsp; FIRMA: __________</td>
            </tr>
        </table>

        @unless ($loop->last)
            <div class="page-break"></div>
        @endunless
    @endforeach
</body>
</html>
