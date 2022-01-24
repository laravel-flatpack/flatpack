@extends('flatpack::layout')

@section('content')
<div class="flex flex-col py-10 gap-10 w-full">
    <div class="flex flex-row justify-between items-center w-full my-2">
        <h1 class="font-bold text-4xl">{{ Str::ucfirst($entity) }}</h1>
        <div class="flex flex-row">
            <a href="{{ route('flatpack.form', [ 'entity' => $entity, 'id' => 'create' ]) }}" class="button">
                <span class="whitespace-nowrap">New {{ Str::singular($entity) }}</span>
            </a>
        </div>
    </div>
    <livewire:flatpack.table :model="$model" :entity="$entity" :composition="$composition" />
</div>
@endsection
