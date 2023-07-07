<x-flatpack-layout>
    
    <livewire:flatpack.dashboard />

    @push('scripts')
        <script src="{{ asset('flatpack/js/chart.js') }}"></script>
    @endpush

</x-flatpack-layout>
