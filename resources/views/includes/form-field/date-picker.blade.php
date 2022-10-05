@if (strtolower($type ?? '') === 'date-picker')
<x-datetime-picker
	without-timezone
	without-time
	:label="$label"
	:placeholder="$placeholder"
	:disabled="$disabled"
	:readonly="$readonly"
	:min="data_get($options, 'min', false)"
	:max="data_get($options, 'max', false)"
	:parse-format="data_get($options, 'format.parse', 'YYYY-MM-DD')"
	wire:model.defer="fields.{{ $key }}"
/>
@endif