<aside class="navbar" x-bind:class="{ 'is-minimized': navbar }">
    <ul class="navbar-rail-wrapper">
        <li class="navbar-group">
            @include('flatpack::includes.layout.navbar.brand')
            @include('flatpack::includes.layout.navbar.menu')
        </li>
        <li class="navbar-group navbar-rail-bottom">
            @include('flatpack::includes.layout.navbar.bottom')
        </li>
    </ul>
    <button @click="navbar = !navbar" class="navbar-close">
        <x-flatpack::icon icon="close" />
    </button>
</aside>
