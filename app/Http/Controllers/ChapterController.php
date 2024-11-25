<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\Chapter;
use App\Models\Content;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;

class ChapterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Content $content)
    {
        return response()->json($content->chapters()->with('pages')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Content $content)
    {

        if ($content->type !== 'manga') {
            return response()->json([
                'error' => 'Impossible de créer un chapitre pour un contenu de type ' . $content->type . '. Seuls les mangas peuvent avoir des saisons.'
            ], 422);
        }

        // Vérifier si le numéro de épisode existe déjà pour cet anime
        if ($content->seasons()->where('number', $request->number)->exists()) {
            return response()->json([
                'error' => 'L\'épisode ' . $request->number . ' existe déjà pour cet anime.'
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'number' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date'
        ]);

        $baseSlug = Str::slug($validated['title'] . '-' . $content->type);
        $slug = $baseSlug;
        $counter = 1;

        // Vérifier si le slug existe déjà, quel que soit le statut du contenu (supprimé ou non)
        while (Content::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $validated['slug'] = $slug;
        $chapter = $content->chapters()->create($validated);

        return response()->json($chapter, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($content, $chapterId)
    {
        
        // Rechercher le chapitre en fonction du contenu spécifié
        $chapter = Chapter::with(['pages' => function ($query) {
            $query->orderBy('page_number');
        }])
        ->where('content_id', $content) // Vérification de l'appartenance au contenu
        ->findOrFail($chapterId);

        $chapter->increment('views_count');
        return response()->json([
            'chapter' => $chapter,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */

     public function update(Request $request, Content $content, Chapter $chapter)
    {


        // Vérifier si le chapitre appartient bien au contenu
        if ($chapter->content_id !== $content->id) {
            return response()->json(['message' => 'This chapter does not belong to this content'], 403);
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

            // Update chapter
            $chapter->update($validated);

            return response()->json($chapter, 200);

        } catch (\Exception $e) {
            \Log::error('Error updating chapter: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Content $content, Chapter $chapter)
    {
        // Vérifier si l'artiste authentifié est l'auteur du contenu
        $artist = auth()->guard('sanctum')->user()->artist;

        if (!$artist || $artist->id !== $content->artist_id) {
            return response()->json(['error' => 'Vous n\'êtes pas autorisé à supprimer ce contenu.'], 403);
        }

        try {
            // Supprimer le chapitre lié au contenu
            if ($chapter->content_id === $content->id) {
                $chapter->delete(); // Supprimer le chapitre
            }

            return response()->json([
                'message' => 'Chapitre supprimé avec succès.'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la suppression du chapitre.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function toggleLike(Request $request, Chapter $chapter)
    {
        // Récupérer l'ID de l'utilisateur une seule fois
        $userId = auth()->guard('sanctum')->id();
        
        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            DB::beginTransaction();
            
            $like = $chapter->likes()->where('user_id', $userId)->first();
            
            if ($like) {
                // Si le like existe, on le supprime
                $like->delete();
                $chapter->decrement('likes_count');
                $action = 'unliked';
            } else {
                // Si le like n'existe pas, on le crée
                $chapter->likes()->create(['user_id' => $userId]);
                $chapter->increment('likes_count');
                $action = 'liked';
            }
            
            DB::commit();

            // Recharger le chapter pour avoir le nombre exact de likes
            $chapter->refresh();
            
            return response()->json([
                'status' => 'success',
                'action' => $action,
                'likes_count' => $chapter->likes_count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle like'
            ], 500);
        }
    }

}
