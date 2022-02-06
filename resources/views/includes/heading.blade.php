@if (strtolower($type ?? '') === 'heading')
<label
    {{ $attributes->class([
        'w-full h-auto p-0 m-0 overflow-hidden',
        'text-gray-800 border-0 bg-transparent outline-none focus:border-0 focus:bg-transparent focus:ring-0',
        'text-4xl font-bold' => $size === 'large',
        'text-xl font-normal' => $size === 'base' || $size === 'medium',
        'text-sm font-normal' => $size === 'small',
    ]); }}
>
    <span class="{{ empty($value) ? 'opacity-70' : 'opacity-100' }}">
        {{ empty($value) ? $placeholder : $value }}
    </span>
</label>
@endif
