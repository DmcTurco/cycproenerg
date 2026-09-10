@extends('employee.layouts.app')

@section('auth')
    @include('employee.layouts.navbars.auth.sidebar')

    <div class="lg:pl-64">
        @include('employee.layouts.navbars.auth.nav')

        <main class="p-4 sm:p-6 lg:p-8">
            <x-flash-alert />
            @yield('content')
        </main>

        @include('employee.layouts.footers.auth.footer')
    </div>
@endsection
