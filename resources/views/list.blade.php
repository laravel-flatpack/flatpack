<x-flatpack-layout>
<div class="flex flex-col w-full gap-10">
    <div class="flex flex-row items-center justify-between w-full my-2">
        <h1 class="text-4xl font-bold">{{ Str::ucfirst($entity) }}</h1>
        <div class="flex flex-row items-center justify-end gap-2">
            <a href="{{ route('flatpack.form', [ 'entity' => $entity, 'id' => 'create' ]) }}" class="button">
                <span class="whitespace-nowrap">New {{ Str::singular($entity) }}</span>
            </a>
        </div>
    </div>
    <livewire:flatpack.table :model="$model" :entity="$entity" :composition="$composition" />
</div>
</x-flatpack-layout>
