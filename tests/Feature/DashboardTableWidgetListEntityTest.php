<?php

declare(strict_types=1);

use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

test('widget schema normalizer infers list_entity using composition scan', function () {
    $temp = sys_get_temp_dir() . '/flatpack-widget-list-entity-' . uniqid('', true);
    try {
        File::ensureDirectoryExists($temp . '/posts');
        File::put(
            $temp . '/posts/list.yaml',
            "name: Posts\nmodel: Flatpack\\Tests\\Models\\Post\n",
        );
        config()->set('flatpack.composition.path', $temp);

        /** @var WidgetSchemaNormalizer $normalizer */
        $normalizer = app(WidgetSchemaNormalizer::class);
        $out = $normalizer->normalize([
            'widgets' => [
                'recent_posts' => [
                    'type' => 'table',
                    'model' => Post::class,
                    'columns' => [
                        'title' => [
                            'label' => 'Title',
                        ],
                    ],
                ],
            ],
        ]);

        expect($out['widgets']['recent_posts']['list_entity'] ?? null)->toBe('posts');
    } finally {
        if (is_dir($temp)) {
            File::deleteDirectory($temp);
        }
    }
});
