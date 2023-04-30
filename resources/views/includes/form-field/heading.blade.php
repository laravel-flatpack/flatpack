@if ($type === 'heading')
    <div
        {{ $attributes->class([
            'flex items-center justify-start gap-2 w-full h-8 p-0 m-0 overflow-hidden',
            'text-gray-800 border-0 bg-transparent outline-none',
            'text-3xl font-bold' => $size === 'large',
            'text-2xl font-bold' => $size === 'base' || $size === 'medium',
            'text-base font-bold' => $size === 'small',
            'heading'
        ]); }}
    >
        @if (! empty($label))
            <span class="text-xs font-bold uppercase text-gray-600 pr-1">{{ $label }}</span>
        @endif
        <span class="{{ empty($value) ? 'opacity-70' : 'opacity-100' }}">
            {{ empty($value) ? __('New :entity', [
                'entity' => Str::singular($this->entity)
            ]) : $value }}
        </span>
    </div>
@endif
