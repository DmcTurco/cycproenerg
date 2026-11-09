<form method="GET" action="{{ route('employee.materiales.index') }}" class="flex min-w-[16rem] flex-1 items-center gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}" />
    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por código o descripción"
        class="w-full max-w-sm rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
    <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
    @if ($search)
        <a href="{{ route('employee.materiales.index', ['tab' => $tab]) }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
    @endif
</form>
