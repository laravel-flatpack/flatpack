@if ($type === 'relation')
    @if (in_array($relationshipType, ['belongsTo', 'hasOne']))
        @include('flatpack::includes.form-field.relation.select', [
            'entity' => $entity
        ])
    @else
        @include('flatpack::includes.form-field.relation.checkbox-list', [
            'entity' => $entity
        ])
    @endif
@endif
