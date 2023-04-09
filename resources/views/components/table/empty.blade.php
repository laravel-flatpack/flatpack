@aware(['component'])

@php
    $attributes = $attributes->merge(['wire:key' => 'empty-message-'.$component->id]);
    $theme = $component->getTheme();
@endphp

<tr {{ $attributes }}>
    <td colspan="{{ $component->getColspanCount() }}">
        <div class="flex justify-center items-center space-x-2 dark:bg-gray-800">
            <span class="font-medium py-8 text-gray-400 text-lg dark:text-white">{{ $component->getEmptyMessage() }}</span>
        </div>
    </td>
</tr>