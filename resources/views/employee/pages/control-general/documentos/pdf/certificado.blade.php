@php
    $fecha = $datos['fecha'] ? \Carbon\Carbon::parse($datos['fecha'])->format('d / m / Y') : '........ / ........ / ........';
    $filas = max((int) config('documentos_habilitacion.filas_items'), count($datos['items']));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Certificado de garantías {{ $s->numero_suministro }}</title>
@include('employee.pages.control-general.documentos.pdf._estilos')
</head>
<body>

@include('employee.pages.control-general.documentos.pdf._cabecera', ['titulo' => 'CERTIFICADO DE GARANTIAS POR LA INSTALACION INTERNA<br>DE GAS NATURAL<br><span style="font-size: 8.5px; font-weight: normal;">(Usuario de consumo menor o igual a 300m3 al mes)</span>'])

<table class="cab" style="margin-top: 10px;">
    <tr>
        <td class="b">N° {{ $datos['n_certificado'] ?: '..............' }}</td>
        <td style="text-align: right;"><b>Fecha:</b> {{ $fecha }}</td>
    </tr>
</table>

<table class="tabla" style="margin-top: 4px;">
    <tr><td class="sec" colspan="6">EMPRESA INSTALADORA:</td></tr>
    <tr><td class="lbl" style="width: 13%;">Razón Social:</td><td colspan="5" class="b">{{ $empresa['razon_social'] }}</td></tr>
    <tr><td class="lbl">Dirección:</td><td colspan="5">{{ trim($empresa['direccion'] . ' ' . ($empresa['distrito'] ? '- ' . $empresa['distrito'] : '')) }}</td></tr>
    <tr>
        <td class="lbl">N° RUC:</td><td style="width: 22%;" class="b">{{ $empresa['ruc'] }}</td>
        <td class="lbl" style="width: 13%;">N° Registro:</td><td style="width: 20%;" class="b">{{ $empresa['registro'] }}</td>
        <td class="lbl" style="width: 11%;">Categoría:</td><td class="b">{{ $empresa['categoria'] }}</td>
    </tr>
    <tr>
        <td class="lbl">E-mail:</td><td class="b">{{ $empresa['correo'] }}</td>
        <td class="lbl">Teléfono:</td><td class="b">{{ $empresa['telefono'] }}</td>
        <td class="lbl">N° de Póliza:</td><td class="b">{{ $empresa['poliza'] }}</td>
    </tr>
    <tr><td class="sec" colspan="6">INSTALADOR AUTORIZADO:</td></tr>
    <tr><td class="lbl">Nombre:</td><td colspan="5" class="b">{{ $instalador?->nombre }}</td></tr>
    <tr>
        <td class="lbl">N° de DNI o CE:</td><td class="b">{{ $instalador?->numero_documento }}</td>
        <td class="lbl">N° de Registro:</td><td class="b">{{ $instalador?->numero_registro }}</td>
        <td class="lbl">Categoría:</td><td class="b">{{ $instalador?->categoria }}</td>
    </tr>
    <tr><td colspan="6" style="font-size: 8px;">DECLARA HABER: &nbsp; CONSTRUIDO ( <b>X</b> ), &nbsp; MODIFICADO (&nbsp;&nbsp;&nbsp;), &nbsp; AMPLIADO (&nbsp;&nbsp;&nbsp;) &nbsp; LA INSTALACION SIGUIENTE:</td></tr>
    <tr><td class="lbl">Usuario:</td><td colspan="5" class="b">{{ $cliente?->nombre }}</td></tr>
    <tr><td class="lbl">Dirección:</td><td colspan="5" class="b">{{ $direccion }}</td></tr>
    <tr>
        <td class="lbl">Distrito:</td><td class="b">{{ $ubi?->distrito }}</td>
        <td class="lbl">Provincia:</td><td class="b">{{ $ubi?->provincia }}</td>
        <td class="lbl">Departamento:</td><td class="b">{{ $ubi?->departamento }}</td>
    </tr>
    <tr>
        <td class="lbl">Material:</td><td class="b">{{ $datos['material'] }}</td>
        <td class="lbl">Tipo:</td><td class="b">{{ $datos['tipo'] }}</td>
        <td class="lbl">Potencia Nominal:</td><td class="b">{{ $datos['potencia_nominal'] }}</td>
    </tr>
    <tr><td colspan="6" style="font-size: 8px;">Que la misma ha sido efectuada y cumple con todas las disposiciones y normativas de legislación vigente que sean de aplicación tanto en construcción, materiales y ventilación.</td></tr>
</table>

<p style="margin: 10px 0 3px; font-size: 9.5px;">Los Gasodomésticos instalados son:</p>

<table class="tabla">
    <tr class="c b">
        <td style="width: 9%;">Item</td>
        <td style="width: 41%;">Inst. (I) o Proy (P)</td>
        <td style="width: 25%;">Tipo (A) (B) (C)</td>
        <td>Potencia (kW)</td>
    </tr>
    @for ($i = 0; $i < $filas; $i++)
        @php $f = $datos['items'][$i] ?? []; @endphp
        <tr class="c" style="height: 17px;">
            <td>{{ !empty($f) ? $i + 1 : '' }}</td>
            <td>{{ $f['inst'] ?? '' }}</td>
            <td>{{ $f['tipo'] ?? '' }}</td>
            <td>{{ $f['potencia'] ?? '' }}</td>
        </tr>
    @endfor
</table>

<p style="margin-top: 12px; font-size: 8.5px; text-align: justify;">
    La empresa firmante de este documento garantiza por el período de tres años a partir de la fecha contra cualquier deficiente o defectuosa instalación atribuible a una mala ejecución, y que afecte a dicha instalación, a bienes muebles o al inmueble en el que se encuentren, así como toda consecuencia que ella se derive.
</p>

<table class="firmas" style="margin-top: 12px;">
    <tr>
        <td style="width: 50%; height: 110px;">
            <div style="height: 80px;"></div>
            <div class="linea-firma" style="margin-top: 0;">Firma del Instalador</div>
        </td>
        <td style="height: 110px;">
            <div style="height: 80px; text-align: center;">
                @if ($sello)
                    <img src="{{ $sello }}" style="max-height: 78px; max-width: 220px;" alt="">
                @endif
            </div>
            <div class="linea-firma" style="margin-top: 0;">Sello de la Empresa</div>
        </td>
    </tr>
</table>

</body>
</html>
