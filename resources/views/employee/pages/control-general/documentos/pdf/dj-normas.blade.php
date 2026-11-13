@php
    $fecha = $datos['fecha'] ? \Carbon\Carbon::parse($datos['fecha'])->format('d / m / Y') : '........ / ........ / ........';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>DJ de normas técnicas {{ $s->numero_suministro }}</title>
@include('employee.pages.control-general.documentos.pdf._estilos')
</head>
<body>

@include('employee.pages.control-general.documentos.pdf._cabecera', ['titulo' => 'DECLARACIÓN JURADA DE CUMPLIMIENTO DE NORMAS TÉCNICAS Y DE SEGURIDAD, PARA INSTALACIONES INTERNAS DE GAS NATURAL PARA USUARIOS CON CONSUMOS MENORES O IGUALES A 300 m3/mes'])

<p style="margin-top: 22px;"><i>Por medio de la presente:</i></p>

<p style="margin-top: 20px;">
    La Empresa Instaladora <span class="lin">{{ $empresa['razon_social'] }}</span><br>
    Con N° de registro <span class="lin">{{ $empresa['registro'] }}</span> a través de su instalador:
</p>

<p class="c" style="margin-top: 8px;"><span class="lin" style="padding: 0 40px;">{{ $instalador?->nombre }}</span></p>

<p style="margin-top: 14px;">
    Identificado con DNI/CE N° <span class="lin">{{ $instalador?->numero_documento }}</span>
    y N° registro <span class="lin">{{ $instalador?->numero_registro }}</span>
</p>

<p style="margin-top: 14px;">declara que las instalaciones Internas para gas natural realizadas en la dirección</p>

<p class="c" style="margin-top: 6px;"><span class="lin" style="padding: 0 20px;">{{ $direccion }}</span></p>

<p style="margin-top: 16px;">Fueron ejecutadas en cumplimiento de las siguientes normas técnicas y de seguridad vigentes:</p>

<p style="margin-top: 10px;">
    - NTP 111.001 (v.2014) " Gas natural Seco: Sistema de tuberías para instalaciones internas residenciales y comerciales"
    - Norma Técnica de Edificación EM 040:" Instalaciones de Gas"
</p>

<p style="margin-top: 50px; margin-left: 56%;">Lugar: <span class="lin" style="padding: 0 30px;">{{ $datos['lugar'] }}</span></p>
<p style="margin-top: 14px; margin-left: 56%;">Fecha: {{ $fecha }}</p>

<div style="margin-top: 60px; width: 240px;">
    <div style="border-top: 1px solid #000; padding-top: 3px; font-size: 9.5px;">Firma y/o sello del instalador</div>
    <p class="campo">DNI/CE: {{ $instalador?->numero_documento }}</p>
</div>

</body>
</html>
