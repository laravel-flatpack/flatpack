@if (strtolower($type ?? '') === 'editable')
<div class="h-10 flex items-center justify-start">
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
                'text-3xl leading-5 font-bold' => $size === 'large',
                'text-xl leading-5 font-normal' => $size === 'base' || $size === 'medium',
                'text-sm leading-5 font-normal' => $size === 'small',
            ]); }}
        >
            <span x-show="!data" class="{{ empty($value) ? 'opacity-80' : 'opacity-100' }} input-field-placeholder">
                {{ empty($value) ? $placeholder : $value }}
            </span>

            <span x-show="data" x-text="data" class="{{ empty($value) ? 'opacity-80' : 'opacity-100' }} input-field-placeholder">
            </span>

            <div class="edit-button-toggle">
                <x-icon name="pencil" style="outline" class="w-4 h-4 text-primary-100" />
            </div>
        </label>
        <label
            x-show="isEditing"
            for="editable-{{ $key }}"
            {{ $attributes->class([
                'heading editable',
                'text-gray-800',
                'text-3xl font-bold' => $size === 'large',
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
                    'text-3xl font-bold' => $size === 'large',
                    'text-xl font-normal' => $size === 'base' || $size === 'medium',
                    'text-sm font-normal' => $size === 'small',
                ]) }}
            />

            <div class="edit-button">
                <button @click.stop="undo" class="outline-none inline-flex justify-center items-center group transition-all ease-in duration-150 focus:ring-2 focus:ring-offset-2 hover:shadow-sm disabled:opacity-80 disabled:cursor-not-allowed rounded gap-x-2 text-sm px-4 py-2 ring-primary-600 text-primary-600 hover:bg-secondary-100 w-8 h-8">
                    <x-icon name="x" class="w-4 h-4 shrink-0" />
                </button>
            </div>

            <div class="edit-button">
                <button @click.stop="toggleEditing" class="outline-none inline-flex justify-center items-center group transition-all ease-in duration-150 focus:ring-2 focus:ring-offset-2 hover:shadow-sm disabled:opacity-80 disabled:cursor-not-allowed rounded gap-x-2 text-sm px-4 py-2 ring-primary-600 text-primary-600 hover:bg-secondary-100 w-8 h-8">
                    <x-icon name="check" class="w-4 h-4 shrink-0" />
                </button>
            </div>

        </label>
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('flatpack/js/editable.js') }}"></script>
    @endpush
@endonce

@endif
