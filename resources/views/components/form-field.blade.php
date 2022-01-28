<div class="flex flex-row justify-start items-center {{ ($span == '2' || $span === 'full') ? 'col-span-full' : 'col-span-1' }}">
    <div class="flex flex-col justify-start items-start w-full p-2 {{ $disabled ? 'opacity-50' : '' }}">
        @include('flatpack::includes.input-label')
        @include('flatpack::includes.input-blockeditor')
        @include('flatpack::includes.input-editor')
        @include('flatpack::includes.input-textarea')
        @include('flatpack::includes.input-type-datepicker')
        @include('flatpack::includes.input-type-datetimepicker')
        @include('flatpack::includes.input-type-text')
    </div>
</div>
