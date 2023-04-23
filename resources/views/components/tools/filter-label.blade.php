@aware(['component'])
@props(['filter', 'theme' => 'tailwind', 'filterLayout' => 'popover', 'tableName' => 'table'])

<label 
    for="{{ $tableName }}-filter-{{ $filter->getKey() }}" 
    class="block text-sm font-medium leading-5 text-gray-700 dark:text-white"
>
    {{ $filter->getName() }}
</label>
