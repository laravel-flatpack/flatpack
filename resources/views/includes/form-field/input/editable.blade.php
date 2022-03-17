@if (strtolower($type ?? '') === 'editable')
<div
    x-cloak
    x-data="{
        editing: false,
        placeholder: @js($placeholder),
        value: @js($value),
        edit: function () {
            this.editing = true;
            setTimeout(() => { $refs.input.focus(); }, 50);
        },
        undo: function () {
            this.editing = false;
            $refs.input.value = this.value;
        },
        apply: function () {
            if (!this.editing) { return; }
            this.editing = false;
            this.value = $refs.input.value;

            $refs.placeholder.innerText = this.value;
        },
        isEmpty: function () {
            return this.value === '' || this.value === null;
        },
    }"
    class="w-full" :class="editing ? 'is-editing' : ''">
    <label
        x-show="!editing"
        @click="edit"
        {{ $attributes->class([
            'heading editable',
            'text-gray-800 bg-transparent outline-none',
            'text-4xl font-bold' => $size === 'large',
            'text-xl font-normal' => $size === 'base' || $size === 'medium',
            'text-sm font-normal' => $size === 'small',
        ]); }}
    >
        <span x-ref="placeholder" class="{{ empty($value) ? 'opacity-80' : 'opacity-100' }} input-field-placeholder">
            {{ empty($value) ? $placeholder : $value }}
        </span>

        <div class="edit-button">
            <button @click="edit">
                <x-flatpack::icon icon="edit" size="{{ $size }}" />
            </button>
        </div>
    </label>
    <label
        x-show="editing"
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
            @keyup.escape="undo"
            @keyup.enter="apply"
            @keydown.tab="apply"
            wire:model.stop="fields.{{ $key }}"
            wire:key="fields-{{ $key }}"
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
                <x-flatpack::icon icon="cancel" size="small" />
            </button>
        </div>
        <div class="edit-button">
            <button @click.stop="apply" class="w-8 h-8">
                <x-flatpack::icon icon="check" size="small" />
            </button>
        </div>

    </label>
</div>
@endif
