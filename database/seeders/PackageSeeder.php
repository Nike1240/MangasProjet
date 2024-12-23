<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Package;
use Carbon\Carbon;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        // Configuration des DKeys standards
        DB::table('packages')->insert([
            [
                'package_type_id' => 1, // standard_dkey
                'name' => 'DKey Standard',
                'is_pack' => false,
                'unit_price' => 10.00,
                'pack_price' => null,
                'pack_quantity' => null,
                'duration' => '86400',
                'pages_per_dkey' => 100,
                'episodes_per_dkey' => 10,
                'min_quantity' => 1,
                'max_quantity' => 10,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        
            // Configuration des packs
            [
                'package_type_id' => 2, // pack
                'name' => 'Pack Découverte',
                'is_pack' => true,
                'unit_price' => null,
                'pack_price' => 25.00,
                'pack_quantity' => 10,
                'duration' => '10000',
                'pages_per_dkey' => 100,
                'episodes_per_dkey' => 10,
                'min_quantity' => 1,
                'max_quantity' => null,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'package_type_id' => 2, // pack
                'name' => 'Pack Premium',
                'is_pack' => true,
                'unit_price' => null,
                'pack_price' => 45.00,
                'pack_quantity' => 15,
                'duration' => '100000',
                'pages_per_dkey' => 150,
                'episodes_per_dkey' => 15,
                'min_quantity' => 1,
                'max_quantity' => null,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        
            // Configuration des abonnements
            [
                'package_type_id' => 3, // subscription
                'name' => 'Abonnement Hebdomadaire',
                'is_pack' => false,
                'unit_price' => null,
                'pack_price' => 15.00,
                'pack_quantity' => 1000,
                'duration' => 'weekly',
                'pages_per_dkey' => 200,
                'episodes_per_dkey' => 20,
                'min_quantity' => 1,
                'max_quantity' => null,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'package_type_id' => 3, // subscription
                'name' => 'Abonnement Mensuel',
                'is_pack' => false,
                'unit_price' => null,
                'pack_price' => 49.99,
                'pack_quantity' => 2000,
                'duration' => 'monthly',
                'pages_per_dkey' => 300,
                'episodes_per_dkey' => 30,
                'min_quantity' => 1,
                'max_quantity' => null,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
        
        
    }
}
