<div class="flex flex-col py-10 gap-10 w-full">
    <div class="flex flex-row justify-between items-start gap-5 w-full">
        <x-flatpack::form.header :elements="$header" :fields="$fields" />
        <x-flatpack::form.toolbar :elements="$toolbar" />
    </div>
    <div class="flex flex-row w-full">
        <div class="grid grid-cols-2 w-full max-w-5xl mx-auto">
            @foreach ($form as $key => $options)
                <x-flatpack-form-field
                    :key="$key"
                    :options="$options"
                    :entry="$entry"
                />
            @endforeach
        </div>
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
