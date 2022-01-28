<div class="flex flex-col py-10 gap-10 w-full">
    <div class="flex flex-row justify-between items-start gap-5 w-full">
        <x-flatpack::form.header :elements="$header" :fields="$fields" />
        <x-flatpack::form.toolbar :elements="$toolbar" />
    </div>
    <div class="flex flex-row w-full">
        <div class="grid grid-cols-2 w-full max-w-5xl mx-auto">
            @foreach ($form ?? [] as $key => $options)
                <x-flatpack::form-field
                    :key="$key"
                    :value="$fields[$key] ?? null"
                    :type="Arr::get($options, 'type', 'text')"
                    :label="Arr::get($options, 'label', '')"
                    :placeholder="Arr::get($options, 'placeholder', '')"
                    :span="Arr::get($options, 'span', 'full')"
                    :options="Arr::get($options, 'options', [])"
                    :items="Arr::get($options, 'items', [])"
                    :disabled="Arr::get($options, 'disabled', false)"
                    :required="Arr::get($options, 'required', false)"
                    :readonly="Arr::get($options, 'readonly', false)"
                />
            @endforeach
        </div>
        @if (isset($sidebar) && count($sidebar) > 0)
        <div class="w-1/4">
            @foreach ($sidebar as $key => $options)
                <x-flatpack::form-field
                    :key="$key"
                    :value="$fields[$key] ?? null"
                    :type="Arr::get($options, 'type', 'text')"
                    :label="Arr::get($options, 'label', '')"
                    :placeholder="Arr::get($options, 'placeholder', '')"
                    :span="Arr::get($options, 'span', 'full')"
                    :options="Arr::get($options, 'options', [])"
                    :items="Arr::get($options, 'items', [])"
                    :disabled="Arr::get($options, 'disabled', false)"
                    :required="Arr::get($options, 'required', false)"
                    :readonly="Arr::get($options, 'readonly', false)"
                />
            @endforeach
        </div>
        @endif
    </div>
</div>
