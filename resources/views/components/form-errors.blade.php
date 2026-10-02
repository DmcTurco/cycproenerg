{{--
    Errores de validación del servidor para formularios normales (no Alpine):
    el controlador valida y redirige de vuelta con $errors. Uso: <x-form-errors />
--}}
@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200']) }} role="alert">
        <p class="mb-1 font-semibold">Revise lo siguiente:</p>
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
