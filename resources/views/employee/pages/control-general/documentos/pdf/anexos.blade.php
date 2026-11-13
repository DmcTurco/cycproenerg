@php
    // Cada diseño típico elegido es una página completa (imagen ajustada al A4).
    $paginas = [];
    foreach ($datos['anexos'] as $clave) {
        $ruta = resource_path('documentos/anexos/' . ($cfg['anexos'][$clave]['archivo'] ?? ''));
        if (!is_file($ruta)) {
            continue;
        }
        [$ancho, $alto] = getimagesize($ruta);
        $escala = min(700 / $ancho, 1010 / $alto);
        $paginas[] = ['ruta' => $ruta, 'w' => (int) round($ancho * $escala), 'h' => (int) round($alto * $escala)];
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Anexo diseño típico {{ $s->numero_suministro }}</title>
<style>
    @page { margin: 24px 30px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; }
    .pagina { text-align: center; }
    .salto { page-break-before: always; }
</style>
</head>
<body>
@forelse ($paginas as $i => $p)
    <div class="pagina {{ $i > 0 ? 'salto' : '' }}">
        <img src="{{ $p['ruta'] }}" style="width: {{ $p['w'] }}px; height: {{ $p['h'] }}px;" alt="">
    </div>
@empty
    <p style="margin-top: 40px; text-align: center; color: #666;">
        Elige al menos un diseño típico en el formulario para que salga en este anexo.
    </p>
@endforelse
</body>
</html>
