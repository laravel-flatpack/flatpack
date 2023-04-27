<aside class="navbar" x-bind:class="{ 'is-minimized': navbar }">
    <ul class="navbar-rail-wrapper">
        <li class="navbar-group">
            @include('flatpack::includes.layout.navbar.brand')
            @include('flatpack::includes.layout.navbar.menu')
        </li>
    </ul>
    <button @click="navbar = !navbar" class="navbar-close">
        <x-icon name="x" style="outline" class="w-6 h-6" />
    </button>
</aside>
