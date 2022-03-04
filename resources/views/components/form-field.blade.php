<div class="form-field col-span-full @if($span !== 'full') lg:col-span-1 @endif">
    <div {{ $attributes->class(["form-field-elements", "opacity-60" => $disabled, "has-errors" => !empty($fieldErrors)]) }}>
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
        @include('flatpack::includes.input-type-multiselect')
        @include('flatpack::includes.input-type-relation')
        @include('flatpack::includes.input-tags')
        @include('flatpack::includes.button')
        @include('flatpack::includes.heading')
        @include('flatpack::includes.editable')
    </div>
    @include('flatpack::includes.input-errors')
</div>
