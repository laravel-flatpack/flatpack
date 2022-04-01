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
    @wireUiScripts

    <script src="https://unpkg.com/alpinejs@3.9.0/dist/cdn.min.js" defer></script>

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

            <div class="container px-5 pb-20 mx-auto max-w-[1440px]">
                {{ $slot }}
            </div>
        </main>

    </div>

    <x-notifications z-index="z-50" />

    @livewireScripts

    <script src="{{ asset('flatpack/js/flatpack.js') }}"></script>

    @yield('scripts')
</body>
</html>
