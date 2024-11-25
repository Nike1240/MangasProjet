<?php

namespace App\Http\Controllers;
use App\Models\Package;
use App\Models\DKey;
use App\Models\DKeyTransaction;
use App\Services\DKeyPricingService;
use Illuminate\Http\Request;

class DKeyPurchaseController extends Controller
{
    protected $pricingService;

    public function __construct(DKeyPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function initiatePurchase(Request $request)
    {
        $request->validate([
            'pack_type' => 'required|string',
            'quantity' => $request->pack_type === 'standard_dkey' ? 'required|integer|min:1' : 'nullable',
            'package_name' => $request->pack_type !== 'standard_dkey' ? 'required|string' : 'nullable',
        ]);

        try {
            if ($request->pack_type === 'standard_dkey') {
                $priceDetails = $this->pricingService->calculatePrice(
                    $request->quantity,
                    $request->pack_type
                );
            } else {
                $priceDetails = $this->pricingService->getPackPrice(
                    $request->pack_type,
                    $request->package_name
                );
            }

            $transaction = DKeyTransaction::create([
                'user_id' => auth()->guard('sanctum')->id(),
                'package_id' => $priceDetails['package_id'],
                'discount_id' => $priceDetails['discount_id'],
                'quantity' => $priceDetails['quantity'] === 'unlimited' ? null : $priceDetails['quantity'],
                'unit_price' => $priceDetails['unit_price'],
                'pack_price' => $priceDetails['pack_price'],
                'discount_amount' => $priceDetails['discount_amount'] ?? 0,
                'total_amount' => $priceDetails['total_price'],
                'payment_method' => 'pending',
                'status' => 'pending',
                'transaction_reference' => uniqid('DK-')
            ]);

            return response()->json([
                'transaction_id' => $transaction->id,
                'price_details' => $priceDetails,
                'payment_url' => route('test-payment.process', $transaction->id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'initialisation de l\'achat',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function packs(){
        $package = Package :: get(); 
        return response()->json([
            'packs' => $package
        ]);
    }
}