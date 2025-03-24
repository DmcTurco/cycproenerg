<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    @stack('meta')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets') }}/img/apple-icon.png">
    <link rel="icon" type="image/png" href="{{ asset('assets') }}/img/favicon.png">
    <title>
        C Y C
    </title>
    <!--     Fonts and icons     -->
    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <!-- Nucleo Icons -->
    <link href="{{ asset('assets') }}/css/nucleo-icons.css" rel="stylesheet" />
    <link href="{{ asset('assets') }}/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    {{-- <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <!-- CSS Files -->
    <link id="pagestyle" href="{{ asset('assets') }}/css/material-dashboard.css?v=3.0.0" rel="stylesheet" />
    <!--JQuery -->
    <script src="{{ asset('assets') }}/js/jquery-3.5.1.min.js"></script>
    <script src="{{ asset('assets') }}/js/core/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!--sweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- <i class="fa fa-life-ring" aria-hidden="true"></i> --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    @stack('styles')
</head>

<body class="g-sidenav-show bg-gray-100">
    @auth
        @yield('auth')
    @endauth
    @guest
        @yield('guest')
    @endguest

    <!-- Scripts base -->
    <script src="{{ asset('assets') }}/js/jquery-3.5.1.min.js"></script>
    <script src="{{ asset('assets') }}/js/core/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets') }}/js/core/popper.min.js"></script>
    <script src="{{ asset('assets') }}/js/core/bootstrap.min.js"></script>
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="{{ asset('assets') }}/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="{{ asset('/js/validations.js') }}"></script>

    <!-- Stack para scripts adicionales -->
    @stack('scripts')

    <script>
        // Cargar Google Maps API programáticamente con loading=async
        (function() {
            const script = document.createElement('script');
            script.src =
                'https://maps.googleapis.com/maps/api/js?key=AIzaSyCTuazTT4ftRrTOscHQYPabgJPLiBS9YXc&region=ES&callback=initMap&loading=async';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        })();
    </script>
    <script>
        // Función global que Google Maps llamará cuando se cargue
        function initMap() {
            console.log("Google Maps API cargada correctamente");
            // Si tienes una función initMapHandler, llámala aquí
            if (typeof initMapHandler === 'function') {
                initMapHandler();
            }
        }
    </script>

    <!-- Scripts específicos de la plantilla -->
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <script src="{{ asset('assets') }}/js/material-dashboard.min.js?v=3.0.0"></script>

    @stack('js')
    @stack('dashboard')
</body>

</html>
