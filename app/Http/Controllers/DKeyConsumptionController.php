<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Page;
use App\Models\Episode;
use App\Models\UserContentProgression;
use App\Services\DKeyConsumptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DKeyConsumptionController extends Controller
{
    private $dkeyService;

    public function __construct(DKeyConsumptionService $dkeyService)
    {
        $this->dkeyService = $dkeyService;
    }

    public function consumeContent($itemId, $type)
    {
        try {
            $user = auth()->guard('sanctum')->user();
            
            // Récupérer le contenu en fonction du type (page ou épisode)
            if ($type === 'page') {
                $page = Page::findOrFail($itemId);
                $chapter = $page->chapter;
                $content = $chapter->content;
            } elseif ($type === 'episode') {
                $episode = Episode::findOrFail($itemId);
                $season = $episode->season;
                $content = $season->content;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de contenu non supporté'
                ], 400);
            }

            // Vérifier d'abord si l'utilisateur peut accéder au contenu
            if (!$this->dkeyService->canAccessContent($user, $content)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé - Solde DKeys insuffisant ou aucun abonnement actif'
                ], 403);
            }

            // Si l'accès est autorisé, on procède à la mise à jour de la progression
            $currentProgression = UserContentProgression::where('user_id', $user->id)
                ->where('content_id', $content->id)
                ->first();
                
            $progression = $currentProgression ? $currentProgression->accessed_count + 1 : 1;

            // Mettre à jour ou créer la progression
            UserContentProgression::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'content_id' => $content->id
                ],
                [
                    'accessed_count' => $progression
                ]
            );

            // Consommer les DKeys si nécessaire
            $result = $this->dkeyService->consumeContent($user, $content, $progression);

            Log::info('Consommation de contenu', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'type' => $type,
                'item_id' => $itemId,
                'progression' => $progression,
                'result' => $result
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la consommation du contenu', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la consommation du contenu'
            ], 500);
        }
    }

    public function checkAccess($contentId)
    {
        $user = auth()->guard('sanctum')->user();
    
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $content = Content::findOrFail($contentId);
    
        $canAccess = $this->dkeyService->canAccessContent($user, $content);
    
        // Vérifie les dkeys restantes
        $remainingKeys = $user->dkeys
            ? $user->dkeys->where('status', 'active')->sum('key_remaining')
            : 0;
    
        return response()->json([
            'can_access' => $canAccess,
            'remaining_keys' => $remainingKeys
        ]);
    }
    

    public function trackContentAccess($contentId, $specificId)
    {
        $user = auth()->guard('sanctum')->user();
        $content = Content::findOrFail($contentId);

        // Vérifier d'abord si l'utilisateur peut accéder au contenu
        if (!$this->dkeyService->canAccessContent($user, $content)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé - Solde DKeys insuffisant ou aucun abonnement actif'
            ], 403);
        }

        // Déterminer le type de progression (page ou épisode)
        $progressType = $content->type === 'manga' ? 'page' : 'episode';

        // Créer ou mettre à jour la progression de l'utilisateur
        $progression = UserContentProgression::firstOrCreate([
            'user_id' => $user->id,
            'content_id' => $contentId
        ]);

        // Incrémenter le compteur de progression
        $progression->increment('accessed_count');

        // Consommer les DKeys si nécessaire
        $result = $this->dkeyService->consumeContent(
            $user, 
            $content, 
            $progression->accessed_count
        );

        // Log de la progression
        Log::info('Content Accessed', [
            'user_id' => $user->id,
            'content_id' => $contentId,
            'specific_id' => $specificId,
            'progression_count' => $progression->accessed_count,
            'dkey_deducted' => $result['dkey_deduced'] ?? false
        ]);

        return $result;
    }
}