@if (strtolower($type ?? '') === 'date-picker')
<x-datetime-picker
	x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $key }}')"
    wire:model.defer="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
	:label="$label"
	:placeholder="$placeholder"
	:disabled="$disabled"
	:readonly="$readonly"
	:min="data_get($options, 'min', false)"
	:max="data_get($options, 'max', false)"
	:parse-format="data_get($options, 'format.parse', 'YYYY-MM-DD')"
	without-timezone
	without-time
/>
@endif