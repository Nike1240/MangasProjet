<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Advertisement;
use App\Models\AdView;
use App\Models\DKey;
use Illuminate\Support\Facades\Auth;

class AdViewController extends Controller
{

    public function addAdvertisement(Request $request)
    {

        $admin = Auth::guard('admin')->user();
        
        if (!$admin) {
            return response()->json(['message' => 'Vous n\'êtes pas administrateur'], 404);
        }
        // Validation des données d'entrée
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|url',
            'duration' => 'required|integer|min:1',
            'reward_amount' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,scheduled',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'advertiser_name' => 'required|string|max:255',
            'daily_budget' => 'required|numeric|min:0',
        ]);

        // Création de la publicité
        $advertisement = Advertisement::create([
            'title' => $validatedData['title'],
            'video_url' => $validatedData['video_url'],
            'duration' => $validatedData['duration'],
            'reward_amount' => $validatedData['reward_amount'],
            'status' => $validatedData['status'],
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'advertiser_name' => $validatedData['advertiser_name'],
            'daily_budget' => $validatedData['daily_budget'],
        ]);

        // Retourner une réponse de succès
        return response()->json([
            'message' => 'Advertisement successfully added',
            'advertisement' => $advertisement
        ], 201);
    }


    public function recordAdView(Request $request)
    {
        $userId = auth()->guard('sanctum')->id();

        // Sélection d'une publicité active de manière aléatoire
        $advertisement = Advertisement::where('status', 'active')->inRandomOrder()->first();

        // Si aucune publicité active n'est disponible
        if (!$advertisement) {
            return response()->json(['error' => 'No active advertisements available'], 404);
        }

        // Le frontend envoie uniquement la durée regardée
        $request->validate([
            'watched_duration' => 'required|integer',
        ]);

        // Calcul de la récompense basée sur la durée regardée
        $rewardEarned = $this->calculateReward(
            $request->watched_duration,
            $advertisement->duration
        );

        // Enregistrement de la vue de la publicité
        $adView = AdView::create([
            'user_id' => $userId,
            'advertisement_id' => $advertisement->id,
            'watched_duration' => $request->watched_duration,
            'reward_earned' => $rewardEarned
        ]);

        // Mettre à jour les statistiques de la publicité
        $advertisement->increment('total_views');

        // Définition du type de source pour la DKey
        $sourceType = 'ad_reward';

        // Ajouter les D-Keys gagnés
        if ($rewardEarned > 0) {
            $expirationDate = $this->addDKey($userId, $sourceType, $rewardEarned);
        }

        // Retourner la réponse avec la publicité choisie, la récompense gagnée, et un message
        return response()->json([
            'message' => 'Ad view recorded and reward added',
            'advertisement' => $advertisement, // Permet au frontend d'afficher la vidéo
            'reward_earned' => $rewardEarned
        ]);
    }


    // Fonction de calcul de la récompense en DKeys
    private function calculateReward($watchedDuration, $adDuration)
    {
        // Si l'utilisateur regarde moins de la moitié de la vidéo
        if ($watchedDuration <= ($adDuration / 2)) {
            return 1; // 1 DKey pour moins de la moitié
        }

        // Si l'utilisateur regarde plus de la moitié mais pas la vidéo entière
        if ($watchedDuration < $adDuration) {
            return 2; // 2 DKeys pour plus de la moitié mais pas la totalité
        }

        // Si l'utilisateur regarde la vidéo entière
        return 3; // 3 DKeys pour une vue complète
    }

    // Fonction pour ajouter ou mettre à jour une DKey
    private function addDKey($userId, $sourceType, $rewardEarned)
    {
        // Obtenir la date d'expiration selon la source
        $expirationDate = $this->getExpirationDate($sourceType);

        // Rechercher une DKey existante pour l'utilisateur
        $existingDKey = DKey::where('user_id', $userId)
            ->where('source_type', $sourceType)
            ->where('expires_at', '>', now()) // Vérifie si la clé n'est pas expirée
            ->first();

        if ($existingDKey) {
            // Mettre à jour la DKey existante
            $existingDKey->update([
                'key_remaining' => $existingDKey->key_remaining + $rewardEarned, // Ajouter les DKeys gagnés
                'expires_at' => max($existingDKey->expires_at, $expirationDate) // Prendre la date d'expiration la plus éloignée
            ]);
        } else {
            // Créer une nouvelle DKey si aucune n'existe
            DKey::create([
                'user_id' => $userId,
                'key_remaining' => $rewardEarned,
                'source_type' => $sourceType,
                'expires_at' => $expirationDate,
                'status' => 'active', // Statut actif par défaut
            ]);
        }
    }

    // Fonction pour obtenir la date d'expiration selon la source
    private function getExpirationDate($source_type)
    {
        $now = now();

        switch ($source_type) {
            case 'purchase':
                return $now->addDay(2); 
            case 'subscription':
                return $now->addWeek(); 
            case 'ad_reward':
                return $now->addHours(24); 
            default:
                return $now->addDay(); 
        }
    }


}
