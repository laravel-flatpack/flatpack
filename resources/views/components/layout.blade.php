<html>
<head>
    <title>Flatpack - Admin Panel</title>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('flatpack/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('flatpack/fonts/inter.css') }}" />
    <link rel="stylesheet" href="{{ asset('flatpack/fonts/icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('flatpack/css/flatpack.css') }}" />
    <script src="https://unpkg.com/alpinejs@3.8.1/dist/cdn.min.js" defer></script>
    @livewireStyles
</head>
<body>
    <div
        x-cloak
        x-data="{navbar: Flatpack.navbar.minimized}"
        x-init="$watch('navbar', value => Flatpack.navbar.set(value))"
        class="layout-wrapper"
    >
        @include('flatpack::includes.layout.sidebar')

        <main class="main-content">

            @include('flatpack::includes.layout.topbar')

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
