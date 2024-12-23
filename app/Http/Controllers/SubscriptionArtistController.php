<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionArtistController extends Controller
{
    // Afficher la liste des souscriptions d'un utilisateur
    public function index()
    {
        $subscriptions = auth()->user()->subscriptions;
        return response()->json([
            'subscriptions' => $subscriptions
        ]);
    }

    // Créer une nouvelle souscription
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_type' => 'required|in:free,pro',
            'duration' => 'required|integer|min:1', // durée en jours
        ]);

        $subscription = Subscription::create([
            'user_id' => $request->user_id,
            'plan_type' => $request->plan_type,
            'start_date' => now(),
            'end_date' => now()->addDays($request->duration),
            'status' => 'active',
            'auto_renewal' => $request->input('auto_renewal', false),
        ]);

        return response()->json([
            'message' => 'Subscription created successfully',
            'subscription' => $subscription
        ], 201);
    }

    // Mettre à jour une souscription
    public function update(Request $request, Subscription $subscription)
    {
        $request->validate([
            'plan_type' => 'sometimes|in:free,pro',
            'status' => 'sometimes|in:active,expired,cancelled',
            'auto_renewal' => 'sometimes|boolean',
            'end_date' => 'sometimes|date'
        ]);

        $subscription->update($request->all());

        return response()->json([
            'message' => 'Subscription updated successfully',
            'subscription' => $subscription
        ]);
    }

    // Annuler une souscription
    public function cancel(Subscription $subscription)
    {
        $subscription->cancel();

        return response()->json([
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    // Renouveler une souscription
    public function renew(Subscription $subscription, Request $request)
    {
        $request->validate([
            'duration' => 'required|integer|min:1'
        ]);

        $subscription->renew($request->duration);

        return response()->json([
            'message' => 'Subscription renewed successfully',
            'subscription' => $subscription
        ]);
    }

    // Vérifier le statut d'une souscription
    public function checkStatus(User $user)
    {
        $currentSubscription = $user->currentSubscription();

        return response()->json([
            'has_active_subscription' => $currentSubscription ? true : false,
            'subscription_type' => $currentSubscription ? $currentSubscription->plan_type : null,
            'expires_at' => $currentSubscription ? $currentSubscription->end_date : null,
            'is_pro' => $currentSubscription ? $currentSubscription->isPro() : false
        ]);
    }
}
