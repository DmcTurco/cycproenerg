@extends('employee.layouts.app')

@section('guest')
    @include('employee.layouts.navbars.guest.nav')

    <div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
        @yield('content')
    </div>

    @include('employee.layouts.footers.guest.footer')
@endsection
