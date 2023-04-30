<ul class="mt-6 navbar-items">
    @foreach ($navigation as $key => $item)
    <li>
        <x-flatpack::navbar.item
            :key="$key"
            :icon="data_get($item, 'icon')"
            :title="data_get($item, 'title', '')"
            :url="data_get($item, 'url', '#')"
            :type="data_get($item, 'type')"
            :current="($key === $current)"
        />
    </li>
    @endforeach
</ul>
