<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $tags = [
            ['name' => 'Shounen',],
            ['name' => 'Shoujo', ],
            ['name' => 'Seinen',],
            ['name' => 'Josei',],
            ['name' => 'Gore', ],
            ['name' => 'Ecchi', ],
            ['name' => 'School Life',],
            ['name' => 'Isekai', ],
            ['name' => 'Military',],
            ['name' => 'Music', ],
            ['name' => 'Psychological',],
            ['name' => 'Cooking',],
            ['name' => 'Martial Arts', ],
            ['name' => 'Super Power', ],
            ['name' => 'Magic',],
            ['name' => 'Harem',],
            ['name' => 'Gender Bender',],
            ['name' => 'Post-Apocalyptic', ],
            ['name' => 'Time Travel',],
            ['name' => 'Vampire', ],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
