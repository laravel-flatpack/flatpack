@props([
    'title' => '',
    'icon' => null,
    'url' => '#',
    'current' => false,
    'type' => 'navbar-item',
])
<div class="w-full block">
    <a href="{{ $url }}" {{ $attributes->class([
        'navbar-menu-item' => $type !== 'button',
        'navbar-menu-button' => $type === 'button',
        'is-active' => $current
    ])}}>
        <span class="navbar-item-icon">
            <x-icon name="{{ $icon }}" style="outline" class="w-[20px] h-[20px]" />
        </span>
        <span class="navbar-item-text">{{ $title }}</span>
    </a>
</div>