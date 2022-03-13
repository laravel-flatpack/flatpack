@if (!empty($fieldErrors))
<div class="mt-2">
@foreach ($fieldErrors as $error)
    <div class="text-sm text-red-500">
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
</div>
@endif
