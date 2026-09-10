@props(['label', 'field'])

<div>
    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $label }}</p>
    <p class="mt-1 text-sm text-gray-900" x-text="field('{{ $field }}')"></p>
</div>
