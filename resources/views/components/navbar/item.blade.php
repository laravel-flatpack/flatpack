@props([
    'title' => '',
    'icon' => null,
    'url' => '#',
    'current' => false,
    'type' => 'navbar-item',
])

<a href="{{ $url ?? '#' }}" {{ $attributes->class([
    'navbar-menu-item',
    'is-pinned' => $type === 'featured',
    'is-active' => $current
])}}>

    <x-icon name="{{ $icon }}" style="{{ $current ? 'solid' : 'outline' }}" class="h-7 w-7" />

    <span class="navbar-item-text">{{ $title }}</span>
</a>
