<x-app-shell :title="$title ?? 'CYC PROENERG'">
    @auth('employee')
        @yield('auth')
    @endauth
    @guest('employee')
        @yield('guest')
    @endguest
</x-app-shell>
