<ul class="navbar-items">
    <li>
        <a href="/{{ config('flatpack.prefix', 'backend') }}" class="navbar-logo {{ $current === 'home' ? 'current' : '' }}">
            <svg width="46" height="46">
                <use xlink:href="{{ url( config('flatpack.brand.logo', 'flatpack/images/logo.svg') ) }}#logo"></use>
            </svg>
            <span>{{ config('flatpack.brand.name', 'Flatpack') }}</span>
        </a>
    </li>
</ul>
