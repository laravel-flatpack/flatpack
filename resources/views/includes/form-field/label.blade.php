@if(!empty($label) && $type !== 'button')
<label class="block text-sm font-medium text-secondary-700 dark:text-gray-400" for="fields-{{ $key }}">{{ $label }}</label>
@endif
