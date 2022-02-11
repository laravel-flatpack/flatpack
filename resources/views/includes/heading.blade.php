@if (strtolower($type ?? '') === 'heading')
<div
    {{ $attributes->class([
        'inline-block w-full h-auto p-0 m-0 overflow-hidden',
        'text-gray-800 border-0 bg-transparent outline-none',
        'text-4xl font-bold' => $size === 'large',
        'text-xl font-normal' => $size === 'base' || $size === 'medium',
        'text-sm font-normal' => $size === 'small',
    ]); }}
>
    <span class="{{ empty($value) ? 'opacity-70' : 'opacity-100' }}">
        {{ empty($value) ? $placeholder : $value }}
    </span>
</div>
@endif
