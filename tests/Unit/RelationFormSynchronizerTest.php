<?php

declare(strict_types=1);

use Flatpack\Actions\RelationFormSynchronizer;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\PostComment;
use Flatpack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

uses(TestCase::class);

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
