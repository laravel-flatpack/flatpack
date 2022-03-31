@foreach ($getErrorMessages($errors) as $error)
<div class="form-field-errors">
    @if (is_array($error))
        <ul class="space-y-1 text-sm text-negative-700 dark:text-negative-600">
        @foreach ($error as $e)
            <li>{{ $e }}</li>
        @endforeach
        </ul>
    @else
        {{ $error }}
    @endif
</div>
@endforeach

