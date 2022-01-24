@extends('flatpack::layout')

@section('content')
    <div class="heading py-10">
        <h1 class="font-bold text-4xl">{{ Str::ucfirst($entity) }}</h1>
    </div>
    <livewire:flatpack.table :model="$model" :entity="$entity" :composition="$composition" />
@endsection
