{{--
    Campo de fecha con calendario (flatpickr, resources/js/datepicker.js).
    Muestra dd/mm/aaaa en español; el valor que se envía / x-model sigue
    siendo aaaa-mm-dd. Acepta los mismos atributos que un <input>:

        <x-date-input name="fecha" value="{{ now()->toDateString() }}" required />
        <x-date-input id="fecha" x-model="form.fecha" />
--}}
{{-- Sin "class" usa el estilo estándar (form-input); con "class" se respeta la que se pase. --}}
<input type="date" data-datepicker {{ $attributes->has('class') ? $attributes : $attributes->merge(['class' => 'form-input']) }} />
