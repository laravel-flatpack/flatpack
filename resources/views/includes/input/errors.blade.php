@foreach ($fieldErrors as $error)
<div class="form-field-errors">
    @if (is_array($error))
        <ul>
        @foreach ($error as $e)
            <li>{{ $e }}</li>
        @endforeach
        </ul>
    @else
        {{ $error }}
    @endif
</div>
@endforeach
