@props([
    'title' => '',
    'icon' => null,
    'url' => '#',
    'current' => false,
    'type' => 'navbar-item',
])

<a href="{{ $url ?? '#' }}"
    {{ $attributes->class([
        'navbar-menu-item',
        'is-pinned' => $type === 'featured',
        'is-active' => $current,
    ])}}>
    <x-flatpack::icon icon="{{ $icon }}" />
    <span class="navbar-item-text">{{ $title }}</span>
</a>
