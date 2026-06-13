<?php

declare(strict_types=1);

use Flatpack\Actions\RelationFormSynchronizer;
use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\PostComment;
use Flatpack\Tests\Models\PostMeta;
use Flatpack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('RelationFormSynchronizer returns immediately when schema is null', function () {
    $sync = new RelationFormSynchronizer;
    $post = new Post;

    expect(fn () => $sync->sync($post, null, ['any' => 'value']))->not->toThrow(Throwable::class);
});

test('RelationFormSynchronizer invokes file-upload callback with normalized payload', function () {
    $sync = new RelationFormSynchronizer;

    $model = new class extends Model
    {
        /** @var list<object> */
        public array $received = [];

        protected $table = 'posts';

        public function comments(): MorphMany
        {
            return $this->morphMany(PostComment::class, 'commentable');
        }

        public function processGallery(array $items): void
        {
            $this->received = $items;
        }
    };

    $sync->sync($model, [
        'fields' => [
            'gallery' => [
                'id' => 'gallery',
                'type' => 'file-upload',
                'mode' => 'relation',
                'upload' => [],
                'relation' => 'comments',
                'callback' => 'processGallery',
                'label' => 'Gallery',
            ],
        ],
    ], [
        'gallery' => [
            ['path' => 'a.png', 'url' => '/storage/a.png'],
            ['path' => 'b.png', 'url' => '/storage/b.png'],
        ],
    ]);

    expect($model->received)->toHaveCount(2)
        ->and($model->received[0])->toBeInstanceOf(stdClass::class)
        ->and($model->received[0]->path)->toBe('a.png')
        ->and($model->received[0]->url)->toBe('/storage/a.png')
        ->and($model->received[1]->path)->toBe('b.png')
        ->and($model->received[1]->url)->toBe('/storage/b.png');
});

test('RelationFormSynchronizer skips when fields is not an array', function () {
    $sync = new RelationFormSynchronizer;
    $post = Post::factory()->create();

    $sync->sync($post, [
        'fields' => 'not-an-array',
    ], []);

    expect(true)->toBeTrue();
});

test('RelationFormSynchronizer skips non-table relation field definitions', function () {
    $sync = new RelationFormSynchronizer;
    $post = Post::factory()->create();

    $sync->sync($post, [
        'fields' => [
            'category' => [
                'id' => 'category',
                'type' => 'combobox',
                'relation' => 'category',
                'label' => 'Category',
            ],
        ],
    ], [
        'category' => 1,
    ]);

    expect(true)->toBeTrue();
});

test('RelationFormSynchronizer syncs belongsToMany ids from payload', function () {
    $sync = new RelationFormSynchronizer;
    $post = Post::factory()->create();
    $catA = Category::factory()->create();
    $catB = Category::factory()->create();

    $sync->sync($post, [
        'fields' => [
            'tags' => [
                'id' => 'tags',
                'type' => 'combobox',
                'multiple' => true,
                'relation' => 'categories',
                'label' => 'Tags',
            ],
        ],
    ], [
        'tags' => [
            ['id' => $catA->getKey()],
            ['id' => $catB->getKey()],
        ],
    ]);

    /** @var Post $reloaded */
    $reloaded = $post->fresh();
    expect($reloaded)->not->toBeNull();

    $syncedIds = $reloaded->categories()->pluck('categories.id')->map(static fn ($id): string => (string) $id)->sort()->values()->all();

    expect($syncedIds)->toBe([(string) $catA->getKey(), (string) $catB->getKey()]);
});

test('RelationFormSynchronizer creates updates and deletes morphMany rows for inline table', function () {
    $sync = new RelationFormSynchronizer;
    /** @var Post $post */
    $post = Post::factory()->create();
    $keep = PostComment::factory()->create([
        'commentable_id' => $post->getKey(),
        'commentable_type' => Post::class,
        'content' => 'Old',
    ]);
    PostComment::factory()->create([
        'commentable_id' => $post->getKey(),
        'commentable_type' => Post::class,
        'content' => 'Remove me',
    ]);

    $sync->sync($post, [
        'fields' => [
            'comments' => [
                'id' => 'comments',
                'type' => 'table',
                'relation' => 'comments',
                'columns' => [
                    ['id' => 'content', 'type' => 'text', 'label' => 'Content'],
                ],
                'label' => 'Comments',
            ],
        ],
    ], [
        'comments' => [
            ['id' => $keep->getKey(), 'content' => 'Updated'],
            ['content' => 'Brand new'],
        ],
    ]);

    $comments = $post->fresh()?->comments()->orderBy('id')->get();
    expect($comments)->toHaveCount(2)
        ->and($comments->firstWhere('id', $keep->getKey())?->content)->toBe('Updated')
        ->and($comments->pluck('content')->contains('Brand new'))->toBeTrue();
});

test('RelationFormSynchronizer replaces hasOne child from payload', function () {
    $sync = new RelationFormSynchronizer;
    /** @var Post $post */
    $post = Post::factory()->create();

    $sync->sync($post, [
        'fields' => [
            'meta' => [
                'id' => 'meta',
                'type' => 'table',
                'relation' => 'meta',
                'columns' => [
                    ['id' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle'],
                ],
                'label' => 'Meta',
            ],
        ],
    ], [
        'meta' => [
            'subtitle' => 'Inline meta',
        ],
    ]);

    expect($post->fresh()?->meta?->subtitle)->toBe('Inline meta');
});

test('RelationFormSynchronizer deletes hasOne child when payload is empty', function () {
    $sync = new RelationFormSynchronizer;
    /** @var Post $post */
    $post = Post::factory()->create();
    PostMeta::query()->create([
        'post_id' => $post->getKey(),
        'subtitle' => 'Was here',
    ]);

    $sync->sync($post, [
        'fields' => [
            'meta' => [
                'id' => 'meta',
                'type' => 'table',
                'relation' => 'meta',
                'columns' => [
                    ['id' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle'],
                ],
                'label' => 'Meta',
            ],
        ],
    ], [
        'meta' => [],
    ]);

    expect($post->fresh()?->meta)->toBeNull();
});

test('RelationFormSynchronizer leaves file-upload relation when callback is missing', function () {
    $sync = new RelationFormSynchronizer;

    $model = new class extends Model
    {
        protected $table = 'posts';

        public function comments(): MorphMany
        {
            return $this->morphMany(PostComment::class, 'commentable');
        }
    };

    $sync->sync($model, [
        'fields' => [
            'gallery' => [
                'id' => 'gallery',
                'type' => 'file-upload',
                'mode' => 'relation',
                'upload' => [],
                'relation' => 'comments',
                'label' => 'Gallery',
            ],
        ],
    ], [
        'gallery' => [['path' => 'a.png']],
    ]);

    expect(true)->toBeTrue();
});

test('RelationFormSynchronizer ignores non-public file-upload callback', function () {
    $sync = new RelationFormSynchronizer;

    $model = new class extends Model
    {
        /** @var list<object> */
        public array $received = [];

        protected $table = 'posts';

        public function comments(): MorphMany
        {
            return $this->morphMany(PostComment::class, 'commentable');
        }

        protected function handleUploads(array $items): void
        {
            $this->received = $items;
        }
    };

    $sync->sync($model, [
        'fields' => [
            'gallery' => [
                'id' => 'gallery',
                'type' => 'file-upload',
                'mode' => 'relation',
                'upload' => [],
                'relation' => 'comments',
                'callback' => 'handleUploads',
                'label' => 'Gallery',
            ],
        ],
    ], [
        'gallery' => [['path' => 'a.png']],
    ]);

    expect($model->received)->toBeEmpty();
});

test('RelationFormSynchronizer passes object rows through file-upload payload normalization', function () {
    $sync = new RelationFormSynchronizer;

    $model = new class extends Model
    {
        /** @var list<object> */
        public array $received = [];

        protected $table = 'posts';

        public function comments(): MorphMany
        {
            return $this->morphMany(PostComment::class, 'commentable');
        }

        public function takeFiles(array $items): void
        {
            $this->received = $items;
        }
    };

    $item = (object) ['path' => 'solo.png'];
    $sync->sync($model, [
        'fields' => [
            'gallery' => [
                'id' => 'gallery',
                'type' => 'file-upload',
                'mode' => 'relation',
                'upload' => [],
                'relation' => 'comments',
                'callback' => 'takeFiles',
                'label' => 'Gallery',
            ],
        ],
    ], [
        'gallery' => [$item],
    ]);

    expect($model->received)->toHaveCount(1)
        ->and($model->received[0])->toBe($item);
});

test('RelationFormSynchronizer yields empty file-upload list when payload is not an array', function () {
    $sync = new RelationFormSynchronizer;

    $model = new class extends Model
    {
        /** @var list<object> */
        public array $received = [];

        protected $table = 'posts';

        public function comments(): MorphMany
        {
            return $this->morphMany(PostComment::class, 'commentable');
        }

        public function takeFiles(array $items): void
        {
            $this->received = $items;
        }
    };

    $sync->sync($model, [
        'fields' => [
            'gallery' => [
                'id' => 'gallery',
                'type' => 'file-upload',
                'mode' => 'relation',
                'upload' => [],
                'relation' => 'comments',
                'callback' => 'takeFiles',
                'label' => 'Gallery',
            ],
        ],
    ], [
        'gallery' => 'not-array',
    ]);

    expect($model->received)->toBeEmpty();
});
