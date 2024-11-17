<?php

namespace App\Http\Controllers;
use App\Models\DKeyTransaction;
use App\Models\DKeyConfiguration;
use App\Models\Package;
use App\Models\DKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestPaymentController extends Controller
{   
    public function showForm($transactionId)
    {
        $transaction = DKeyTransaction::findOrFail($transactionId);
        $configurations = DKeyConfiguration::all();
        return view('payment.test-form', compact('transaction', 'configurations'));
    }

    public function process(Request $request, $transactionId)
    {
        $request->validate([
            'subscription_type' => 'required|exists:package_types,name',
            'card_number' => 'required|size:16',
            'expiry_month' => 'required|digits:2',
            'expiry_year' => 'required|digits:2',
            'cvv' => 'required|digits:3'
        ]);

        $transaction = DKeyTransaction::findOrFail($transactionId);
        
        try {
            DB::beginTransaction();

            $transaction->update([
                'payment_method' => 'test_card',
                'status' => 'completed'
            ]);

            // Créer les DKeys
            $this->createDKeys($transaction);

            // Si c'est un abonnement, créer ou mettre à jour l'abonnement
            $package = Package::with('packageType')
                ->findOrFail($transaction->package_id);
            
            Log::info('Package Type:', [
                'package_id' => $package->id,
                'package_name' => $package->name,
                'package_type' => $package->packageType->name
            ]);
                
            if ($package->packageType->name === 'subscription') {
                // Déterminer le type d'abonnement
                $planType = null;
                $packageName = strtolower($package->name);
                
                Log::info('Package Name:', ['name' => $packageName]);
                
                if (str_contains($packageName, 'hebdomadaire')) {
                    $planType = 'weekly';
                } elseif (str_contains($packageName, 'mensuel')) {
                    $planType = 'monthly';
                } elseif (str_contains($packageName, 'illimité')) {
                    $planType = 'unlimited';
                }

                Log::info('Plan Type:', ['type' => $planType]);

                if ($planType) {
                    try {
                        $subscriptionController = app(SubscriptionController::class);
                        $subscriptionRequest = new Request(['plan_type' => $planType]);
                        $result = $subscriptionController->subscribe($subscriptionRequest, $transactionId);
                        Log::info('Subscription Result:', ['result' => $result]);
                    } catch (\Exception $e) {
                        Log::error('Subscription Creation Error:', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                } else {
                    Log::warning('No plan type determined for package:', [
                        'package_name' => $packageName
                    ]);
                }
            } else {
                Log::info('Not a subscription package');
            }

            DB::commit();

            return response()->json([
                'message' => 'Test payment processed successfully',
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment Processing Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    private function createDKeys(DKeyTransaction $transaction)
    {
        $userId = auth()->guard('sanctum')->id();
        
        // Charger le package avec son type
        $package = Package::with('packageType')
            ->findOrFail($transaction->package_id);
        
        if (!$package) {
            throw new \Exception("Package non trouvé");
        }

        // Déterminer le type de source et la durée
        $sourceType = $package->packageType->name === 'subscription' ? 'subscription' : 'purchase';
        $duration = null;
        
        if ($sourceType === 'subscription') {
            $packageName = strtolower($package->name);
            if (str_contains($packageName, 'hebdomadaire')) {
                $duration = 'weekly';
            } elseif (str_contains($packageName, 'mensuel')) {
                $duration = 'monthly';
            } elseif (str_contains($packageName, 'illimité')) {
                $duration = 'unlimited';
            }
        } else {
            $duration = $package->duration;
        }

        // Obtenir la date d'expiration
        $expirationDate = $this->getExpirationDate($sourceType, $duration);

        // Rechercher une DKey existante pour l'utilisateur
        $existingDKey = DKey::where('user_id', $userId)
            ->where('source_type', $sourceType)
            ->where('expires_at', '>', now()) // Vérifie si la clé n'est pas expirée
            ->first();

        if ($existingDKey) {
            // Mettre à jour la DKey existante
            $existingDKey->update([
                'key_remaining' => $existingDKey->key_remaining + $transaction->quantity,
                'expires_at' => max($existingDKey->expires_at, $expirationDate) // Prend la date d'expiration la plus éloignée
            ]);

            // Lier la transaction à la DKey existante
            $transaction->update([
                'dkey_id' => $existingDKey->id
            ]);
        } else {
            // Créer une nouvelle DKey si aucune n'existe
            $newDKey = DKey::create([
                'user_id' => $userId,
                'transaction_id' => $transaction->id,
                'key_remaining' => $transaction->quantity,
                'source_type' => $sourceType,
                'expires_at' => $expirationDate
            ]);
        }
    }

    private function getExpirationDate($transactionType, $duration = null)
{
    $now = now();

    switch ($transactionType) {
        case 'subscription':
            switch ($duration) {
                case 'weekly':
                    return $now->addWeek();
                case 'monthly':
                    return $now->addMonth();
                case 'unlimited':
                    return $now->addYear();
                default:
                    return $now->addDay();
            }
        case 'purchase':
            if ($duration) {
                // Convertir la durée en secondes en jours
                return $now->addSeconds((int)$duration);
            }
            return $now->addDay();
        case 'ad_reward':
            return $now->addHours(24);
        default:
            return $now->addDay();
    }
}
}