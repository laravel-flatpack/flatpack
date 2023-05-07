<x-flatpack-layout>
    <livewire:flatpack.form
        :model="$model"
        :entity="$entity"
        :entry="$entry"
        :composition="$composition"
        :formType="$formType"
        />
    @once
        @push('scripts')
            <script src="{{ asset('flatpack/js/form.js') }}"></script>
        @endpush
    @endonce
</x-flatpack-layout>
