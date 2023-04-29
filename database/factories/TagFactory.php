<?php

namespace Flatpack\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = \Flatpack\Tests\Models\Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->words(
                fake()->randomElement([1, 2, 3]),
                true
            ),
        ];
    }
}
