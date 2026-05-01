<?php

declare(strict_types=1);

use Flatpack\Actions\RelationFormSynchronizer;
use Flatpack\Tests\Models\Post;

test('RelationFormSynchronizer returns immediately when schema is null', function () {
    $sync = new RelationFormSynchronizer;
    $post = new Post;

    expect(fn () => $sync->sync($post, null, ['any' => 'value']))->not->toThrow(Throwable::class);
});
