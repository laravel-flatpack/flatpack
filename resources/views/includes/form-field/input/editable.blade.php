@if (strtolower($type ?? '') === 'editable')
<div
    wire:ignore
    x-cloak
    x-data="{
        editing: false, placeholder: @js($placeholder), value: @js($value)
    }"
    class="w-full"
    :class="editing ? 'is-editing' : ''">
    <label
        x-show="!editing"
        @click="editing=true; setTimeout(() => { console.log(editing, $refs.input, value); $refs.input.focus(); }, 10);"
        {{ $attributes->class([
            'heading editable',
            'text-gray-800 bg-transparent outline-none',
            'text-4xl font-bold' => $size === 'large',
            'text-xl font-normal' => $size === 'base' || $size === 'medium',
            'text-sm font-normal' => $size === 'small',
        ]); }}
    >
        <div
            class="input-field-placeholder"
            :class="value !== '' && value !== null ? 'opacity-100' : 'opacity-70'"
            x-text="value !== '' && value !== null ? value : placeholder">
        </div>

        <span class="edit-button" x-show="!editing">
            <x-flatpack::icon icon="edit" size="{{ $size }}" />
        </span>
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
            x-cloak
            x-show="editing"
            x-ref="input"
            @keyup.escape="editing=false; setTimeout(() => { console.log('esc', editing, $refs.input.value, value); $refs.input.value = value; }, 10);"
            @keyup.enter="editing=false; setTimeout(() => { console.log('enter', editing, $refs.input.value, value); value = $refs.input.value; }, 10);"
            @blur="editing=false; setTimeout(() => { console.log('blur', editing, $refs.input.value, value); value = $refs.input.value; }, 10);"
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

        <span class="edit-button">
            <div @click="editing=false; setTimeout(() => { $refs.input.value = value; }, 10);">
                <x-flatpack::icon icon="cancel" size="small" />
            </div>
            <div @click="editing=false; setTimeout(() => { value = $refs.input.value; }, 10);">
                <x-flatpack::icon icon="check" size="small" />
            </div>
        </span>

    </label>
</div>
@endif
