@if (sizeof($scopes ?? []) > 0)
<ul class="flex items-center justify-center list-none border border-transparent">
    @foreach ($scopes as $key => $options)
        <li class="pr-1">
            <button
                wire:click="$set('scope', '{{ $key }}')"
                style="min-width: 52px"
                class="flex items-center justify-center px-2 py-2 {{ $key === $scope ? 'bg-gray-300 bg-opacity-50' : 'bg-transparent' }} rounded-md hover:bg-gray-300">
                <span class="text-sm {{ $key === $scope ? 'font-medium': '' }}">{{ Arr::get($options, 'label', $key) }}</span>
                @if (Arr::get($options, 'count', false))
                    <span class="flex items-center justify-center px-2 ml-2 text-xs text-white bg-gray-400 rounded-lg py-0.5">{{ $this->scopeQuery($key)->count() }}</span>
                @endif
            </button>
        </li>
    @endforeach
</ul>
@endif
