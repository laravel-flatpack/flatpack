@if (!empty($fieldErrors))
<div class="mt-2">
@foreach ($fieldErrors as $error)
    <div class="text-sm text-red-500">
        {{ $error }}
    </div>
@endforeach
</div>
@endif
