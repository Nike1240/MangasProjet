<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\DKeyTransaction;
use App\Models\Package;
use App\Models\DKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    private $subscriptionKeyRewards = [
        'weekly' => 5,    
        'monthly' => 25,  
        'unlimited' => 100 
    ];

    public function subscribe(Request $request, $transactionId)
    {
        Log::info('Starting subscription creation', [
            'transaction_id' => $transactionId,
            'plan_type' => $request->plan_type
        ]);

        $userId = auth()->guard('sanctum')->id();
        
        // Récupérer la transaction et le package associé
        $transaction = DKeyTransaction::findOrFail($transactionId);
        $package = Package::findOrFail($transaction->package_id);

        DB::beginTransaction();
        try {
            $now = now();
            $endDate = $this->getPlanDuration($request->plan_type);

            // Vérifier si un abonnement actif existe déjà
            $existingSubscription = Subscription::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                // Mettre à jour l'abonnement existant
                $existingSubscription->update([
                    'end_date' => max($existingSubscription->end_date, $endDate),
                    'package_id' => $package->id,
                    'transaction_id' => $transactionId,
                    'price_paid' => $transaction->total_amount,
                    'status' => 'active'
                ]);
                
                $subscription = $existingSubscription;
                Log::info('Updated existing subscription');
            } else {
                // Créer un nouvel abonnement
                $subscription = Subscription::create([
                    'user_id' => $userId,
                    'package_id' => $package->id,
                    'transaction_id' => $transactionId,
                    'start_date' => $now,
                    'end_date' => $endDate,
                    'price_paid' => $transaction->total_amount,
                    'status' => 'active'
                ]);
                Log::info('Created new subscription');
            }

            // Attribuer les clés de récompense
            $this->giveSubscriptionRewardKeys($userId, $request->plan_type, $endDate);

            DB::commit();
            Log::info('Subscription saved successfully', [
                'subscription' => $subscription->toArray()
            ]);

            return response()->json([
                'message' => 'Subscription processed successfully',
                'subscription' => $subscription
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function giveSubscriptionRewardKeys($userId, $planType, $expirationDate)
    {
        $keyAmount = $this->subscriptionKeyRewards[$planType] ?? 0;
        
        if ($keyAmount > 0) {
            // Vérifier s'il existe déjà une DKey de type subscription
            $existingDKey = DKey::where('user_id', $userId)
                ->where('source_type', 'subscription')  // Changé de 'subscription_reward' à 'subscription'
                ->where('expires_at', '>', now())
                ->first();

            if ($existingDKey) {
                // Mettre à jour la DKey existante
                $existingDKey->update([
                    'key_remaining' => $existingDKey->key_remaining + $keyAmount,
                    'expires_at' => max($existingDKey->expires_at, $expirationDate)
                ]);

                Log::info('Updated existing reward keys', [
                    'user_id' => $userId,
                    'added_keys' => $keyAmount,
                    'total_keys' => $existingDKey->key_remaining + $keyAmount
                ]);
            } else {
                // Créer une nouvelle DKey
                DKey::create([
                    'user_id' => $userId,
                    'key_remaining' => $keyAmount,
                    'source_type' => 'subscription',  // Changé de 'subscription_reward' à 'subscription'
                    'expires_at' => $expirationDate
                ]);

                Log::info('Created new reward keys', [
                    'user_id' => $userId,
                    'keys' => $keyAmount
                ]);
            }
        }
    }

    private function getPlanDuration($planType)
    {
        $now = now();

        switch ($planType) {
            case 'weekly':
                return $now->copy()->addWeek();
            case 'monthly':
                return $now->copy()->addMonth();
            case 'unlimited':
                return $now->copy()->addYear();
            default:
                return $now->copy()->addMonth();
        }
    }
}