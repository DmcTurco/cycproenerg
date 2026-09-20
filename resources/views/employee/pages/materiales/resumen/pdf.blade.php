<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen {{ $corte['cuadrilla']->nombre }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 14px; margin: 0 0 2px 0; }
        .muted { color: #6b7280; }
        .datos { margin-top: 10px; margin-bottom: 12px; }
        .datos td { padding: 2px 0; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items th { background: #1d4ed8; color: #fff; padding: 5px 6px; font-size: 9.5px; text-align: left; }
        .items td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 9.5px; }
        .items td.num, .items th.num { text-align: right; }
        .items td.center, .items th.center { text-align: center; }
        .total-row td { border-top: 1px solid #9ca3af; font-weight: bold; background: #fef3cd; }
        .nota { margin-top: 8px; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>RESUMEN DE MATERIALES RETIRADOS — CORTE</h1>
    <div class="muted">C&amp;C PROYECTOS INTEGRALES EN ENERGIA S.A.C. · RUC 20604329397</div>

    <table class="datos">
        <tr><td style="width: 90px;"><strong>CUADRILLA:</strong></td><td>{{ $corte['cuadrilla']->nombre }} ({{ $corte['cuadrilla']->tipo }})</td></tr>
        @if ($desde)
            <tr><td><strong>DESDE:</strong></td><td>{{ $desde->format('d/m/Y') }}</td></tr>
        @endif
        <tr><td><strong>HASTA:</strong></td><td>{{ $corte['hasta']->format('d/m/Y') }}</td></tr>
    </table>

    @if ($corte['tipo'] === 'personal_directo')
        <p><strong>LIQUIDACION DE MATERIALES DE {{ $corte['cuadrilla']->nombre }}</strong> (acumulado al {{ $corte['hasta']->format('d/m/Y') }})</p>

        <table class="items">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="center">Unid.</th>
                    <th class="num">Retirado (vales)</th>
                    <th class="num">Ejecutado</th>
                    <th class="num">Devuelto</th>
                    <th class="num">Saldo en su poder</th>
                    <th class="num">P. unit. S/ (costo)</th>
                    <th class="num">Valor S/</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($corte['items'] as $item)
                    <tr>
                        <td>{{ $item['material']->codigo }}</td>
                        <td>{{ $item['material']->descripcion }}</td>
                        <td class="center">{{ $item['unidad'] }}</td>
                        <td class="num">{{ number_format($item['retirado'], 2) }}</td>
                        <td class="num">{{ number_format($item['ejecutado'], 2) }}</td>
                        <td class="num">{{ number_format($item['devuelto'], 2) }}</td>
                        <td class="num">{{ number_format($item['saldo'], 2) }}</td>
                        <td class="num">{{ number_format($item['precio_unitario'], 2) }}</td>
                        <td class="num">{{ number_format($item['valor'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="8" style="text-align: right;">VALOR DEL SALDO EN SU PODER (a costo, sin IGV) S/</td>
                    <td class="num">{{ number_format($corte['valor_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <p class="nota">SALDO &gt; 0: material en su poder o por sustentar (se valoriza a costo). SALDO &lt; 0: usó material que no retiró con vale, NO va a su favor: VALOR = 0 (revisar).</p>
    @else
        <p><strong>COTIZACIONES PENDIENTES DE DESCUENTO</strong> (emitidas hasta el {{ $corte['hasta']->format('d/m/Y') }})</p>

        <table class="items">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>N° Cotización</th>
                    <th>Concepto</th>
                    <th class="num">Monto S/IGV</th>
                    <th class="num">IGV</th>
                    <th class="num">Total C/IGV</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($corte['cotizaciones'] as $cot)
                    <tr>
                        <td>{{ $cot->fecha->format('d/m/Y') }}</td>
                        <td>{{ $cot->numero }}</td>
                        <td>Materiales de obra según cotización</td>
                        <td class="num">{{ number_format($cot->monto_sin_igv, 2) }}</td>
                        <td class="num">{{ number_format($cot->igv, 2) }}</td>
                        <td class="num">{{ number_format($cot->total_con_igv, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">TOTAL A DESCONTAR EN LA VALORIZACION</td>
                    <td class="num">{{ number_format($corte['totales']['monto_sin_igv'], 2) }}</td>
                    <td class="num">{{ number_format($corte['totales']['igv'], 2) }}</td>
                    <td class="num">{{ number_format($corte['totales']['total_con_igv'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <p class="nota">El detalle ítem por ítem está en cada cotización (puede adjuntarse como anexo de este PDF).</p>
    @endif
</body>
</html>
