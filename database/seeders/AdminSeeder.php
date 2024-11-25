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
            'email' => 'admin@admin.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Moulikath',
            'last_name' => 'BADAROU',
            'phone_number' => '97294598',
            'adresse' => 'AKPAKPA',
            
        ]);
    }
}

