<?php

use Flatpack\Http\Livewire\BlockEditor;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders the block editor component', function () {
    Storage::fake(config('flatpack.storage.disk'));

    Livewire::test(BlockEditor::class, [
        'editorId' => 'key',
        'value' => '{"time": 1682622261481, "blocks": [{"id": "gKkkpuHZCP", "data": {"text": "Lorem ipsum"}, "type": "paragraph"}, "type": "paragraph"}], "version": "2.26.5"}',
        'readOnly' => false,
    ])
    ->call('render')
    ->assertViewIs('flatpack::components.block-editor')
    ->call('save')
    ->assertEmittedUp('flatpack-editor:save')
    ->call('loadImageFromUrl', 'https://laravel-flatpack.com/flatpack-logo.png');

    Storage::disk(config('flatpack.storage.disk'))->assertExists('flatpack-logo.png');
});
