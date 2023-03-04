<?php

use Flatpack\Tests\Models\Post;
use Flatpack\Traits\WithStorageFiles;

beforeEach(function () {
    $this->trait = new class () {
        use WithStorageFiles {
            combinePath as public;
            getStorageDisk as public;
            getStoragePath as public;
            getFileName as public;
        }

        public function __construct()
        {
            $this->files = [];
            $this->entity = 'posts';
            $this->entry = Post::create(['title' => 'Lorem Ipsum']);
        }
    };
});

it('returns a combined path and filename', function () {
    $this->expect($this->trait->combinePath('foo','bar.baz.txt'))
         ->toBe('foo/bar.baz.txt');
    $this->expect($this->trait->combinePath('foo/bar/baz/','/bar.baz.txt'))
         ->toBe('foo/bar/baz/bar.baz.txt');
});

it('returns the storage disk path', function () {
    $this->expect($this->trait->getStorageDisk())
         ->toBe('public');
});

it('returns the entity path', function () {
    $this->expect($this->trait->getStoragePath())
         ->toBe('uploads/posts/1');
});

it('returns the file name', function () {
    $this->expect($this->trait->getFileName('foo-bar/foo/bar.baz/foo.bar'))
         ->toBe('foo.bar');
});
