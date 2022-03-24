<div {{ $attributes->class(["form-field col-span-full", "lg:col-span-1" => $span !== "full" ]) }}>
    <div {{ $attributes->class([
        "form-field-elements",
        "opacity-60" => $disabled,
        "has-label" => !empty($label),
        "has-errors" => !empty($fieldErrors)
    ]) }}>

        @switch(data_get($options, 'type'))

            @case('button')
                <x-flatpack-action-button
                    key="action-{{ $key }}"
                    :options="$options"
                    :entity="$entity"
                    :model="$model"
                    :entry="$entry"
                    class="self-center w-fit h-fit"
                />
                @break

            @case('image-upload')
                <livewire:flatpack.image-uploader
                    :name="$key"
                    :options="$options"
                    :entity="$entity"
                    :model="$model"
                    :entry="$entry"
                />
                @break

            @case('block-editor')
                <livewire:flatpack.block-editor
                    :editor-id="$key"
                    :value="$entry->{$key}"
                    :read-only="$readonly"
                    class="w-full"
                />
                @break

            @default
                <div class="w-full field-wrapper">
                    @if ($relation)
                        <x-flatpack-relation-field
                            :key="$key"
                            :options="$options"
                            :entry="$entry"
                        />
                    @else
                        @include('flatpack::includes.form-field.input.text')
                        @include('flatpack::includes.form-field.input.email')
                        @include('flatpack::includes.form-field.input.password')
                        @include('flatpack::includes.form-field.editor')
                        @include('flatpack::includes.form-field.textarea')
                        @include('flatpack::includes.form-field.datepicker')
                        @include('flatpack::includes.form-field.datetimepicker')
                        @include('flatpack::includes.form-field.select')
                        @include('flatpack::includes.form-field.multiselect')
                        @include('flatpack::includes.form-field.editable')
                        @include('flatpack::includes.heading')
                    @endif
                </div>

        @endswitch

        @include('flatpack::includes.form-field.errors')

    </div>

</div>
