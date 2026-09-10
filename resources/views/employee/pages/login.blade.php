@extends('employee.layouts.user_type.guest')

@section('content')
    <div class="w-full max-w-sm">
        <div class="card">
            <h2 class="mb-1 text-xl font-semibold text-gray-900">Bienvenido</h2>
            <p class="mb-6 text-sm text-gray-500">Ingresa tus credenciales para continuar.</p>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="form-input" />
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="form-label">Contraseña</label>
                    <input id="password" type="password" name="password" required class="form-input" />
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input id="rememberMe" type="checkbox" name="remember"
                        class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    <label for="rememberMe" class="ml-2 text-sm text-gray-600">Recordarme</label>
                </div>

                <button type="submit" class="btn-brand w-full">Iniciar sesión</button>
            </form>
        </div>
    </div>
@endsection
