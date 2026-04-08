<?php

declare(strict_types=1);

namespace Flatpack\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class CategoryFactory extends Factory
{
    protected $model = \Flatpack\Tests\Models\Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = fake()->text(20);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
