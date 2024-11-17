<?php

namespace Database\Factories;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(),
            'season_number' => $this->faker->unique()->numberBetween(1, 10),
            'description' => $this->faker->paragraph(),
            'is_published' => $this->faker->boolean(80),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Season $season) {
            $season->episodes()->createMany(
                EpisodeFactory::new()->count(12)->make()->toArray()
            );
        });
    }
}

