<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        Admin::create([
            'name' => 'Moulikath',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password123')
        ]);
    }
}

