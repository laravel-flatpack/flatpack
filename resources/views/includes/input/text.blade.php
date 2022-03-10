@if (strtolower($type ?? '') === 'text')
<input
    wire:model.stop="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    type="text"
    placeholder="{{ $placeholder ?? '' }}"
    autocomplete="{{ $autocomplete ?? 'off' }}"
    class="form-field-input"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $readonly ? 'readonly' : '' }}
/>
@endif