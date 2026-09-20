<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de entrega de materiales</title>
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
    </style>
</head>
<body>
    <h1>FORMATO DE ENTREGA DE MATERIALES</h1>
    <div class="muted">C&amp;C PROYECTOS INTEGRALES EN ENERGIA S.A.C. · RUC 20604329397</div>

    <table class="campos">
        <tr>
            <td style="width: 50%;"><strong>FECHA:</strong> ______ / ______ / ________</td>
            <td><strong>ENTREGADO A:</strong> {{ $cuadrilla->nombre ?? '' }} _________________________________</td>
        </tr>
        <tr>
            <td><strong>CUADRILLA / EMPRESA:</strong> {{ $cuadrilla ? ($cuadrilla->empresas->pluck('codigo')->join('/') ?: $cuadrilla->nombre) : '' }} ______________________________</td>
            <td>
                <strong>TIPO:</strong>
                [{{ $cuadrilla && $cuadrilla->tipo === 'CONTRATISTA' ? 'X' : ' ' }}] CONTRATISTA
                &nbsp;&nbsp;
                [{{ $cuadrilla && $cuadrilla->tipo === 'PERSONAL DIRECTO' ? 'X' : ' ' }}] PERSONAL DIRECTO
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="num">N°</th>
                <th style="width: 80px;">Código</th>
                <th>Descripción</th>
                <th class="num" style="width: 55px;">Cant.</th>
                <th style="width: 130px;">Unid. / Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $n)
                <tr>
                    <td class="num">{{ $n }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="nota">El material entregado a CONTRATISTA se cotiza y descuenta en su valorización. El entregado a PERSONAL DIRECTO se sustenta con su reporte de ejecución y la liquidación del resumen.</p>

    <table class="firmas">
        <tr>
            <td style="width: 50%;">ENTREGÓ (Almacén): ____________________ &nbsp; FIRMA: __________</td>
            <td>RECIBÍ CONFORME: ____________________ &nbsp; DNI: __________ &nbsp; FIRMA: __________</td>
        </tr>
    </table>
</body>
</html>
