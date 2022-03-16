@if (strtolower($type) === 'relation')
    @if ($relationshipType === 'belongsTo')
        @include('flatpack::includes.form-field.input.select')
    @else
        @include('flatpack::includes.form-field.input.multiselect')
    @endif
@endif
