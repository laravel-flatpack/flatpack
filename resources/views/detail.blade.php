@extends('flatpack::layout')

@section('content')
    <livewire:flatpack.form
        :model="$model"
        :entity="$entity"
        :entry="$entry"
        :composition="$composition" />
@endsection
