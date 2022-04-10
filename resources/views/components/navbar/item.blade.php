@props([
    'title' => '',
    'icon' => null,
    'url' => '#',
    'current' => false,
    'type' => 'navbar-item',
])
<a href="{{ $url }}" {{ $attributes->class([
    'navbar-menu-item',
    'is-pinned' => $type === 'featured',
    'is-active' => $current
])}}>
    <x-icon
        name="{{ $icon }}"
        style="outline"
        class="h-[24px] w-[24px]"
    />
    <span class="navbar-item-text">{{ $title }}</span>
</a>
