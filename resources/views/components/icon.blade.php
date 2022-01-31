@props(['icon' => '', 'size' => 'medium'])
@if ($size === 'small')
<div class="material-icons text-xl w-6 h-6 flex justify-center items-center">
    {{ $icon }}
</div>
@else
<div class="material-icons text-3xl w-12 h-12 flex justify-center items-center">
    {{ $icon }}
</div>
@endif
