<?php

namespace App\Services;

use App\Models\DKey;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DKeyConsumptionService
{
    /**
     * Vérifie si l'utilisateur peut accéder au contenu
     */
    public function canAccessContent(User $user): bool
    {
        return DKey::where('user_id', $user->id)
                  ->active()
                  ->sum('key_remaining') > 0;
    }

    /**
     * Gère la consommation de pages pour un manga
     */
    public function handlePageConsumption(User $user, Package $package, int $pagesRead): array
    {
        if (!$this->canAccessContent($user)) {
            return [
                'success' => false,
                'message' => 'Insufficient DKeys balance'
            ];
        }

        $dKeysToDeduct = (int) ceil($pagesRead / $package->pages_per_dkey);
        
        if ($dKeysToDeduct > 0) {
            return $this->deductDKeys($user, $dKeysToDeduct);
        }

        return [
            'success' => true,
            'message' => 'No DKeys needed for this consumption'
        ];
    }

    /**
     * Gère la consommation d'épisodes pour un anime
     */
    public function handleEpisodeConsumption(User $user, Package $package, int $episodesWatched): array
    {
        if (!$this->canAccessContent($user)) {
            return [
                'success' => false,
                'message' => 'Insufficient DKeys balance'
            ];
        }

        $dKeysToDeduct = (int) ceil($episodesWatched / $package->episodes_per_dkey);

        if ($dKeysToDeduct > 0) {
            return $this->deductDKeys($user, $dKeysToDeduct);
        }

        return [
            'success' => true,
            'message' => 'No DKeys needed for this consumption'
        ];
    }

    /**
     * Déduit les DKeys du solde de l'utilisateur
     */
    private function deductDKeys(User $user, int $amount): array
    {
        try {
            DB::beginTransaction();

            $activeKeys = DKey::where('user_id', $user->id)
                            ->active()
                            ->orderBy('expires_at')
                            ->get();

            $totalAvailable = $activeKeys->sum('key_remaining');

            if ($totalAvailable < $amount) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Insufficient DKeys balance',
                    'required' => $amount,
                    'available' => $totalAvailable
                ];
            }

            $remainingToDeduct = $amount;

            foreach ($activeKeys as $key) {
                if ($remainingToDeduct <= 0) break;

                if ($key->key_remaining <= $remainingToDeduct) {
                    $remainingToDeduct -= $key->key_remaining;
                    $key->key_remaining = 0;
                    $key->status = 'consumed';
                } else {
                    $key->key_remaining -= $remainingToDeduct;
                    $remainingToDeduct = 0;
                }

                $key->save();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'DKeys deducted successfully',
                'deducted' => $amount,
                'remaining' => $totalAvailable - $amount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to deduct DKeys: ' . $e->getMessage()
            ];
        }
    }
}