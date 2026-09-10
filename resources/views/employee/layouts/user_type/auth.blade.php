@extends('employee.layouts.app')

@section('auth')
    @include('employee.layouts.navbars.auth.sidebar')

    <div class="flex min-h-screen flex-col lg:pl-64">
        @include('employee.layouts.navbars.auth.nav')

        <main @class([
            'flex flex-1 flex-col',
            'p-4 sm:p-6 lg:p-8' => empty($fullBleed),
            'p-2 sm:p-3' => !empty($fullBleed),
        ])>
            <x-flash-alert />
            @yield('content')
        </main>

        @empty($fullBleed)
            @include('employee.layouts.footers.auth.footer')
        @endempty
    </div>
@endsection
