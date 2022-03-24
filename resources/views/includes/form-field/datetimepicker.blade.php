@if (strtolower($type ?? '') === 'datetimepicker')
    <x-datetime-picker
        without-timezone
        :label="$label"
        :placeholder="$placeholder"
        :disabled="$disabled"
        :readonly="$readonly"
        :min="data_get($options, 'min', false)"
        :max="data_get($options, 'max', false)"
        wire:model.defer="fields.{{ $key }}"
    />
@endif
