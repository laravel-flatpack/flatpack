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
        <aside class="navbar" x-bind:class="{ 'is-minimized': navbar }">
            <ul class="navbar-rail-wrapper">
                <li class="navbar-group">
                    <x-flatpack::navbar :navigation="$navigation" :current="$current" />
                </li>
                <li class="navbar-group navbar-rail-bottom">
                    <ul class="navbar-items">
                        <li>
                            <div x-data="{open: false}" class="relative">
                                <button @click="open = !open" aria-expanded="true" aria-haspopup="true" class="navbar-menu-item">
                                    <x-flatpack::icon icon="settings" />
                                    <span class="navbar-item-text">Settings</span>
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute w-56 mt-2 text-left origin-top-right bg-white rounded-md shadow-lg te bottom-10 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                    <div class="py-1" role="none">
                                        {{-- <div class="block px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="menu-item-0">
                                            <div class="flex justify-start">
                                                <div class="form-switch">
                                                    <input onchange="Flatpack.toggleDarkMode()" class="h-5 align-top bg-gray-300 bg-no-repeat bg-contain border-0 rounded-full shadow-sm appearance-none cursor-pointer form-check-input w-9 focus:outline-none" type="checkbox" role="switch">
                                                    <label class="inline-block ml-4 text-gray-700 form-check-label" for="flexSwitchCheckDefault">Dark mode</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pb-2 mt-2 border-t"></div> --}}
                                        <a href="https://github.com/faustoq/laravel-flatpack" target="_blank" class="block px-4 py-2 text-sm text-gray-700" role="menuitem" tabindex="-1" id="menu-item-1">Support</a>
                                        <a href="https://github.com/faustoq/laravel-flatpack/blob/main/LICENSE.md" target="_blank" class="block px-4 py-2 text-sm text-gray-700" role="menuitem" tabindex="-1" id="menu-item-2">License</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
            <button @click="navbar = !navbar" class="navbar-close">
                <x-flatpack::icon icon="close" />
            </button>
        </aside>
        <main class="main-content">
            <div class="top-bar-wrapper">
                <div class="flex items-center justify-start gap-5 text-gray-300">
                    <button @click="navbar = !navbar">
                        <div x-show="!navbar"><x-flatpack::icon icon="menu_open" /></div>
                        <div x-show="navbar"><x-flatpack::icon icon="menu" /></div>
                    </button>
                    <div>
                        {{--Breadcrumbs--}}
                    </div>
                </div>
                <div class="flex gap-4 text-white">
                    <div class="flex items-center justify-center has-tooltip">
                        <span class="px-3 py-1 mt-20 text-sm text-gray-100 bg-gray-900 rounded shadow-lg tooltip">Preview</span>
                        <a href="/" target="_blank" class="text-gray-400 hover:text-white">
                            <x-flatpack::icon icon="radio_button_checked" />
                        </a>
                    </div>
                    <div x-data="{open: false}" class="relative">
                        <button @click="open = !open" aria-expanded="true" aria-haspopup="true" class="text-gray-400 hover:text-white">
                            <x-flatpack::icon icon="account_circle" />
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 w-40 mt-2 text-left origin-top-right bg-white rounded-md shadow-lg top-8 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                            <div class="py-1" role="none">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700" role="menuitem" tabindex="-1">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
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
