<ul class="navbar-items">
    <li>
        <div class="hidden xl:block">
            <button @click="navbar = !navbar" aria-label="{{ __('Toggle Sidebar') }}" class="navbar-menu-item">
                <x-icon name="menu" style="solid" class="h-[24px] w-[24px]" />
                {{-- <span class="navbar-item-text">{{ __('Toggle Sidebar') }}</span> --}}
            </button>
        </div>
    </li>
</ul>
