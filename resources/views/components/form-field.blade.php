<div {{ $attributes->class(["form-field col-span-full", "lg:col-span-1" => $span !== "full" ]) }}>
    <div {{ $attributes->class([
        "form-field-elements",
        "opacity-60" => $disabled,
        "has-label" => !empty($label),
        "has-errors" => !empty($errors->getMessages())
    ]) }}>
        <div class="w-full field-wrapper">
            @include('flatpack::includes.form-field.input.text')
            @include('flatpack::includes.form-field.input.email')
            @include('flatpack::includes.form-field.input.password')
            @include('flatpack::includes.form-field.editor')
            @include('flatpack::includes.form-field.block-editor')
            @include('flatpack::includes.form-field.textarea')
            @include('flatpack::includes.form-field.toggle')
            @include('flatpack::includes.form-field.date-picker')
            @include('flatpack::includes.form-field.datetime-picker')
            @include('flatpack::includes.form-field.select')
            @include('flatpack::includes.form-field.relation')
            @include('flatpack::includes.form-field.tags')
            @include('flatpack::includes.form-field.editable')
            @include('flatpack::includes.form-field.image-upload')
            @include('flatpack::includes.form-field.button')
            @include('flatpack::includes.form-field.heading')
            @include('flatpack::includes.form-field.section')
        </div>

        @include('flatpack::includes.form-field.errors')
    </div>
</div>
