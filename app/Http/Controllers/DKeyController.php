<?php

namespace App\Http\Controllers;

use App\Models\DKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DKeyController extends Controller
{
    public function getBalance($userId)
    {
        if (auth()->guard('sanctum')->id() != $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $totalBalance = DKey::where('user_id', $userId)
                           ->active()
                           ->sum('key_remaining');

        $dKeyDetails = DKey::where('user_id', $userId)
                          ->active()
                          ->get()
                          ->map(function($key) {
                              return [
                                  'key_remaining' => $key->key_remaining,
                                  'expires_at' => $key->expires_at,
                                  'remaining_time' => now()->diffInHours($key->expires_at) . ' heures',
                                  'source_type' => $key->source_type
                              ];
                          });

        return response()->json([
            'total_balance' => $totalBalance,
            'dkey_details' => $dKeyDetails
        ]);
    }

    public function cleanExpiredKeys()
    {
    
        try {
            $expiredKeys = DKey::where('expires_at', '<=', now())
                              ->where('status', 'active')
                              ->update(['status' => 'expired']);

            return response()->json([
                'message' => 'Expired keys cleaned successfully',
                'expired_count' => $expiredKeys
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clean expired keys'
            ], 500);
        }
    }

    public function deductKeys(Request $request)
    {
        $userId = auth()->guard('sanctum')->id();

        try {
            $validated = $request->validate([
                'deduct_amount' => 'required|integer|min:1'
            ]);

            DB::beginTransaction();

            // Récupérer toutes les D-keys de l'utilisateur, triées par source (ad_reward, purchase, subscription)
            $earnedKeys = DKey::where('user_id', $userId)
                            ->where('source_type', 'ad_reward') // D-keys gagnées
                            ->where('status', 'active')
                            ->orderBy('expires_at')
                            ->get();

            $purchasedOrSubscribedKeys = DKey::where('user_id', $userId)
                                            ->whereIn('source_type', ['purchase', 'subscription']) // D-keys achetées et souscrites
                                            ->where('status', 'active')
                                            ->orderBy('expires_at')
                                            ->get();

            $remainingToDeduct = $validated['deduct_amount'];

            // D'abord, déduire les D-keys gagnées (ad_reward)
            foreach ($earnedKeys as $key) {
                if ($remainingToDeduct <= 0) break;

                if ($key->key_remaining <= $remainingToDeduct) {
                    $remainingToDeduct -= $key->key_remaining;
                    $key->key_remaining = 0;
                    $key->status = 'expired'; // Expirer la clé une fois utilisée
                } else {
                    $key->key_remaining -= $remainingToDeduct;
                    $remainingToDeduct = 0;
                }

                $key->save();
            }

            // Si des D-keys restent à déduire, alors suspendre les D-keys achetées ou souscrites
            if ($remainingToDeduct > 0) {
                foreach ($purchasedOrSubscribedKeys as $key) {
                    if ($remainingToDeduct <= 0) break;

                    // Suspendre la clé achetée ou souscrite en la mettant en statut 'paused'
                    $key->status = 'paused'; 

                    if ($key->key_remaining <= $remainingToDeduct) {
                        $remainingToDeduct -= $key->key_remaining;
                        $key->key_remaining = 0;
                    } else {
                        $key->key_remaining -= $remainingToDeduct;
                        $remainingToDeduct = 0;
                    }

                    $key->save();
                }
            }

            // Si après tout ça, il reste encore des D-keys à déduire, renvoyer une erreur
            if ($remainingToDeduct > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient D-Keys balance'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'D-Keys deducted successfully',
                'new_balance' => $this->getBalance($userId) // Mettre à jour le solde des D-keys
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deduct D-Keys: ' . $e->getMessage()
            ], 500);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereDate('expires_at', '>=', now());
    }


}