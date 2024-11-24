<?php

namespace App\Services;

use App\Models\User;
use App\Models\Content;
use App\Models\UserContentProgression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FreeAccessService
{
    private const FREE_MANGA_LIMIT = 10;
    private const FREE_ANIME_LIMIT = 5;

    /**
     * Vérifie si l'utilisateur a encore des accès gratuits disponibles
     */
    public function hasFreeAccessRemaining(User $user, Content $content): bool
    {
        $contentType = $content->type;
        $freeLimit = $this->getFreeLimit($contentType);
        $consumedCount = $this->getConsumedCount($user, $contentType);

        return $consumedCount < $freeLimit;
    }

    /**
     * Obtient la limite gratuite en fonction du type de contenu
     */
    private function getFreeLimit(string $contentType): int
    {
        return $contentType === Content::TYPE_MANGA 
            ? self::FREE_MANGA_LIMIT 
            : self::FREE_ANIME_LIMIT;
    }

    /**
     * Obtient le nombre de contenus déjà consommés par type
     */
    private function getConsumedCount(User $user, string $contentType): int
    {
        return UserContentProgression::where('user_id', $user->id)
            ->whereHas('content', function ($query) use ($contentType) {
                $query->where('type', $contentType);
            })
            ->sum('accessed_count');
    }

    /**
     * Incrémente le compteur de consommation pour un contenu
     */

    /**
     * Obtient le nombre d'accès gratuits restants
     */
    public function getRemainingFreeAccess(User $user, string $contentType): int
    {
        $freeLimit = $this->getFreeLimit($contentType);
        $consumedCount = $this->getConsumedCount($user, $contentType);

        return max(0, $freeLimit - $consumedCount);
    }

    public function initializeFreeAccess(User $user): void
    {
        try {
            DB::beginTransaction();

            // Initialiser le suivi pour manga et anime avec un compteur à 0
            foreach ([Content::TYPE_MANGA, Content::TYPE_ANIME] as $contentType) {
                UserContentProgression::create([
                    'user_id' => $user->id,
                    'content_type' => $contentType,
                    'accessed_count' => 0,
                    'last_accessed_at' => now()
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'initialisation des accès gratuits pour l\'utilisateur ID: ' . $user->id . '. Détails: ' . $e->getMessage());
            throw $e;
        }
    }
}