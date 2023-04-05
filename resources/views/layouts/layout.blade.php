<!DOCTYPE html>
<html>

<head>
    <title>Flatpack - Admin Panel</title>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ flatpackAsset('flatpack/favicon.ico') }}">
    <link rel="stylesheet" href="{{ flatpackAsset('flatpack/fonts/inter.css') }}" />
    <link rel="stylesheet" href="{{ flatpackAsset('flatpack/css/flatpack.css') }}" />
    @livewireStyles
    @wireUiScripts
    <script defer src="{{ flatpackAsset('flatpack/js/alpine.js') }}"></script>
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

            <div class="container px-5 pb-20 mx-auto max-w-[1400px]">
                {{ $slot }}
            </div>

            @include('flatpack::includes.layout.footer')
        </main>

    </div>

    <x-notifications z-index="z-50" />

    @livewireScripts

    <script src="{{ flatpackAsset('flatpack/js/flatpack.js') }}"></script>

    @stack('scripts')
</body>

</html>