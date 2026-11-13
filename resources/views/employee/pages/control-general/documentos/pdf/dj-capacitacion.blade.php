@php
    $fecha = $datos['fecha'] ? \Carbon\Carbon::parse($datos['fecha'])->format('d / m / Y') : '........ / ........ / ........';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>DJ de capacitación y tenencia {{ $s->numero_suministro }}</title>
@include('employee.pages.control-general.documentos.pdf._estilos')
</head>
<body>

@include('employee.pages.control-general.documentos.pdf._cabecera', ['titulo' => 'DECLARACION JURADA DE CAPACITACION<br>Y TENENCIA DEL GASODOMESTICO'])

<p class="fecha"><b>Fecha:</b> {{ $fecha }}</p>

<p style="margin-top: 6px;">
    Señores: OSINERGMIN<br>
    Calle Bernardo Monteagudo 222 Magdalena<br>
    Lima, Perú
</p>

<p style="margin-top: 22px;">Por medio de la presente declaro que:</p>

<p style="margin-top: 14px;">
    Yo, <span class="lin">{{ $cliente?->nombre }}</span>
    &nbsp;&nbsp; DNI / C.E. N° <span class="lin">{{ $documento }}</span>
</p>

<p style="margin-top: 10px;">soy propietario de la vivienda ubicada <span class="lin">{{ $direccion }}</span></p>

<p style="margin-top: 10px;">cuyo suministro de gas natural es el N° <span class="lin">{{ $s->numero_suministro }}</span></p>

<p style="margin-top: 10px;">y he recibido la capacitación sobre:</p>
<ul style="margin: 6px 0 0 18px; padding: 0;">
    <li>Seguridad y acciones en caso de emergencias.</li>
    <li>Uso adecuado de la cocina a gas natural.</li>
    <li>Procedimiento de recaudación del Financiamiento FISE.</li>
</ul>

<p style="margin-top: 16px;">
    La capacitación ha sido realizada por el Sr. <span class="lin">{{ $instalador?->nombre }}</span>
    identificado con DNI / C.E. N° <span class="lin">{{ $instalador?->numero_documento }}</span>.
    Por lo tanto, me responsabilizo por los daños que se pueda producir por una mala manipulación de la instalación o de la cocina adecuada a gas natural.
</p>

<table class="firmas" style="margin-top: 20px;">
    <tr>
        <td style="width: 50%;">
            <div class="linea-firma">Firma Propietario o Representante de la Vivienda</div>
            <p class="campo">Nombres: {{ $esRuc ? '' : $cliente?->nombre }}</p>
            <p class="campo">Apellidos:</p>
            <p class="campo">DNI / CE: {{ $esRuc ? '' : $documento }}</p>
        </td>
        <td>
            <div class="linea-firma">Firma y Sello del Instalador de Gas Natural</div>
            <p class="campo">Nombres: {{ $instalador?->nombre }}</p>
            <p class="campo">Apellidos:</p>
            <p class="campo">DNI / CE: {{ $instalador?->numero_documento }}</p>
        </td>
    </tr>
</table>

</body>
</html>
