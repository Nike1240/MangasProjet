<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition()
    {
        return [
            'title' => $this->faker->unique()->sentence(3),
            'cover_image' => 'covers/default.jpg',
            'type' => $this->faker->randomElement(['manga', 'anime']),
            'status' => $this->faker->randomElement(['ongoing', 'completed']),
            'description' => $this->faker->paragraphs(3, true),
            'author_id' => User::factory(),
            'views' => $this->faker->numberBetween(0, 100000),
            'is_featured' => $this->faker->boolean(20),
            'is_published' => $this->faker->boolean(80),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Content $content) {
            $content->genres()->attach(
                \App\Models\Genre::inRandomOrder()->limit(3)->pluck('id')
            );
            $content->tags()->attach(
                \App\Models\Tag::inRandomOrder()->limit(2)->pluck('id')
            );
        });
    }
}
