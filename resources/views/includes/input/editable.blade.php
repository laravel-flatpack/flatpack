@if (strtolower($type ?? '') === 'editable')
<div wire:ignore x-cloak x-data="{ editing: false, placeholder: '{{ $placeholder }}' }" class="w-full">
    <button
        x-show="!editing"
        @click="editing=true; setTimeout(() => { $refs.editableInput.focus(); $refs.editableInput.select(); }, 10);"
        {{ $attributes->class([
            'editable-heading',
            'w-full h-auto p-0 m-0 overflow-hidden',
            'text-gray-800 bg-transparent outline-none',
            'text-4xl font-bold' => $size === 'large',
            'text-xl font-normal' => $size === 'base' || $size === 'medium',
            'text-sm font-normal' => $size === 'small',
        ]); }}
    >
        <span x-ref="editableText" class="input-field-placeholder {{ empty($value) ? 'opacity-70' : 'opacity-100' }}">
            {{ empty($value) ? $placeholder : $value }}
        </span>
        <span class="edit-button">
            <x-flatpack::icon icon="edit" size="{{ $size }}" />
        </span>
    </button>
    <label
        x-show="editing"
        {{ $attributes->class([
            'editable-heading',
            'w-full h-auto p-0 m-0 overflow-hidden',
            'text-gray-800 bg-transparent outline-none',
            'text-4xl font-bold' => $size === 'large',
            'text-xl font-normal' => $size === 'base' || $size === 'medium',
            'text-sm font-normal' => $size === 'small',
        ]); }}
    >
        <input
            x-cloak
            x-show="editing"
            x-ref="editableInput"
            @keyup.escape="editing=false; setTimeout(() => { $refs.editableInput.value = $refs.editableText.innerText; }, 10);"
            @keyup.enter="editing=false; setTimeout(() => {
                $refs.editableText.innerText = ($refs.editableInput.value !== '' ? $refs.editableInput.value : placeholder);
            }, 10);"
            @blur="editing=false; setTimeout(() => {
                $refs.editableText.innerText = ($refs.editableInput.value !== '' ? $refs.editableInput.value : placeholder);
            }, 10);"
            wire:model.stop="fields.{{ $key }}"
            wire:key="fields-{{ $key }}"
            id="editable-{{ $key }}"
            type="text"
            name="{{ $key }}"
            {{ $attributes->class([
                'form-field-input',
                'w-full h-auto p-2',
                'text-gray-800 bg-transparent',
                'text-4xl font-bold' => $size === 'large',
                'text-xl font-normal' => $size === 'base' || $size === 'medium',
                'text-sm font-normal' => $size === 'small',
            ]) }}
        />
        <span class="edit-button">
            <div @click="editing=false; setTimeout(() => { $refs.editableInput.value = $refs.editableText.innerText; }, 10);">
                <x-flatpack::icon icon="cancel" size="small" />
            </div>
            <div @click="editing=false; setTimeout(() => { $refs.editableText.innerText = ($refs.editableInput.value !== '' ? $refs.editableInput.value : placeholder); }, 10);">
                <x-flatpack::icon icon="check" size="small" />
            </div>
        </span>
    </label>
</div>
@endif
