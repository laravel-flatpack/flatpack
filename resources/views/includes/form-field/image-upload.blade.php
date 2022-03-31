@if ($type === 'image-upload')
    <livewire:flatpack.image-uploader
        :name="$key"
        :options="$options"
        :entity="$entity"
        :model="$model"
        :entry="$entry"
    />
@endif
