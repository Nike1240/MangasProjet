<?php

namespace Database\Factories;

use App\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(),
            'chapter_number' => $this->faker->unique()->numberBetween(1, 100),
            'description' => $this->faker->paragraph(),
            'is_published' => $this->faker->boolean(80),
            'views' => $this->faker->numberBetween(0, 10000),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Chapter $chapter) {
            // Créer quelques pages pour ce chapitre
            for ($i = 1; $i <= 20; $i++) {
                $chapter->pages()->create([
                    'image_path' => "chapters/{$chapter->id}/page_{$i}.jpg",
                    'page_number' => $i
                ]);
            }
        });
    }
}

