<ul class="navbar-items">
    <li>
        <div x-data="{open: false}" class="relative">
            <button @click="open = !open" aria-expanded="true" aria-haspopup="true" class="navbar-menu-item">
                <x-icon name="cog" style="solid" class="h-7 w-7" />

                <span class="navbar-item-text">{{ __('Settings') }}</span>
            </button>
            <div x-show="open" @click.away="open = false" class="absolute w-56 mt-2 text-left origin-top-right bg-white rounded-md shadow-lg te bottom-10 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                <div class="py-1" role="none">
                    <a href="https://github.com/faustoq/laravel-flatpack" target="_blank" class="block px-4 py-2 text-sm text-gray-700" role="menuitem" tabindex="-1">{{ __('Support') }}</a>
                    <a href="https://github.com/faustoq/laravel-flatpack/blob/main/LICENSE.md" target="_blank" class="block px-4 py-2 text-sm text-gray-700" role="menuitem" tabindex="-1">{{ __('License') }}</a>
                </div>
            </div>
        </div>
    </li>
</ul>
