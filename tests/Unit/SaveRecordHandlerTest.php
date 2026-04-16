<?php

declare(strict_types=1);

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(TestCase::class, RefreshDatabase::class);

test('save record handler supports writable fields from form schema', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'title' => 'Original title',
    ]);

    $request = Request::create('/flatpack/posts/' . $post->getKey(), 'PATCH', [
        'values' => [
            'title' => 'Updated from form schema',
        ],
    ]);

    $result = (new SaveRecordHandler())->handle(new FlatpackActionContext(
        request: $request,
        entity: 'posts',
        actionName: 'save',
        modelClass: Post::class,
        record: (string) $post->getKey(),
        compositionType: 'form',
        composition: [
            'model' => Post::class,
            'schema' => [
                'fields' => [
                    'title' => [
                        'type' => 'text',
                        'label' => 'Title',
                    ],
                ],
            ],
        ],
        schema: [
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
            ],
        ],
        model: $post,
    ));

    expect($result?->title)->toBe('Updated from form schema');
    expect($post->fresh()?->title)->toBe('Updated from form schema');
});
