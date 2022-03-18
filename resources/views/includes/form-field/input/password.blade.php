@if (strtolower($type) === 'password')
<input
    wire:model.lazy="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    type="password"
    placeholder="{{ $placeholder ?? '' }}"
    class="form-field-input"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $readonly ? 'readonly' : '' }}
/>
@endif
