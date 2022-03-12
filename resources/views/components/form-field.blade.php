<div class="form-field col-span-full @if($span !== 'full') lg:col-span-1 @endif">
    <div {{ $attributes->class([ "form-field-elements", "opacity-60" => $disabled, "has-errors" => !empty($fieldErrors) ]) }}>
        @include('flatpack::includes.input.label')
        <div class="w-full field-wrapper">
            @if (!empty($relationshipType))
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
    </div>
    @include('flatpack::includes.input.errors')
</div>
