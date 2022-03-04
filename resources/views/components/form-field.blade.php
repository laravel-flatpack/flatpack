<div class="form-field col-span-full @if($span !== 'full') lg:col-span-1 @endif">
    <div {{ $attributes->class([ "form-field-elements", "opacity-60" => $disabled, "has-errors" => !empty($fieldErrors) ]) }}>
        @include('flatpack::includes.input.label')
        @include('flatpack::includes.input.blockeditor')
        @include('flatpack::includes.input.editor')
        @include('flatpack::includes.input.textarea')
        @include('flatpack::includes.input.datepicker')
        @include('flatpack::includes.input.datetimepicker')
        @include('flatpack::includes.input.text')
        @include('flatpack::includes.input.email')
        @include('flatpack::includes.input.password')
        @include('flatpack::includes.input.input-select')
        @include('flatpack::includes.input.input-multiselect')
        @include('flatpack::includes.input.relation')
        @include('flatpack::includes.input.tags')
        @include('flatpack::includes.input.editable')
        @include('flatpack::includes.button')
        @include('flatpack::includes.heading')
    </div>
    @include('flatpack::includes.input.errors')
</div>
