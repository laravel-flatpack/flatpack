@if (strtolower($key ?? '') === 'subtitle')
<h2 class="flex flex-row gap-2 justify-start items-center text-base mt-0 text-gray-500">
    @if (!empty($label))
    <span class="font-bold text-sm uppercase">{{ $label }}: </span>
    @endif
    <span class="font-normal">{{ $value }}</span>
</h2>
@endif
