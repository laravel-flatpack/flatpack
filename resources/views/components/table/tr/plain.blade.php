@aware(['component'])
@props(['customAttributes' => []])

@php
    $theme = $component->getTheme();
@endphp

<tr {{ $attributes
    ->merge($customAttributes)
    ->class(['bg-white dark:bg-gray-700 dark:text-white' => $customAttributes['default'] ?? true])
    ->except('default')
}}>
    {{ $slot }}
</tr>
