@if (strtolower($type ?? '') === 'textarea')
<textarea
    wire:model.stop="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    type="text"
    placeholder="{{ $placeholder ?? '' }}"
    class="form-field-input resize-none"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $readonly ? 'readonly' : '' }}
></textarea>
@endif
