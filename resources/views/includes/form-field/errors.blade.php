@foreach ($getErrorMessages($errors) as $messages)
<div class="form-field-errors">
    <ul class="space-y-1 text-sm text-negative-600">
    @foreach (collect($messages) as $error)
        <li class="flex items-center justify-start gap-1">
            <x-icon name="exclamation-circle" outline class="w-4 h-4" />
            <span>{{ $error }}</span>
        </li>
    @endforeach
    </ul>
</div>
@endforeach

