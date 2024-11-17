<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Genre;



class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $genres = [
            ['name' => 'Action',],
            ['name' => 'Adventure',],
            ['name' => 'Comedy',],
            ['name' => 'Drama', ],
            ['name' => 'Fantasy',],
            ['name' => 'Horror',],
            ['name' => 'Mystery', ],
            ['name' => 'Romance',],
            ['name' => 'Sci-Fi',],
            ['name' => 'Slice of Life',],
            ['name' => 'Sports', ],
            ['name' => 'Supernatural',],
            ['name' => 'Thriller', ],
            ['name' => 'Historical',],
            ['name' => 'Mecha', ],
        ];

        foreach ($genres as $genre) {
            Genre::create($genre);
        }
    }
}
