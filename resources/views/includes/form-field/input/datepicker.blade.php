@if (strtolower($type ?? '') === 'datepicker')
<input
    wire:model.lazy="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    type="date"
    @if(isset($options['min']) && strlen($options['min'])) min="{{ $options['min'] }}" @endif
    @if(isset($options['max']) && strlen($options['max'])) max="{{ $options['max'] }}" @endif
    class="form-field-input"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $readonly ? 'readonly' : '' }}
/>
@endif
