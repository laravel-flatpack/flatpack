<?php

declare(strict_types=1);

namespace Flatpack\Tests\Database\Factories;

use Flatpack\Tests\Models\PostComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostComment>
 */
final class PostCommentFactory extends Factory
{
    protected $model = PostComment::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(),
            'user_id' => null,
        ];
    }
}
