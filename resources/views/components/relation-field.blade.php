@if ($type === 'relation')
    @if (in_array($relationshipType, ['belongsTo', 'hasOne']))
        @include('flatpack::includes.form-field.relation.select')
    @else
        @include('flatpack::includes.form-field.relation.checkbox-list')
    @endif
@endif
