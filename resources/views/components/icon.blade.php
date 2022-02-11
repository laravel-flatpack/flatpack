@props([
    'icon' => '',
    'size' => 'medium'
])

<div {{ $attributes->class([
    'material-icons',
    'text-xl' => $size === 'small',
    'text-3xl' => $size === 'large',
]) }}>{{ $icon }}</div>
