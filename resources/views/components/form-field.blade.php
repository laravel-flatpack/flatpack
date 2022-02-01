<div class="form-field {{ ($span == 2 || $span === 'full') ? 'col-span-full' : 'col-span-1' }}">
    <div class="form-field-elements {{ $disabled ? 'opacity-60' : 'opacity-100' }}">
        @include('flatpack::includes.input-label')
        @include('flatpack::includes.input-blockeditor')
        @include('flatpack::includes.input-editor')
        @include('flatpack::includes.input-textarea')
        @include('flatpack::includes.input-type-datepicker')
        @include('flatpack::includes.input-type-datetimepicker')
        @include('flatpack::includes.input-type-text')
        @include('flatpack::includes.input-type-email')
        @include('flatpack::includes.input-type-password')
        @include('flatpack::includes.input-type-select')
        @include('flatpack::includes.input-type-relation')
        @include('flatpack::includes.input-tags')
    </div>
</div>
