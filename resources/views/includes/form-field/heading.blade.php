@if ($type === 'heading')
    <div
        {{ $attributes->class([
            'block w-full h-auto p-0 m-0 overflow-hidden',
            'text-gray-800 border-0 bg-transparent outline-none',
            'text-3xl font-bold' => $size === 'large',
            'text-xl font-normal' => $size === 'base' || $size === 'medium',
            'text-sm font-normal' => $size === 'small',
            'heading'
        ]); }}
    >
        <span class="{{ empty($value) ? 'opacity-70' : 'opacity-100' }}">
            {{ empty($value) ? __('New :entity', [
                'entity' => Str::singular($this->entity)
            ]) : $value }}
        </span>
    </div>
@endif
