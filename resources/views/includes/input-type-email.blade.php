@if (strtolower($type) === 'email')
<input
    wire:model.stop="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    type="email"
    placeholder="{{ $placeholder ?? '' }}"
    class="form-field-input"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $readonly ? 'readonly' : '' }}
/>
@endif
