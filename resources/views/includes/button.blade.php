@if (strtolower($type ?? '') === 'button')
<div>
<x-flatpack::button>
    {{ $value ?? '' }}
</x-flatpack::button>
</div>
@endif
