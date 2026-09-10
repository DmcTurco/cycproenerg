@props(['title' => 'CYC PROENERG'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body x-data class="bg-surface text-gray-900 antialiased">
    {{ $slot }}

    @livewireScripts
    @stack('scripts')
</body>
</html>
