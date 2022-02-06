<html>
<head>
    <title>Flat Pack - Admin Panel</title>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('flatpack/fonts/inter.css') }}" />
    <link rel="stylesheet" href="{{ asset('flatpack/fonts/icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('flatpack/css/flatpack.css') }}" />
    <script src="https://unpkg.com/alpinejs@3.8.1/dist/cdn.min.js" defer></script>
    @livewireStyles
</head>
<body>
    <div
        x-cloak
        x-data="{
            navbarMini: localStorage.getItem('navbarMini') === 'true'
        }"
        x-init="$watch('navbarMini', val => localStorage.setItem('navbarMini', val))"
        class="layout-wrapper"
    >
        <aside class="navbar" x-bind:class="{ 'is-minimized': navbarMini }">
            <ul class="navbar-rail-wrapper">
                <li class="navbar-rail">
                    <ul class="navbar-items">
                        @foreach ($navigation as $key => $item)
                        <li>
                            <a href="{{ $item['url'] ?? '#' }}"
                                {{ $attributes->class([
                                    'hover:text-white block',
                                    'bg-gray-900 my-8' => ($item['type'] ?? '') === 'featured',
                                    'text-white' => ($key === $current),
                                    'text-gray-400' => ($key !== $current),
                                ])}}>
                                <x-flatpack::icon icon="{{ $item['icon'] ?? '' }}" />
                                <span class="navbar-item-text">{{ $item['title'] }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </li>
                <li class="navbar-rail-bottom">
                    {{-- TODO: add navbar bottom items --}}
                    {{-- <ul class="navbar-items">
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white block">
                                <x-flatpack::icon icon="settings" />
                            </a>
                        </li>
                    </ul> --}}
                </li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="top-bar-wrapper">
                <div class="flex gap-5 justify-start items-center text-gray-300">
                    <button @click="navbarMini = !navbarMini">
                        <div x-show="!navbarMini"><x-flatpack::icon icon="menu_open" /></div>
                        <div x-show="navbarMini"><x-flatpack::icon icon="menu" /></div>
                    </button>
                    <div>
                        {{--Breadcrumbs--}}
                    </div>
                </div>
                <div class="text-white"><x-flatpack::icon icon="notifications" /></div>
            </div>
            <div class="container px-5 py-5 mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>
    @livewireScripts
    <script src="{{ asset('flatpack/js/flatpack.js') }}"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/30.0.0/classic/ckeditor.js"></script>
    <script src="https://unpkg.com/@nextapps-be/livewire-sortablejs@0.1.1/dist/livewire-sortable.js"></script>
</body>
</html>
