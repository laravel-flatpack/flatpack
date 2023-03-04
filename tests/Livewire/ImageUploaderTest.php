<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Flatpack\Http\Livewire\ImageUploader;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\Tag;
use Livewire\Livewire;

it('uploads files to storage folder', function () {
    $post = Post::create([
        'title' => 'Lorem ipsum'
    ]);

    Storage::fake('public');
    $disk = Storage::disk('public');

    $this->expect(sizeof($disk->allFiles("uploads/posts/{$post->id}")))
         ->toBe(0);

    $file = UploadedFile::fake()->image('test-image.jpg');
 
    Livewire::test(ImageUploader::class, [
        'name' => 'picture',
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'options' => [
            'maxSize' => 1024
        ],
    ])
    ->assertSet('previousValue', [])
    ->assertSet('images', [])
    ->assertSet('size', 1024)
    ->assertSet('multiple', false)
    ->assertSet('preview', 'auto')
    ->set('rawImages', [
        $file
    ])
    ->call('handleUpload', [], $post->id)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 1);
    
    $this->expect(sizeof($disk->allFiles("uploads/posts/{$post->id}")))
         ->toBe(1);
});

it('handles multiple image uploads', function () {
    $post = Post::create([
        'title' => 'Lorem ipsummmmm',
        'picture' => 'previous-image.jpg'
    ]);

    Storage::fake('public');
    $disk = Storage::disk('public');

    $current = [
        'previous-image.jpg'
    ];
 
    Livewire::test(ImageUploader::class, [
        'name' => 'picture',
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'options' => [
            'multiple' => true
        ],
    ])
    ->assertSet('previousValue', $current)
    ->assertSet('images', $current)
    ->assertSet('multiple', true)
    ->set('rawImages', [
        UploadedFile::fake()->image('test-image.jpg'),
        UploadedFile::fake()->image('test-image.jpg'),
        UploadedFile::fake()->image('test-image.jpg')
    ])
    ->call('handleUpload', [], $post->id)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 4)
    ->call('handleRemoveImage', 2)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 3);
});