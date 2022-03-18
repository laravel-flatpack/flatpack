@if (in_array(strtolower($type), ['relation','select']))
<select
    wire:model="fields.{{ $key }}"
    wire:key="fields-{{ $key }}"
    id="fields-{{ $key }}"
    class="form-select form-field-input"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $readonly ? 'readonly' : '' }}
>
    <option>{{ __('Select an option') }}</option>

    @foreach($items as $optionValue => $display)

    <option value="{{ $optionValue }}">
        {{ $display }}
    </option>

    @endforeach
</select>
@endif
