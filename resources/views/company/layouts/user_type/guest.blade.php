@extends('company.layouts.app')

@section('guest')
    @if(\Request::is('login/forgot-password'))
        @include('company.layouts.navbars.guest')
        @yield('content')
    @else
        <div class="container position-sticky z-index-sticky top-0">
            <div class="row">
                <div class="col-12">
                    @include('company.layouts.navbars.guest')
                </div>
            </div>
        </div>
        @yield('content')
        @include('company.layouts.footers.guest')
    @endif
@endsection
