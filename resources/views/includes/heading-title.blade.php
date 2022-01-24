@if (strtolower($key ?? '') === 'title')
    <h1 class="text-4xl font-bold mt-0 mb-1">{{ empty($label) ? '' : "{$label}: " }}{{ $value }}</h1>
@endif
