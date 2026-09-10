@props(['colspan', 'message' => 'No se encontraron registros.'])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10 text-center text-sm text-gray-500">
        <div class="flex flex-col items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-2.5 5H6.5L4 13m16 0h-4.5a1 1 0 00-.9.55l-.7 1.4a1 1 0 01-.9.55h-2a1 1 0 01-.9-.55l-.7-1.4a1 1 0 00-.9-.55H4" />
            </svg>
            <span>{{ $message }}</span>
        </div>
    </td>
</tr>
