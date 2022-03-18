@if (strtolower($type ?? '') === 'textarea')
<textarea
    wire:model.lazy="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    type="text"
    placeholder="{{ $placeholder ?? '' }}"
    class="resize-none form-field-input"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $readonly ? 'readonly' : '' }}
></textarea>
@endif
