<?php

use Flatpack\Http\Livewire\ImageUploader;
use Flatpack\Tests\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('uploads files to storage folder', function () {
    $post = Post::factory()->create([
        'title' => 'Lorem ipsum',
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
            'maxSize' => 1024,
        ],
    ])
    ->assertSet('previousValue', [])
    ->assertSet('images', [])
    ->assertSet('size', 1024)
    ->assertSet('multiple', false)
    ->assertSet('preview', 'auto')
    ->set('rawImages', [
        $file,
    ])
    ->call('handleUpload', [], $post->id)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 1);

    $this->expect(sizeof($disk->allFiles("uploads/posts/{$post->id}")))
         ->toBe(1);
});

it('handles multiple image uploads', function () {
    $post = Post::factory()->create(['title' => 'Lorem ipsummmmm']);

    Livewire::test(ImageUploader::class, [
        'name' => 'picture',
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'options' => [
            'multiple' => true,
        ],
    ])
    ->assertSet('previousValue', [])
    ->assertSet('images', [])
    ->assertSet('multiple', true)
    ->set('rawImages', [
        UploadedFile::fake()->image('test-image.jpg'),
        UploadedFile::fake()->image('test-image.jpg'),
        UploadedFile::fake()->image('test-image.jpg'),
    ])
    ->call('handleUpload', [], $post->id)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 3);
});

it('handles existing images', function () {
    $post = Post::factory()->create(['title' => 'Lorem ipsummmmm']);

    $path = "uploads/posts/{$post->id}";
    $file = "previous-image.jpg";

    Storage::fake('public');
    $disk = Storage::disk('public');
    $previous = UploadedFile::fake()
        ->image('photo.jpg')
        ->store("uploads/posts/{$post->id}", [
            'disk' => 'public',
        ]);

    $post->picture = $previous;
    $post->save();

    $disk->assertExists($previous);

    Livewire::test(ImageUploader::class, [
        'name' => 'picture',
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'options' => [
            'multiple' => true,
        ],
    ])
    ->assertSet('rawImages', [])
    ->assertSet('previousValue', [$previous])
    ->assertSet('images', [$previous])
    ->assertSet('multiple', true)
    ->set('rawImages', [
        \Livewire\TemporaryUploadedFile::fake()->image('temp-image.jpg'),
        UploadedFile::fake()->image('test-image.jpg'),
        UploadedFile::fake()->image('test-image.jpg'),
    ])
    ->call('handleRemoveImage', 1)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 3)
    ->call('handleUpload', [], $post->id)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 3)
    ->assertSet('toDelete', [])
    ->call('handleRemoveImage', 2)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 2)
    ->call('handleRemoveImage', 0)
    ->assertEmitted('flatpack-imageuploader:updated')
    ->assertCount('images', 1)
    ->call('render');

    $disk->assertMissing($previous);
});

it('handles input errors', function () {
    $post = Post::factory()->create(['title' => 'Lorem ipsummmmm']);

    Livewire::test(ImageUploader::class, [
        'name' => 'picture',
        'entity' => 'posts',
        'model' => Post::class,
        'entry' => $post,
        'options' => [
            'maxSize' => 1,
            'multiple' => true,
        ],
    ])
    ->assertSet('rawImages', [])
    ->assertSet('multiple', true)
    ->set('rawImages', [
        UploadedFile::fake()->image('test-image.jpg')->size(1000),
    ])
    ->call('handleUpload', [], $post->id)
    ->assertCount('images', 0)
    ->assertHasErrors();
});
