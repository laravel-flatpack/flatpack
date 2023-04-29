@if (strtolower($type) === 'relation')
    <x-flatpack-relation-field
        :key="$key"
        :options="$options"
        :entry="$entry"
        :entity="$entity"
    />
@endif
