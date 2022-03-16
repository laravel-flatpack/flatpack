<div {{ $attributes->class(["form-field col-span-full", "lg:col-span-1" => $span !== "full" ]) }}>
    <div {{ $attributes->class([
        "form-field-elements",
        "opacity-60" => $disabled,
        "has-label" => !empty($label),
        "has-errors" => !empty($fieldErrors)
    ]) }}>

        @include('flatpack::includes.form-field.label')

        @switch(getOption($options, 'type'))

            @case('button')
                <x-flatpack-action-button
                    key="action-{{ $key }}"
                    :options="$options"
                    :entity="$entity"
                    :model="$model"
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
                        <x-flatpack-input-field
                            :key="$key"
                            :options="$options"
                            :entry="$entry"
                        />
                    @endif
                </div>

        @endswitch

        @include('flatpack::includes.form-field.input.errors')

    </div>

</div>
