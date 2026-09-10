<x-app-shell :title="$title ?? 'CYC PROENERG'">
    @auth('company')
        @yield('auth')
    @endauth
    @guest('company')
        @yield('guest')
    @endguest
</x-app-shell>
