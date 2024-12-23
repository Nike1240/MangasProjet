<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Artist;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    /**
     * Récupérer la liste de tous les artistes
     */
    public function listArtists()
    {
        try {
            $artists = Artist::all();

            return response()->json([
                'status' => 'success',
                'total_artists' => $artists->count(),
                'artists' => $artists
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des artistes : ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de récupérer la liste des artistes'
            ], 500);
        }
    }

    /**
     * Filtrer les artistes
     */
    public function filterArtists(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Requête qui joint les tables artists et users
        $query = Artist::query();

        // Filtrage par statut is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Récupérer les artistes avec leurs utilisateurs associés
        $artists = $query->with('user')->get();

        return response()->json([
            'status' => 'success',
            'total_artists' => $artists->count(),
            'artists' => $artists
        ]);
    }

    /**
     * Modifier le statut d'un artiste (activer/désactiver)
     */
    public function toggleArtistStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'artist_id' => 'required|exists:artists,id',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $artist = Artist::findOrFail($request->artist_id);
            
            // Mettre à jour uniquement le statut de l'artiste
            $artist->update([
                'is_active' => $request->is_active,
                'deactivated_at' => $request->is_active ? null : now()
            ]);

            // Supprimer les tokens si désactivé
            if (!$request->is_active) {
                $artist->tokens()->delete();
            }

            // Log de l'action
            Log::info('Statut de l\'artiste modifié', [
                'artist_id' => $artist->id,
                'new_status' => $artist->is_active ? 'Activé' : 'Désactivé'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => $artist->is_active ? 'Artiste activé' : 'Artiste désactivé',
                'artist' => $artist
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification du statut de l\'artiste : ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de modifier le statut de l\'artiste'
            ], 500);
        }
    }

    /**
     * Supprimer un artiste
     */
    public function deleteArtist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'artist_id' => 'required|exists:artists,id'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            // Commencer une transaction de base de données
            DB::beginTransaction();
    
            // Trouver l'artiste
            $artist = Artist::findOrFail($request->artist_id);
            
            // Trouver l'utilisateur associé
            $user = User::find($artist->user_id);
    
            // Supprimer l'artiste
            $artist->delete();
    
            // Supprimer l'utilisateur si trouvé
            if ($user) {
                $user->delete();
            }
    
            // Valider la transaction
            DB::commit();
    
            Log::info('Artiste et utilisateur supprimés par admin', [
                'artist_id' => $artist->id,
                'user_id' => $user ? $user->id : 'Non trouvé'
            ]);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Artiste et utilisateur supprimés avec succès'
            ]);
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();
    
            Log::error('Erreur lors de la suppression de l\'artiste : ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer l\'artiste et l\'utilisateur'
            ], 500);
        }
    }

    
   
}


