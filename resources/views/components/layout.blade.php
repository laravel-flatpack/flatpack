<html>
<head>
    <title>Flat Pack - Admin Panel</title>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    <link rel="stylesheet" href="{{ asset('flatpack/css/flatpack.css') }}" />
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js" defer></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/30.0.0/classic/ckeditor.js" defer></script>
    @livewireStyles
</head>
<body>
    <div class="layout-wrapper">
        <aside class="navbar">
            <ul class="navbar-rail-wrapper">
                <li class="navbar-rail">
                    <ul class="navbar-items">
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white block">
                                <x-flatpack::icon icon="grid_view" />
                                <span></span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white bg-gray-900 block my-8">
                                <x-flatpack::icon icon="add_box" />
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white block">
                                <x-flatpack::icon icon="push_pin" />
                                <span></span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white block">
                                <x-flatpack::icon icon="label" />
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white block">
                                <x-flatpack::icon icon="tag" />
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="navbar-rail-bottom">
                    <ul class="navbar-items">
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white block">
                                <x-flatpack::icon icon="manage_accounts" />
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-300 hover:text-white block">
                                <x-flatpack::icon icon="settings" />
                            </a>
                        </li>
                    </ul>
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
    <script src="https://unpkg.com/@nextapps-be/livewire-sortablejs@0.1.1/dist/livewire-sortable.js"></script>
</body>
</html>
