<form class="flex flex-col w-full mb-0" wire:submit.prevent="submit">
    <div class="form-fieldset is-compact">
    @foreach ($formFields as $key => $options)
        <x-flatpack-form-field
            :key="$key"
            :options="$options"
            :entry="$entry"
            :model="$model"
            :binding="$fieldsBinding"
        />
    @endforeach
    </div>
    <footer class="gap-4 px-4 py-3 bg-gray-50 sm:flex sm:flex-row-reverse">
        <x-button
            primary
            type="submit"
            label="{{ __('Save') }}"
        />
        <x-button
            wire:click="cancel"
            default
            type="button"
            label="{{ __('Cancel') }}"
        />
    </footer>
</form>
