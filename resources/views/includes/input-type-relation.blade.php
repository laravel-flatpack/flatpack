@if (strtolower($type) === 'relation')
    @if($relationshipType === 'belongsTo')
        @include('flatpack::includes.input-select')
    @else
        @include('flatpack::includes.input-multiselect')
    @endif
@endif
