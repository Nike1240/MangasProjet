<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Discount;
use Carbon\Carbon;


class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('discounts')->insert([
            [
                'package_id' => 1, // Pour les DKeys standards
                'name' => 'Réduction quantité (5+ DKeys)',
                'type' => 'percentage',
                'value' => 10.00, // 10% de réduction
                'min_quantity' => 5,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'package_id' => 4, // Pour l'abonnement mensuel
                'name' => 'Promo lancement mensuel',
                'type' => 'fixed_amount',
                'value' => 10.00, // 10€ de réduction
                'min_quantity' => null, // Pas de quantité minimum pour cet abonnement
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(1),
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
        
    }
}
