<?php

use Flatpack\Actions\Upload;

beforeEach(function () {
    $this->actionInstance = new Upload('posts', \Flatpack\Tests\Models\Post::class);
});

it('handles the action', function () {
    $file = \Illuminate\Http\UploadedFile::fake()
        ->createWithContent('filename.txt', 'hello world')
        ->store('filename.txt');

    $action = $this->actionInstance
        ->addFile($file)
        ->run();

    $this->expect($this->actionInstance->files)->toBe([$file]);
    $this->expect(count($action))->toBe(1);
});

it('handles the action for multiple files', function () {
    $files = [
        \Illuminate\Http\UploadedFile::fake()
            ->createWithContent('filename.txt', 'hello world')
            ->store('filename.txt'),
        \Illuminate\Http\UploadedFile::fake()
            ->createWithContent('filename.txt', 'hello world')
            ->store('filename.txt'),
    ];

    $action = $this->actionInstance
        ->addFiles($files)
        ->run();

    $this->expect($this->actionInstance->files)->toBe($files);
    $this->expect(count($action))->toBe(2);
});


it('returns an empty array if no files are provided', function () {
    $action = $this->actionInstance->run();

    $this->expect($this->actionInstance->files)->toBe([]);
    $this->expect($action)->toBe([]);
});
