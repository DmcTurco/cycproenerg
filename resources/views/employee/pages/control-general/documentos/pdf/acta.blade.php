@php
    // Marca de casilla: "X" o vacío.
    $x = fn ($si) => $si ? 'X' : '';
    $fecha = $s->fecha_registro_portal ? \Carbon\Carbon::parse($s->fecha_registro_portal)->format('d/m/Y') : '';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Acta de habilitación {{ $s->numero_suministro }}</title>
<style>
    @page { margin: 22px 26px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #000; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    td, th { border: 1px solid #000; padding: 2px 4px; vertical-align: middle; overflow: hidden; }
    .sec { background: #d9d9d9; font-weight: bold; text-align: left; }
    .c { text-align: center; }
    .b { font-weight: bold; }
    .lbl { white-space: nowrap; }
    .val { font-weight: bold; }
    .sin-borde td { border: none; }
    .gap { height: 4px; border: none; }
    .alto td { height: 17px; }
    .box { width: 14px; text-align: center; font-weight: bold; font-size: 9px; }
    .firma { height: 62px; }
</style>
</head>
<body>

{{-- Encabezado --}}
<table>
    <tr>
        <td style="width: 21%; text-align: center;"><img src="{{ public_path('images/acta-logo.jpeg') }}" style="width: 105px;" alt=""></td>
        <td class="c b" style="font-size: 10px; padding: 8px;">
            SOLICITUD DE HABILITACIÓN DE INSTALACIONES INTERNAS DE GAS NATURAL PARA USUARIOS RESIDENCIALES Y COMERCIALES
        </td>
    </tr>
</table>
<div class="gap"></div>

{{-- 1. Datos generales --}}
<table>
    <tr><td class="sec c" colspan="6">DATOS GENERALES</td></tr>
    <tr class="alto">
        <td class="lbl" style="width: 13%;">N° CONTRATO:</td>
        <td class="val c" style="width: 20%;">{{ $s->numero_suministro }}</td>
        <td class="lbl" style="width: 14%;">N° INSTALACIÓN:</td>
        <td class="val c" style="width: 20%;">{{ $datos['n_instalacion'] }}</td>
        <td class="lbl" style="width: 15%;">FECHA DE INGRESO:</td>
        <td class="val c">{{ $fecha }}</td>
    </tr>
</table>
<div class="gap"></div>

{{-- Tipo de solicitud --}}
<table>
    <tr><td style="width: 22.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td><td style="width: 4.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td><td style="width: 24.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td><td style="width: 4.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td><td style="width: 22.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td><td style="width: 4.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td><td style="width: 20.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td></tr>
    <tr class="alto">
        <td>Habilitación del servicio</td><td class="box">X</td>
        <td>Modificación y/o Ampliación</td><td class="box"></td>
        <td>Conexión de punto previsto</td><td class="box"></td>
        <td>Otros motivos: ………………</td>
    </tr>
</table>
<div class="gap"></div>

{{-- 2. Domicilio de la instalación --}}
<table>
    <tr><td class="sec" colspan="8">1.   UBICACIÓN DE LA INSTALACIÓN</td></tr>
    <tr class="alto">
        <td class="lbl" style="width: 11%;">Jr./Av./Calle:</td>
        <td class="val" colspan="3">{{ $direccion }}</td>
        <td class="lbl" style="width: 6%;">N°:</td><td style="width: 7%;"></td>
        <td class="lbl" style="width: 6%;">Piso:</td><td class="val c" style="width: 6%;">{{ $piso }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Urbanización:</td>
        <td class="val" colspan="3">{{ $direccion }}</td>
        <td class="lbl" colspan="2">Dpto.:</td><td class="val c" colspan="2">{{ $dpto }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Distrito:</td><td class="val">{{ $ubi?->distrito }}</td>
        <td class="lbl" style="width: 11%;">Provincia:</td><td class="val">{{ $ubi?->provincia }}</td>
        <td class="lbl" colspan="2">Cód. Postal:</td><td colspan="2"></td>
    </tr>
</table>
<div class="gap"></div>

{{-- 3. Solicitante --}}
<table>
    <tr><td class="sec" colspan="6">2.   DATOS DEL SOLICITANTE</td></tr>
    <tr class="alto">
        <td class="lbl" style="width: 17%;">Apellidos y Nombres:</td>
        <td class="val" colspan="5">{{ $esRuc ? '' : $cliente?->nombre }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">DNI/CE:</td><td class="val" colspan="5">{{ $esRuc ? '' : $documento }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Teléf.:</td><td></td>
        <td class="lbl" style="width: 9%;">Celular:</td><td class="val">{{ $cliente?->celular }}</td>
        <td class="lbl" style="width: 8%;">e-mail:</td><td class="val">{{ $cliente?->correo_electronico }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Razón Social:</td><td class="val" colspan="3">{{ $esRuc ? $cliente?->nombre : '' }}</td>
        <td class="lbl">R.U.C.:</td><td class="val">{{ $esRuc ? $documento : '' }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Representante Legal:</td><td colspan="5"></td>
    </tr>
    <tr class="alto">
        <td class="lbl">Teléf.:</td><td></td>
        <td class="lbl">Celular:</td><td></td>
        <td class="lbl">e-mail:</td><td></td>
    </tr>
</table>
<div class="gap"></div>

{{-- 4. Instalador --}}
<table>
    <tr><td class="sec" colspan="6">3.   DATOS DEL INSTALADOR</td></tr>
    <tr class="alto">
        <td class="lbl" style="width: 17%;">Apellidos y Nombres:</td>
        <td class="val c" colspan="5">{{ $instalador?->nombre }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">DNI/CE:</td><td class="val c">{{ $instalador?->numero_documento }}</td>
        <td class="lbl" style="width: 11%;">Categoría:</td><td class="val c">{{ $instalador?->categoria }}</td>
        <td class="lbl" style="width: 10%;">Registro:</td><td class="val c">{{ $instalador?->numero_registro }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Dirección:</td><td class="val c" colspan="5">{{ $instalador?->direccion }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Distrito:</td><td class="val c">{{ $instalador?->distrito }}</td>
        <td class="lbl">Provincia:</td><td class="val c">{{ $instalador ? 'LIMA' : '' }}</td>
        <td class="lbl">Departamento:</td><td class="val c">{{ $instalador ? 'LIMA' : '' }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Razón Social:</td><td class="val c" colspan="3">{{ $empresa['razon_social'] ?? '' }}</td>
        <td class="lbl">R.U.C.:</td><td class="val c">{{ $empresa['ruc'] ?? '' }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Categoría:</td><td class="val c">{{ $empresa['categoria'] ?? '' }}</td>
        <td class="lbl">Registro:</td><td class="val c" colspan="3">{{ $empresa['registro'] ?? '' }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Dirección:</td><td class="val c" colspan="5">{{ $empresa['direccion'] ?? '' }}</td>
    </tr>
    <tr class="alto">
        <td class="lbl">Distrito:</td><td class="val c">{{ $empresa['distrito'] ?? '' }}</td>
        <td class="lbl">Provincia:</td><td class="val c">{{ $empresa['provincia'] ?? '' }}</td>
        <td class="lbl">Departamento:</td><td class="val c">{{ $empresa['departamento'] ?? '' }}</td>
    </tr>
</table>
<div class="gap"></div>

{{-- 5. Instalación interna --}}
<table>
    <tr>
        <td style="width: 13%; padding: 0; border: 0; height: 0;"></td><td style="width: 5%; padding: 0; border: 0; height: 0;"></td>
        <td style="width: 13%; padding: 0; border: 0; height: 0;"></td><td style="width: 5%; padding: 0; border: 0; height: 0;"></td>
        <td style="width: 13%; padding: 0; border: 0; height: 0;"></td><td style="width: 5%; padding: 0; border: 0; height: 0;"></td>
        <td style="width: 13%; padding: 0; border: 0; height: 0;"></td><td style="width: 5%; padding: 0; border: 0; height: 0;"></td>
        <td style="width: 19%; padding: 0; border: 0; height: 0;"></td><td style="width: 9%; padding: 0; border: 0; height: 0;"></td>
    </tr>
    <tr><td class="sec" colspan="10">4.   INSTALACIÓN INTERNA</td></tr>
    <tr>
        <td class="b" colspan="2">Uso de la instalación</td>
        <td class="b" colspan="4">Tipo de instalación</td>
        <td class="b" colspan="4">Material de instalación</td>
    </tr>
    <tr class="alto">
        <td>Residencial</td><td class="box">{{ $x(!$esComercial) }}</td>
        <td>A la vista</td><td class="box">{{ $x($aLaVista) }}</td>
        <td>Empotrada</td><td class="box">{{ $x($empotrada) }}</td>
        <td>PEALPE</td><td class="box">{{ $x($datos['material'] === 'PEALPE') }}</td>
        <td>COBRE</td><td class="box">{{ $x($datos['material'] === 'COBRE') }}</td>
    </tr>
    <tr class="alto">
        <td>Comercial</td><td class="box">{{ $x($esComercial) }}</td>
        <td colspan="4"></td>
        <td>ACERO</td><td class="box">{{ $x($datos['material'] === 'ACERO') }}</td>
        <td colspan="2"></td>
    </tr>
</table>
<div class="gap"></div>

{{-- 6. Puntos de consumo --}}
@php
    $filas = max((int) config('documentos_habilitacion.filas_puntos'), $puntos, count($datos['puntos']));
@endphp
<table>
    <tr>
        @foreach ([5, 18, 14, 9, 17, 8, 5, 5, 9, 10] as $ancho)
            <td style="width: {{ $ancho }}%; padding: 0; border: 0; height: 0;"></td>
        @endforeach
    </tr>
    <tr><td class="sec" colspan="10">5.   DETALLE DE LOS PUNTOS DE CONSUMO A CONECTAR Y CONECTADOS</td></tr>
    <tr class="c b">
        <td rowspan="2">Item</td>
        <td rowspan="2">Artefacto</td>
        <td rowspan="2">Estado del punto (Conectado / Pendiente)</td>
        <td rowspan="2">Potencia (kW)</td>
        <td rowspan="2">Ambiente de ubicación del artefacto</td>
        <td rowspan="2">Volumen (m3)</td>
        <td colspan="2">Confinado</td>
        <td colspan="2">Rejilla (cm2)</td>
    </tr>
    <tr class="c b"><td>SI</td><td>NO</td><td>Sup.</td><td>Inf.</td></tr>
    @for ($i = 0; $i < $filas; $i++)
        @php $f = $datos['puntos'][$i] ?? []; @endphp
        <tr class="alto c">
            <td>{{ ($i + 1 <= $puntos || !empty($f)) ? $i + 1 : '' }}</td>
            <td>{{ $f['artefacto'] ?? '' }}</td>
            <td>{{ $f['estado'] ?? '' }}</td>
            <td>{{ $f['potencia'] ?? '' }}</td>
            <td>{{ $f['ambiente'] ?? '' }}</td>
            <td>{{ $f['volumen'] ?? '' }}</td>
            <td class="b">{{ ($f['confinado'] ?? '') === 'SI' ? 'X' : '' }}</td>
            <td class="b">{{ ($f['confinado'] ?? '') === 'NO' ? 'X' : '' }}</td>
            <td>{{ $f['rejilla_sup'] ?? '' }}</td>
            <td>{{ $f['rejilla_inf'] ?? '' }}</td>
        </tr>
    @endfor
</table>
<div class="gap"></div>

{{-- 7. Documentos presentados --}}
<table>
    <tr><td style="width: 94.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td><td style="width: 6.0%; padding: 0; border: 0; height: 0; line-height: 0;"></td></tr>
    <tr><td class="sec" colspan="2">6.   DOCUMENTOS PRESENTADOS</td></tr>
    <tr class="alto"><td>a) Plano de instalación de acuerdo a Obra (isométrico y de vista en planta)</td><td class="box">X</td></tr>
    <tr class="alto"><td>b) Declaración jurada del Instalador, señalando haber construido las Instalaciones de acuerdo con el proyecto Típico o el Proyecto No Típico aprobado</td><td class="box">X</td></tr>
    <tr class="alto"><td>c) Copia del Certificado de Fabricación de los materiales utilizados</td><td class="box"></td></tr>
</table>
<div class="gap"></div>

{{-- Firmas --}}
<table>
    <tr class="c b">
        <td style="width: 50%;">REPRESENTANTE DEL CONCESIONARIO</td>
        <td>SOLICITANTE</td>
    </tr>
    <tr class="firma c"><td>Firma y Sello</td><td>Firma y Sello</td></tr>
    <tr class="alto"><td>Apellidos y Nombres:</td><td>Apellidos y Nombres: {{ $esRuc ? '' : $cliente?->nombre }}</td></tr>
    <tr class="alto"><td>DNI/CE:</td><td>DNI/CE: {{ $esRuc ? '' : $documento }}</td></tr>
    <tr class="alto"><td>N° Registro:</td><td>Razón Social: {{ $esRuc ? $cliente?->nombre : '' }}</td></tr>
</table>

</body>
</html>
