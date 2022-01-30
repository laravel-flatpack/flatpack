<div class="flex flex-col gap-10 w-full min-h-screen">
    <div class="flex flex-row justify-between items-start gap-5 w-full">
        <x-flatpack::form.header :elements="$header" :fields="$fields" />
        <x-flatpack::form.toolbar :elements="$toolbar" />
    </div>
    <div class="flex flex-row w-full">
        @foreach ($form as $key => $options)
            @if ($key === 'tabs')
                @include('flatpack::includes.tabs')
            @else
                @if ($loop->first)
                    <div class="box form-fieldset">
                @endif
                    <x-flatpack-form-field
                        :key="$key"
                        :options="$options"
                        :entry="$entry"
                    />
                @if ($loop->last)
                    </div>
                @endif
            @endif
        @endforeach

        @if (isset($sidebar) && count($sidebar) > 0)
        <div class="w-1/4">
            @foreach ($sidebar as $key => $options)
                <x-flatpack-form-field
                    :key="$key"
                    :options="$options"
                    :entry="$entry"
                />
            @endforeach
        </div>
        @endif
    </div>
</div>
