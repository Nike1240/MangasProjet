<?php

namespace App\Services;

use App\Models\User;
use App\Models\Package;
use App\Models\DKey;
use App\Models\Content;
use App\Models\UserContentProgression;
use App\Models\DKeyTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DKeyConsumptionService
{

    private FreeAccessService $freeAccessService;

    public function __construct(FreeAccessService $freeAccessService)
    {
        $this->freeAccessService = $freeAccessService;
    }
    // public function attributeFreeDKeys(User $user): void
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Récupérer le package standard
    //         $standardPackage = Package::where('is_active', true)
    //             ->where('name', 'DKey Standard')
    //             ->first();

    //         if (!$standardPackage) {
    //             Log::warning('Package standard non trouvé pour l\'utilisateur ID: ' . $user->id);
    //             throw new \RuntimeException('Le package standard pour les DKeys gratuites n\'est pas disponible.');
    //         }

    //         // Créer les DKeys gratuites
    //         DKey::create([
    //             'user_id' => $user->id,
    //             'package_id' => $standardPackage->id,
    //             'key_remaining' => 3,
    //             'source_type' => 'ad_reward',
    //             'status' => DKey::STATUS_ACTIVE,
    //             'expires_at' => now()->addDays(7),
    //         ]);

    //         // Enregistrer la transaction
    //         DKeyTransaction::create([
    //             'user_id' => $user->id,
    //             'package_id' => $standardPackage->id,
    //             'quantity' => 3,
    //             'total_amount' => 0,
    //             'status' => 'completed',
    //             'payment_method' => 'signup',
    //             'transaction_reference' => 'free_signup_' . $user->id,
    //         ]);

    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Erreur lors de l\'attribution des DKeys gratuites pour l\'utilisateur ID: ' . $user->id . '. Détails: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }

    /**
     * Vérifie si l'utilisateur peut accéder au contenu
     */
        public function canAccessContent(User $user, Content $content): bool
        {
            // Vérifier l'abonnement actif
            if ($this->hasActiveSubscription($user)) {
                return true;
            }

            // Vérifier la limite gratuite
            if ($this->hasFreeAccessRemaining($user, $content)) {
                return true;
            }

            // Vérifier les DKeys disponibles
            return $this->hasAvailableDKeys($user);
        }

        protected function hasActiveSubscription(User $user): bool
        {
            return $user->subscriptions()
                ->where('status', 'active')
                ->whereDate('end_date', '>=', now())
                ->exists();
        }

        protected function hasFreeAccessRemaining(User $user, Content $content): bool
        {
            return $this->freeAccessService->hasFreeAccessRemaining($user, $content);
        }

        protected function hasAvailableDKeys(User $user): bool
        {
            $totalDKeys = DKey::where('user_id', $user->id)
                ->active()
                ->sum('key_remaining');

            return $totalDKeys > 0;
        }
        private function getTotalContentConsume(User $user, string $contentType): int
        {
            // Valider le type de contenu
            $validContentTypes = [Content::TYPE_MANGA, Content::TYPE_ANIME];
            if (!in_array($contentType, $validContentTypes, true)) {
                throw new \InvalidArgumentException('Type de contenu invalide : ' . $contentType);
            }

            // Récupérer tous les contenus de ce type
            $contentIds = Content::where('type', $contentType)->pluck('id');

            // Calculer la consommation totale pour ces contenus
            return UserContentProgression::where('user_id', $user->id)
                ->whereIn('content_id', $contentIds)
                ->sum('accessed_count');
        }


    /**
     * Consommer du contenu et déduire les DKeys
     */
    public function consumeContent(User $user, Content $content, int $currentProgress): array
    {
        // Vérifier l'accès initial
        if (!$this->canAccessContent($user, $content)) {
            return [
                'success' => false,
                'message' => 'Aucun abonnement actif ou solde de DKeys insuffisant'
            ];
        }


        // Si c'est un accès gratuit, incrémenter le compteur
        if ($this->hasFreeAccessRemaining($user, $content)) {
            $this->canAccessContent($user, $content);
            return [
                'success' => true,
                'message' => 'Accès gratuit utilisé',
                'dkey_deduced' => false
            ];
        }
        // Récupérer le package par défaut
        // $packageDefaut = Package::where('is_active', true)->first();


        $packageDefaut = Package::where('is_active', true)
        ->whereIn('id', function ($query) use ($user) {
            $query->select('package_id')
                ->from('d_key_transactions')
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->union(
                    DB::table('d_keys')
                        ->select('package_id') 
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->where('expires_at', '>=', now()) 
                );
        })
        ->orderByDesc('pack_quantity') 
        ->first();
    
        
        if (!$packageDefaut) {
            throw new \RuntimeException('Aucun package par défaut trouvé');
        }

        // Déterminer le type de contenu et la limite par DKey
        $estManga = $content->type === 'manga';
        $estAnime = $content->type === 'anime';

        if (!$estManga && !$estAnime) {
            throw new \InvalidArgumentException('Type de contenu non supporté');
        }

        $limiteParDKey = $estManga 
            ? $packageDefaut->pages_per_dkey 
            : $packageDefaut->episodes_per_dkey;

        // Vérifier si un nouveau DKey doit être déduit
        $dKeyADeduire = floor($currentProgress / $limiteParDKey) > 
                        floor(($currentProgress - 1) / $limiteParDKey);

        if ($dKeyADeduire) {
            $resultDeduction = $this->deduireDKeys($user, 1);
            
            if (!$resultDeduction['succes']) {
                return $resultDeduction;
            }
        }

        return [
            'success' => true,
            'message' => $dKeyADeduire 
                ? 'DKey déduite pour la progression' 
                : 'Progression en cours',
            'dkey_deduced' => $dKeyADeduire
        ];
    }

    /**
     * Calculer les DKeys requis pour la consommation
     */
    private function calculerDetailsConsommation(Content $content): array
    {
        $estManga = $content->type === 'manga';
        $estAnime = $content->type === 'anime';

        if (!$estManga && !$estAnime) {
            throw new \InvalidArgumentException('Type de contenu non supporté');
        }

        // Package par défaut
        $packageDefaut = Package::where('is_active', true)
        ->whereIn('id', function ($query) use ($user) {
            $query->select('package_id')
                ->from('d_key_transactions')
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->union(
                    DB::table('d_keys')
                        ->select('package_id') 
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->where('expires_at', '>=', now()) 
                );
        })
        ->orderByDesc('pack_quantity') 
        ->first();

        if (!$packageDefaut) {
            throw new \RuntimeException('Aucun package par défaut trouvé');
        }

        $unitesAConsommer = $estManga 
            ? $content->chapters()->count() 
            : $content->episodes()->count();

        $uniteConsommation = $estManga 
            ? $packageDefaut->pages_per_dkey 
            : $packageDefaut->episodes_per_dkey;

        return [
            'dkeys_requis' => max(1, ceil($unitesAConsommer / $uniteConsommation)),
            'type_contenu' => $estManga ? 'manga' : 'anime'
        ];
    }

    /**
     * Déduire les DKeys du solde de l'utilisateur
     */
    private function deduireDKeys(User $user, int $montant): array
    {
        try {
            DB::beginTransaction();

            // Priorité aux clés de récompense publicitaire
            $clesDuReward = DKey::where('user_id', $user->id)
                ->where('source_type', 'ad_reward')
                ->where('status', 'active')
                ->orderBy('expires_at')
                ->get();

            $clesDachat = DKey::where('user_id', $user->id)
                ->whereIn('source_type', ['purchase', 'subscription'])
                ->where('status', 'active')
                ->orderBy('expires_at')
                ->get();

            $resteADeduire = $montant;

            // Déduire des clés de récompense d'abord
            foreach ($clesDuReward as $cle) {
                if ($resteADeduire <= 0) break;

                $resteADeduire = $this->majSoldeCle($cle, $resteADeduire);
            }

            // Si besoin, déduire des clés achetées/abonnées
            if ($resteADeduire > 0) {
                foreach ($clesDachat as $cle) {
                    if ($resteADeduire <= 0) break;
                    $resteADeduire = $this->majSoldeCle($cle, $resteADeduire);
                }
            }

            // Vérifier si montant total déduit
            if ($resteADeduire > 0) {
                DB::rollBack();
                return [
                    'succes' => false,
                    'message' => 'Solde de DKeys insuffisant',
                    'requis' => $montant,
                    'disponible' => $montant - $resteADeduire
                ];
            }

            DB::commit();

            return [
                'succes' => true,
                'message' => 'DKeys déduites avec succès',
                'deduites' => $montant
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur de déduction des DKeys: ' . $e->getMessage());
            
            return [
                'succes' => false,
                'message' => 'Échec de la déduction des DKeys'
            ];
        }
    }

    /**
     * Mettre à jour le solde d'une clé
     */
    private function majSoldeCle(DKey $cle, int $montantADeduire): int
    {
        if ($cle->key_remaining <= $montantADeduire) {
            $montantADeduire -= $cle->key_remaining;
            $cle->key_remaining = 0;
            $cle->status = 'consumed';
        } else {
            $cle->key_remaining -= $montantADeduire;
            $montantADeduire = 0;
        }

        $cle->save();
        return $montantADeduire;
    }
}