@if (strtolower($type) === 'relation')
    @if (in_array($relationshipType, ['belongsTo', 'hasOne']))
        @include('flatpack::includes.form-field.select')
    @else
        @include('flatpack::includes.form-field.multiselect')
    @endif
@endif
