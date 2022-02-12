<div class="top-bar-wrapper">
    <div class="flex items-center justify-start gap-5 text-gray-300">
        <button @click="navbar = !navbar">
            <div x-show="!navbar"><x-flatpack::icon icon="menu_open" /></div>
            <div x-show="navbar"><x-flatpack::icon icon="menu" /></div>
        </button>
    </div>
    <div class="flex gap-4 text-white">
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
