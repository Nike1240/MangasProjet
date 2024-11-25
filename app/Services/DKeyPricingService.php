<?php

namespace App\Services;

use App\Models\Package;

class DKeyPricingService
{
    public function calculatePrice(int $quantity, string $packType)
    {
        $priceConfig = Package::with('packageType')
            ->whereHas('packageType', function ($query) use ($packType) {
                $query->where('name', $packType)
                    ->where('is_active', true);
            })
            ->first();

        if (!$priceConfig) {
            throw new \Exception("Configuration de prix non trouvée pour le type de pack spécifié");
        }

        $totalPrice = 0;
        $discountAmount = 0;

        $unitPrice = (float) $priceConfig->unit_price;
        $totalPrice = $quantity * $unitPrice;

        // Appliquer une réduction en fonction de la quantité
        if ($quantity >= 50) {
            $discountAmount = $totalPrice * 0.1;
            $totalPrice -= $discountAmount;
        } elseif ($quantity >= 20) {
            $discountAmount = $totalPrice * 0.05;
            $totalPrice -= $discountAmount;
        }

        return [
            'package_id' => $priceConfig->id,
            'discount_id' => $discountAmount > 0 ? 1 : null,
            'unit_price' => $priceConfig->unit_price,
            'pack_price' => $totalPrice,
            'quantity' => $quantity,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice
        ];
    }

    public function getPackPrice(string $packType, string $packageName)
    {
        $priceConfig = Package::with('packageType')
            ->whereHas('packageType', function ($query) use ($packType) {
                $query->where('name', $packType)
                    ->where('is_active', true);
            })
            ->where('name', $packageName)
            ->first();

        if (!$priceConfig) {
            throw new \Exception("Configuration de prix non trouvée pour le type de pack et le package spécifiés");
        }

        $totalPrice = (float) $priceConfig->pack_price;
        $quantity = $priceConfig->pack_quantity;

        return [
            'package_id' => $priceConfig->id,
            'discount_id' => null,
            'unit_price' => $priceConfig->unit_price,
            'pack_price' => $totalPrice,
            'quantity' => $quantity,
            'discount_amount' => 0,
            'total_price' => $totalPrice
        ];
    }
}