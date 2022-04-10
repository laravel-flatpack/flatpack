<ul class="mt-10 navbar-items">
    @foreach ($navigation as $key => $item)
    <li>
        <x-flatpack::navbar.item
            :key="$key"
            :icon="Arr::get($item, 'icon')"
            :title="Arr::get($item, 'title', '')"
            :url="Arr::get($item, 'url', '#')"
            :type="Arr::get($item, 'type')"
            :current="($key === $current)"
        />
    </li>
    @endforeach
</ul>
