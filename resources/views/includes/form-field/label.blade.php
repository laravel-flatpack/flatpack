@if(!empty($label) && $type !== 'button')
<label class="form-field-label" for="fields-{{ $key }}">{{ $label }}</label>
@endif
