@if (strtolower($type ?? '') === 'editable')
<div
    x-cloak
    x-data="Flatpack.editable.editableInstance('fields.{{ $key }}', 'editable-field-{{ $key }}')"
    x-init="initEditable()"
    :class="isEditing ? 'w-full is-editing' : 'w-full'">
    <label
        x-show="!isEditing"
        @click.stop="toggleEditing"
        {{ $attributes->class([
            'heading editable',
            'text-gray-800 bg-transparent outline-none',
            'text-4xl font-bold' => $size === 'large',
            'text-xl font-normal' => $size === 'base' || $size === 'medium',
            'text-sm font-normal' => $size === 'small',
        ]); }}
    >
        <span x-show="!data" class="{{ empty($value) ? 'opacity-80' : 'opacity-100' }} input-field-placeholder">
            {{ empty($value) ? $placeholder : $value }}
        </span>

        <span x-show="data" x-text="data" class="{{ empty($value) ? 'opacity-80' : 'opacity-100' }} input-field-placeholder">
        </span>

        <div class="edit-button">
            <x-icon name="pencil" style="outline" class="w-5 h-5" />
        </div>
    </label>
    <label
        x-show="isEditing"
        for="editable-{{ $key }}"
        {{ $attributes->class([
            'heading editable',
            'text-gray-800',
            'text-4xl font-bold' => $size === 'large',
            'text-xl font-normal' => $size === 'base' || $size === 'medium',
            'text-sm font-normal' => $size === 'small',
        ]); }}
    >
        <input
            x-ref="input"
            x-model="data"
            x-show="isEditing"
            @click.away="toggleEditing"
            @keydown.enter="disableEditing"
            @keydown.window.escape="disableEditing"
            id="editable-{{ $key }}"
            type="text"
            name="{{ $key }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->class([
                'form-field-input',
                'w-full h-auto p-2',
                'text-gray-800',
                'text-4xl font-bold' => $size === 'large',
                'text-xl font-normal' => $size === 'base' || $size === 'medium',
                'text-sm font-normal' => $size === 'small',
            ]) }}
        />

        <div class="edit-button">
            <button @click.stop="undo" class="w-8 h-8">
                <x-icon name="x" style="outline" class="w-5 h-5" />
            </button>
        </div>

        <div class="edit-button">
            <button @click.stop="toggleEditing" class="w-8 h-8">
                <x-icon name="check" style="outline" class="w-5 h-5" />
            </button>
        </div>

    </label>
</div>

@once
    @push('scripts')
        <script src="{{ asset('flatpack/js/editable.js') }}"></script>
    @endpush
@endonce

@endif
