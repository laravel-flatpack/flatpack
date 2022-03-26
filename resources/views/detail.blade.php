<x-flatpack-layout>

    <livewire:flatpack.form
        :model="$model"
        :entity="$entity"
        :entry="$entry"
        :composition="$composition"
        :formType="$formType"
        />

    @section('scripts')
    @parent
        <script src="{{ asset('flatpack/js/form.js') }}"></script>
    @endsection

</x-flatpack-layout>
