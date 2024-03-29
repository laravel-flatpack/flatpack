@auth
<div x-data="{open: false}" class="relative">
    <button @click="open = !open" aria-label="{{ __('User Options') }}" aria-expanded="true" aria-haspopup="true" class="flex items-center justify-center gap-2 text-sm text-gray-100 hover:text-white">
        <img
            src="{{ $user->getFlatpackUserAvatar() }}"
            alt="{{ $user->name }}"
            class="w-6 h-6 rounded-full"
        />
        <x-icon name="chevron-down" style="outline" class="w-3 h-3 mx-1" />
    </button>
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="origin-top-right right-0 w-48 z-30 absolute mt-2 whitespace-nowrap" 
        x-on:click="open = false"
        x-on:click.away="open = false"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="menu-button"
        tabindex="-1">
        <div class="relative max-h-60 soft-scrollbar overflow-auto border border-secondary-200 rounded-lg shadow-lg p-1 bg-white dark:bg-secondary-800 dark:border-secondary-600">
            <div>
                <form method="POST" action="{{ route('flatpack.logout') }}" class="mb-0" role="none">
                @csrf
                    <a href="{{ route('flatpack.logout') }}"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="text-secondary-600 px-4 py-2 text-sm flex items-center cursor-pointer rounded-md transition-colors duration-150 hover:text-secondary-900 hover:bg-secondary-100 dark:text-secondary-400 dark:hover:bg-secondary-700"
                        role="menuitem"
                        tabindex="-1">
                        <span>{{ __('Logout') }}</span>
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
@endauth