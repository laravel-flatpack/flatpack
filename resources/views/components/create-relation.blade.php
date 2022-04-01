<form class="flex flex-col w-full mb-0" wire:submit.prevent="submit">
    <div class="form-fieldset is-compact">
    @foreach ($main as $key => $options)
        <x-flatpack-form-field
            :key="$key"
            :options="$options"
            :entry="$entry"
            :model="$model"
        />
    @endforeach
    </div>
    <footer class="px-4 py-3 bg-gray-50 sm:flex sm:flex-row-reverse">
        <button
            class="button sm:ml-3 sm:w-auto primary"
            type="submit">{{ __('Save') }}</button>
        <button
            wire:click="cancel"
            class="mt-2 button sm:mt-0 sm:ml-3 sm:w-auto"
            type="button">{{ __('Cancel') }}</button>
    </footer>
</form>
