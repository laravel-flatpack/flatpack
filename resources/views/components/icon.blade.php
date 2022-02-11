@props([
    'icon' => '',
    'size' => 'medium'
])

<span {{ $attributes->class([
    'material-icons',
    'text-base' => $size === 'small',
    'text-3xl' => $size === 'large',
]) }}>{{ $icon }}</span>
