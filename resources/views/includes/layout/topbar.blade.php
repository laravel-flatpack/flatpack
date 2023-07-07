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
                @if (config('flatpack.layout.search-box', false))
                    <livewire:flatpack.search-box />
                @endif
            </div>
            <div class="flex items-center justify-end gap-4 w-1/2">
                @foreach (config('flatpack.layout.buttons', []) as $button)
                    <a href="{{ data_get($button, 'href') }}"
                       target="{{ data_get($button, 'target', '_self') }}"
                       class="outline-none inline-flex justify-center items-center group transition-all ease-in duration-150 focus:ring-0 hover:shadow-sm disabled:opacity-80 disabled:cursor-not-allowed rounded gap-x-2 text-xs px-4 py-2 text-secondary-100 border border-secondary-600 hover:bg-secondary-100 hover:text-gray-700">
                        @if (data_get($button, 'icon'))
                        <x-icon name="{{ data_get($button, 'icon') }}" style="solid" class="w-4 h-4"  />
                        @endif
                        @if (data_get($button, 'label'))
                        <span>{{ data_get($button, 'label') }}</span>
                        @endif
                    </a>
                @endforeach 
                @include('flatpack::components.user-menu')
            </div>
        </div>
    </div>
</div>
