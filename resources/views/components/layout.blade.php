<html>
<head>
    <title>Flat Pack - Admin Panel</title>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="{{ asset('flatpack/fonts/inter.css') }}" />
    <link rel="stylesheet" href="{{ asset('flatpack/css/flatpack.css') }}" />
    <script src="https://unpkg.com/alpinejs@3.8.1/dist/cdn.min.js" defer></script>
    @livewireStyles
</head>
<body>
    <div class="layout-wrapper">
        <aside class="navbar">
            <ul class="navbar-rail-wrapper">
                <li class="navbar-rail">
                    <ul class="navbar-items">
                        @foreach ($navigation as $item)
                        <li>
                            <a href="{{ $item['url'] ?? '#' }}" class="text-gray-300 hover:text-white block {{ ($item['type'] ?? '') === 'featured' ? 'bg-gray-900 my-8' : '' }}">
                                <x-flatpack::icon icon="{{ $item['icon'] ?? '' }}" />
                                <span class="hidden">{{ $item['title'] }}</span>
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
                <div>Breadcrumbs</div>
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
