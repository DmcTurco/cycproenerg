{{-- Sin permiso (middleware VerificarPermiso u otro abort(403)). --}}
@extends(auth('employee')->check() ? 'employee.layouts.user_type.auth' : 'employee.layouts.user_type.guest')

@section('content')
    <div class="flex flex-1 items-center justify-center p-6">
        <div class="max-w-md rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <h2 class="mb-2 text-lg font-semibold text-gray-900">Sin permiso</h2>
            <p class="mb-6 text-sm text-gray-600">{{ $exception->getMessage() ?: 'No tiene permiso para ver esta página.' }}</p>
            <a href="{{ auth('employee')->check() ? route('employee.home') : url('/') }}" class="btn-brand">Volver al inicio</a>
        </div>
    </div>
@endsection
