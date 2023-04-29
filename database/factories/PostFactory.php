<?php

namespace Flatpack\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = \Flatpack\Tests\Models\Post::class;

    /**
      * Define the model's default state.
      *
      * @return array<string, mixed>
      */
    public function definition()
    {
        $title = fake()->text(40);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => $this->bodyBlocks(),
            'excerpt' => fake()->text(160),
            'picture' => null,
            'status' => fake()->boolean(),
        ];
    }

    /**
     * Returns JSON for the Post body
     *
     * @return string
     */
    protected function bodyBlocks()
    {
        return json_encode([
           "time" => fake()->unixTime(),
           "blocks" => [
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(200),
                 ],
                 "type" => "paragraph",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(100),
                 ],
                 "type" => "paragraph",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(210),
                    "caption" => fake()->text(20),
                    "alignment" => "left",
                 ],
                 "type" => "quote",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(100),
                    "level" => 2,
                 ],
                 "type" => "header",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(300),
                 ],
                 "type" => "paragraph",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(120),
                    "level" => 2,
                 ],
                 "type" => "header",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(320),
                 ],
                 "type" => "paragraph",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "file" => [
                       "url" => "https://picsum.photos/800/600",
                    ],
                    "caption" => fake()->text(30),
                    "stretched" => true,
                    "withBorder" => true,
                    "withBackground" => false,
                 ],
                 "type" => "image",
              ],
              [
                 "id" => uniqid(),
                 "data" => [],
                 "type" => "delimiter",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(80),
                    "level" => 3,
                 ],
                 "type" => "header",
              ],
              [
                 "id" => uniqid(),
                 "data" => [
                    "text" => fake()->text(620),
                 ],
                 "type" => "paragraph",
              ],
           ],
           "version" => "2.23.2",
        ]);
    }
}
