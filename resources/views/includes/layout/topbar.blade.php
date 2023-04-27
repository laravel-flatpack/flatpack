<div class="top-bar-wrapper">
    <div class="container text-gray-300 mx-auto max-w-[1400px]">
        <div class="flex justify-between items-center gap-5 w-full">
            <div class="flex items-center justify-start gap-3 w-1/2">
                <button
                    x-on:click="navbar = !navbar"
                    x-bind:class="{ 'is-open': !navbar }"
                    aria-label="{{ __('Toggle Navbar') }}"
                    class="menu-toggle">
                    <x-icon name="menu" style="solid" class="w-5 h-5" />
                </button>
                <livewire:flatpack.search-box />
            </div>
            <div class="flex items-center justify-end gap-4 w-1/2">
                @include('flatpack::components.user-menu')
            </div>
        </div>
    </div>
</div>
