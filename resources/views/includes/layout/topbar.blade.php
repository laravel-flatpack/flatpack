<div class="top-bar-wrapper">
    <div class="flex items-center justify-start gap-5 text-gray-300">
        <button @click="navbar = !navbar">
            <div x-show="!navbar"><x-icon name="login" style="solid" class="w-6 h-6" /></div>
            <div x-show="navbar"><x-icon name="menu" style="outline" class="w-6 h-6" /></div>
        </button>
    </div>
    <div class="flex gap-4 text-white">
        @auth
        <div x-data="{open: false}" class="relative">
            <button @click="open = !open" aria-expanded="true" aria-haspopup="true" class="flex items-center justify-center gap-2 text-sm text-gray-400 hover:text-white">
                <x-icon name="user-circle" style="outline" class="w-6 h-6" />
                <span>{{ Auth::user()->name }}</span>
            </button>
            <div x-show="open" @click.away="open = false" class="absolute right-0 w-40 mt-2 text-left origin-top-right bg-white rounded-md shadow-lg top-8 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                <div class="py-1" role="none">
                    <form method="POST" action="{{ route('logout') }}" class="mb-0">
                    @csrf
                        <a href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="block px-4 py-2 text-sm text-gray-700"
                            role="menuitem"
                            tabindex="-1">
                            <span>{{ __('Log Out') }}</span>
                        </a>
                    </form>
                </div>
            </div>
        </div>
        @endauth
    </div>
</div>
