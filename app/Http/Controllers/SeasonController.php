<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;
use App\Models\Content;
use App\Models\Season;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class SeasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Content $content)
    {
        return response()->json($content->seasons()->with('episodes')->paginate(15));
    }

    public function store(Request $request, Content $content)
    {
        // Vérifier si le contenu est un anime
        if ($content->type !== 'anime') {
            return response()->json([
                'error' => 'Impossible de créer une saison pour un contenu de type ' . $content->type . '. Seuls les animes peuvent avoir des saisons.'
            ], 422);
        }

        // Vérifier si le numéro de saison existe déjà pour cet anime
        if ($content->seasons()->where('number', $request->number)->exists()) {
            return response()->json([
                'error' => 'La saison ' . $request->number . ' existe déjà pour cet anime.'
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'number' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date'
        ]);

        $baseSlug = Str::slug($validated['title'] . '-' . $validated['type']);
        $slug = $baseSlug;
        $counter = 1;

        // Vérifier si le slug existe déjà, quel que soit le statut du contenu (supprimé ou non)
        while (Content::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $validated['slug'] = $slug;
        $season = $content->seasons()->create($validated);

        return response()->json([
            'message' => 'Saison créée avec succès',
            'season' => $season
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $content, $seasonId)
    {
        //
        $season = Season::with(['episodes' => function ($query) {
                $query->orderBy('episode_number');
            }])->where('content_id', $content)
               ->findOrFail($seasonId);
    
            return response()->json([
                'season' => $season,
                'episodes' => $season->episodes
            ]);
            
    }


    /**
     * Update the specified resource in storage.
     */

     public function update(Request $request, Content $content, Season $season)
     {
 
 
         // Vérifier si le chapitre appartient bien au contenu
         if ($season->content_id !== $content->id) {
             return response()->json(['message' => 'This season does not belong to this content'], 403);
         }
 
         try {
             $validated = $request->validate([
                 'title' => 'sometimes|string|max:255',
                 'number' => 'sometimes|integer|min:1',
                 'description' => 'nullable|string',
                 'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
                 'published_at' => 'nullable|date'
             ]);
 
             // Update slug only if title is changed
             if (isset($validated['title'])) {
                 $validated['slug'] = Str::slug($validated['title']);
             }
 
             // Update season
             $season->update($validated);
 
             return response()->json($season, 200);
 
         } catch (\Exception $e) {
             \Log::error('Error updating season: ' . $e->getMessage());
             throw $e;
         }
     }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Content $content, Season $season)
    {
        // Vérifier si l'artiste authentifié est l'auteur du contenu
        $artist = auth()->guard('sanctum')->user()->artist;

        if (!$artist || $artist->id !== $content->artist_id) {
            return response()->json(['error' => 'Vous n\'êtes pas autorisé à supprimer ce contenu.'], 403);
        }

        try {
            // Supprimer la saison lié au contenu
            if ($season->content_id === $content->id) {
                $season->delete(); // Supprimer la saison
            }

            return response()->json([
                'message' => 'Saison supprimé avec succès.'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la suppression de la saison.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
