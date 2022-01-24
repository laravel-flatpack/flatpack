<html>
<head>
    <title>Flat Pack - Admin Panel</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    <link rel="stylesheet" href="{{ asset('flatpack/css/flatpack.css') }}" />
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js" defer></script>
    @livewireStyles
</head>
<body>
    <div class="container mx-auto">
        @yield('content')
    </div>
    @livewireScripts
    <script src="https://unpkg.com/@nextapps-be/livewire-sortablejs@0.1.1/dist/livewire-sortable.js"></script>
    <script src="{{ asset('flatpack/js/flatpack.js') }}"></script>
</body>
</html>
