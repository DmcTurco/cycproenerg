<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $cotizacion->numero }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 15px; margin: 0 0 2px 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 0; }
        .doc-box { border: 1px solid #9ca3af; border-radius: 4px; padding: 8px; text-align: center; width: 180px; }
        .doc-box .titulo { font-weight: bold; font-size: 13px; }
        .doc-box .numero { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .datos-cliente { margin-top: 14px; margin-bottom: 10px; }
        .datos-cliente td { padding: 2px 0; }
        .items { margin-top: 10px; }
        .items th { background: #1d4ed8; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
        .items td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; }
        .items td.num, .items th.num { text-align: right; }
        .totales { margin-top: 10px; width: 240px; float: right; }
        .totales td { padding: 3px 8px; }
        .totales .total-final td { border-top: 1px solid #9ca3af; font-weight: bold; font-size: 12px; }
        .footer-note { margin-top: 60px; clear: both; font-size: 9.5px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <h1>C&amp;C PROYECTOS INTEGRALES EN ENERGIA S.A.C.</h1>
                <div class="muted">RUC 20604329397 · Almacén: Av. Prolongación Chillón Mz. A · www.cycproenerg.com</div>
            </td>
            <td style="width: 190px;">
                <div class="doc-box">
                    <div class="titulo">{{ $cotizacion->es_vale ? 'VALE DE ENTREGA DE MATERIALES' : 'COTIZACIÓN' }}</div>
                    @if ($cotizacion->es_vale)
                        <div class="muted" style="font-size: 9px;">(uso interno, precio costo)</div>
                    @endif
                    <div class="numero">{{ $cotizacion->numero }}</div>
                    <div class="muted">{{ $cotizacion->fecha->format('d/m/Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="datos-cliente">
        <tr><td style="width: 90px;"><strong>CUADRILLA:</strong></td><td>{{ $cotizacion->cuadrilla->nombre ?? '—' }}</td></tr>
        <tr><td><strong>TIPO:</strong></td><td>{{ $cotizacion->cuadrilla->tipo ?? '—' }}</td></tr>
        @if (!$cotizacion->es_vale)
            <tr><td><strong>F. PAGO:</strong></td><td>DESCUENTO EN VALORIZACIÓN</td></tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 30px;">Item</th>
                <th>Código</th>
                <th>Descripción</th>
                <th class="num" style="width: 55px;">Cant.</th>
                <th class="num" style="width: 70px;">P. Unit. S/IGV</th>
                <th class="num" style="width: 70px;">Total S/IGV</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cotizacion->detalles as $index => $detalle)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detalle->material->codigo ?? '—' }}</td>
                    <td>{{ $detalle->material->descripcion ?? '—' }}</td>
                    <td class="num">{{ number_format($detalle->cantidad, 2) }}</td>
                    <td class="num">{{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="num">{{ number_format($detalle->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr><td>SUBTOTAL S/IGV</td><td class="num" style="text-align: right;">{{ number_format($cotizacion->monto_sin_igv, 2) }}</td></tr>
        @if (!$cotizacion->es_vale)
            <tr><td>IGV ({{ number_format($parametros->igv * 100, 0) }}%)</td><td class="num" style="text-align: right;">{{ number_format($cotizacion->igv, 2) }}</td></tr>
        @endif
        <tr class="total-final"><td>TOTAL</td><td class="num" style="text-align: right;">{{ number_format($cotizacion->total_con_igv, 2) }}</td></tr>
    </table>

    <div class="footer-note">
        @if ($cotizacion->es_vale)
            Registrado como USO INTERNO (no se cobra). El stock baja cuando se registre lo ejecutado.
        @else
            Registrada como PENDIENTE de descuento en valorización. El stock quedó descontado automáticamente al emitir este documento.
        @endif
        @if ($cotizacion->observacion)
            <br>{{ $cotizacion->observacion }}
        @endif
    </div>
</body>
</html>
