<div class="top-bar-wrapper">
    <div class="flex items-center justify-start gap-5 text-gray-300">
        <div class="block xl:hidden">
            <button @click="navbar = !navbar" aria-label="{{ __('Toggle Navbar') }}">
                <x-icon name="menu" style="solid" class="w-6 h-6" />
            </button>
        </div>
        {{-- <div class="flex gap-4">
            <x-icon name="search" style="outline" class="w-5 h-5" />
            <input class="bg-transparent outline-none h-5 text-sm" placeholder="{{ __('Search...') }}" />
        </div> --}}
    </div>
    <div class="flex gap-4 text-white">
        @auth
        <div x-data="{open: false}" class="relative">
            <button @click="open = !open" aria-label="{{ __('User Options') }}" aria-expanded="true" aria-haspopup="true" class="flex items-center justify-center gap-2 text-sm text-gray-100 hover:text-white">
                <x-icon name="user-circle" style="outline" class="w-6 h-6" />
                <span>{{ $user->name }}</span>
                <x-icon name="chevron-down" style="outline" class="w-3 h-3" />
            </button>
            <div x-show="open" @click.away="open = false" class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                <div class="py-1" role="none">
                    <a class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" href="https://laravel-flatpack.com/reference/" target="_blank" role="menuitem" tabindex="-1">{{ __('Documentation') }}</a>
                </div>
                <div class="py-1" role="none">
                    <form method="POST" action="{{ route('flatpack.logout') }}" class="mb-0" role="none">
                    @csrf
                        <a href="{{ route('flatpack.logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100"
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
