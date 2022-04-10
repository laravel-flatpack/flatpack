<x-flatpack-layout>
    <livewire:flatpack.table
        :model="$model"
        :entity="$entity"
        :composition="$composition"
    />
    @once
        @push('scripts')
            <script src="{{ asset('flatpack/js/list.js') }}"></script>
        @endpush
    @endonce
</x-flatpack-layout>
