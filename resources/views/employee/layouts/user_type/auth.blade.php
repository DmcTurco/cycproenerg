@extends('employee.layouts.app')

@section('auth')
    @include('employee.layouts.navbars.auth.sidebar')

    {{-- fullBleed: en pantallas lg el wrapper toma una altura FIJA (h-screen, no min-h-screen)
         y se recorta (overflow-hidden). Esto es lo que le da a los hijos flex-1 de adentro
         (main, y luego el contenido de la vista) una altura real contra la cual encogerse.
         Sin esto, "flex-1"/"overflow-auto" internos no tienen efecto y todo crece con el
         contenido, empujando el scroll a la ventana completa. --}}
    <div @class([
        'flex min-h-screen flex-col lg:pl-64',
        'lg:h-screen lg:overflow-hidden' => !empty($fullBleed),
    ])>
        @include('employee.layouts.navbars.auth.nav')

        <main @class([
            'flex flex-1 flex-col',
            'p-4 sm:p-6 lg:p-8' => empty($fullBleed),
            'p-2 sm:p-3 lg:min-h-0 lg:overflow-hidden' => !empty($fullBleed),
        ])>
            <x-flash-alert />
            @yield('content')
        </main>

        @empty($fullBleed)
            @include('employee.layouts.footers.auth.footer')
        @endempty
    </div>
@endsection
