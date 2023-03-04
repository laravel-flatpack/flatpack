<?php

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'User Name',
        'email' => 'example@demo.com',
        'password' => 'password'
    ]);
});

it('returns upload validation error', function () {
    $response = actingAs($this->user)->post("/backend/posts/create/upload");
    $response->assertJson([
        'error' => 'Create the Post entry first.',
    ]);
    
    $post = Post::create([
        'title' => 'Lorem Ipsum'
    ]);

    $response = actingAs($this->user)->post("/backend/posts/{$post->id}/upload");
    $response->assertJson([
        'error' => 'No file to upload',
    ]);
});

it('successfully uploads a file', function () {
    $post = Post::create([
        'title' => 'Lorem Ipsum'
    ]);

    Storage::fake('public');
 
    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = actingAs($this->user)->post("/backend/posts/{$post->id}/upload", [
        'upload' => $file
    ]);

    Storage::disk('public')->assertExists("uploads/posts/{$post->id}/{$file->hashName()}");
});