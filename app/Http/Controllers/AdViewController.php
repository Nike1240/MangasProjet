<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Advertisement;
use App\Models\AdView;
use App\Models\DKey;

class AdViewController extends Controller
{


    public function recordAdView(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'advertisement_id' => 'required|exists:advertisements,id',
            'watched_duration' => 'required|integer',
        ]);

        // Vérifier si la publicité est active
        $advertisement = Advertisement::findOrFail($request->advertisement_id);
        if ($advertisement->status !== 'active') {
            return response()->json(['error' => 'This advertisement is not active'], 400);
        }

        // Calculer la récompense basée sur la configuration de la pub
        $rewardEarned = $this->calculateReward(
            $request->watched_duration,
            $advertisement->duration,
            $advertisement->reward_amount
        );

        $adView = AdView::create([
            'user_id' => $request->user_id,
            'advertisement_id' => $request->advertisement_id,
            'watched_duration' => $request->watched_duration,
            'reward_earned' => $rewardEarned
        ]);

        // Mettre à jour les statistiques de la pub
        $advertisement->increment('total_views');

        // Ajouter les D-Keys gagnés
        if ($rewardEarned > 0) {
            DKey::create([
                'user_id' => $request->user_id,
                'amount' => $rewardEarned,
                'transaction_type' => 'ad_reward'
            ]);
        }

        return response()->json([
            'message' => 'Ad view recorded and reward added',
            'reward_earned' => $rewardEarned
        ]);
    }
    private function calculateReward($duration)
    {
        // Logique de calcul de récompense basée sur la durée
        return floor($duration / 30); // Example: 1 D-Key per 30 seconds
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
