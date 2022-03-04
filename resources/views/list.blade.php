<x-flatpack-layout>
    <livewire:flatpack.table
        :model="$model"
        :entity="$entity"
        :composition="$composition"
    />

@section('scripts')
@parent
<script src="{{ asset('flatpack/js/list-components.js') }}"></script>
@endsection
</x-flatpack-layout>
