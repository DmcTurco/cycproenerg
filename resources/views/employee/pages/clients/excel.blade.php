@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Carga de Excel</h2>
            <a href="{{ route('employee.client.index') }}" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Volver</a>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
            @include('employee.pages.clients.form')
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/excel-uploader.js')
    @endpush
@endsection
