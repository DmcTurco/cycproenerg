@extends('employee.layouts.user_type.guest')

@section('content')
    <div class="w-full max-w-md">
        <div class="mb-6 flex justify-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.964 0a9 9 0 10-11.964 0m11.964 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-8 shadow-xl ring-1 ring-gray-100">
            <h2 class="mb-1 text-center text-xl font-semibold text-gray-900">Bienvenido de nuevo</h2>
            <p class="mb-6 text-center text-sm text-gray-500">Ingresa tus credenciales para acceder al panel</p>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="form-input pl-10" placeholder="tucorreo@empresa.com" />
                    </div>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </span>
                        <input id="password" type="password" name="password" required
                            class="form-input pl-10" placeholder="••••••••" />
                    </div>
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input id="rememberMe" type="checkbox" name="remember"
                        class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    <label for="rememberMe" class="ml-2 text-sm text-gray-600">Recordarme</label>
                </div>

                <button type="submit" class="btn-brand w-full py-2.5">Iniciar sesión</button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">&copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.</p>
    </div>
@endsection
