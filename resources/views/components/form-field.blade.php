<div {{ $attributes->class(["form-field col-span-full", "lg:col-span-1" => $span !== "full"]) }}>
    <div {{ $attributes->class([ "form-field-elements", "opacity-60" => $disabled, "has-errors" => !empty($fieldErrors) ]) }}>

        @include('flatpack::includes.input.label')

        @if (isset($options['type']) && $options['type'] === 'button' && isset($options['action']))
            <x-flatpack-action-button
                key="action-{{ $key }}"
                :options="$options"
                :entity="$entity"
                :model="$model"
                class="self-center w-fit h-fit"
            />
        @elseif (isset($options['type']) && $options['type'] === 'image-upload')
            <livewire:flatpack.image-uploader
                :name="$key"
                :options="$options"
                :entity="$entity"
                :model="$model"
                :entry="$entry"
            />
        @elseif (isset($options['type']) && in_array($options['type'], ['block-editor']))
            <livewire:flatpack.block-editor
                :editor-id="$key"
                :value="$entry->{$key}"
                :read-only="$readonly"
                class="w-full"
            />
        @else
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

        @endif

    </div>

    @include('flatpack::includes.input.errors')

</div>
