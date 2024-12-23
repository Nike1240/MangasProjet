<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use FFMpeg;

class EpisodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Season $season)
    {
        return response()->json($season->episodes()->orderBy('episode_number', 'asc')->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Season $season)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'episode_number' => 'required|integer|min:1',
            'description' => 'nullable|string',
            // 'video_path' => 'required|file|mimetypes:video/mp4,video/webm|max:512000',
            'thumbnail_path' => 'required|image|max:2048',
            'duration' => 'required|integer',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date',
            'content_id' => 'required|exists:contents,id'
        ]);
        // return response()->json($validated, 201);
        // Gérer la vidéo
        $videoFile = $request->file('video_path');
        $videoName = Str::slug($validated['title']) . '_' . time() . '.' . $videoFile->getClientOriginalExtension();
        $videoPath = $videoFile->storeAs('public/anime_episodes', $videoName);
        // Récupérer le chemin complet pour la base de données
        $videoPath = 'anime_episodes/' . $videoName; // Modifié ici

        // Gérer la miniature
        $thumbnailFile = $request->file('thumbnail_path');
        $thumbnailName = 'thumb_' . $videoName . '.' . $thumbnailFile->getClientOriginalExtension();
        $thumbnailPath = $thumbnailFile->storeAs('public/anime_episodes/thumbnails', $thumbnailName);
        // Récupérer le chemin complet pour la base de données
        $thumbnailPath = 'anime_episodes/thumbnails/' . $thumbnailName; // Modifié ici

        // Créer l'épisode
        $episode = $season->episodes()->create([
            'title' => $validated['title'],
            'episode_number' => $validated['episode_number'],
            'description' => $validated['description'],
            'video_path' => $videoPath, // Modifié : utiliser le chemin complet
            'thumbnail_path' => $thumbnailPath, // Modifié : utiliser le chemin complet
            'duration' => $validated['duration'],
            'status' => $validated['status'],
            'published_at' => array_key_exists('published_at' , $validated) ? $validated['published_at'] : null,
            'slug' => Str::slug($validated['title']),
            'season_number' => $season->number,
            'file_size' => $videoFile->getSize(),
            'content_id' => $validated['content_id']
        ]);

        // Mettre à jour le compteur d'épisodes
        $season->episodes_count = $season->episodes()->count();
        $season->save();

        return response()->json($episode, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Episode $episode)
    {
        return response()->json($episode);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Episode $episode)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'episode_number' => 'integer|min:1',
            'description' => 'nullable|string',
            'video' => 'file|mimetypes:video/mp4,video/webm|max:512000',
            'thumbnail' => 'image|max:2048',
            'duration' => 'integer',
            'status' => [Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date'
        ]);

        if ($request->hasFile('video')) {
            // Supprimer l'ancienne vidéo
            Storage::delete('public/anime_episodes/' . $episode->video_path);
            
            // Stocker la nouvelle vidéo
            $videoFile = $request->file('video');
            $videoName = Str::slug($episode->title) . '_' . time() . '.' . $videoFile->getClientOriginalExtension();
            $videoPath = $videoFile->storeAs('public/anime_episodes', $videoName);
            
            $validated['video_path'] = $videoName;
            $validated['file_size'] = $videoFile->getSize();
        }

        if ($request->hasFile('thumbnail')) {
            // Supprimer l'ancienne miniature
            Storage::delete('public/anime_episodes/thumbnails/' . $episode->thumbnail_path);
            
            // Stocker la nouvelle miniature
            $thumbnailFile = $request->file('thumbnail');
            $thumbnailName = 'thumb_' . ($videoName ?? $episode->video_path) . '.' . $thumbnailFile->getClientOriginalExtension();
            $thumbnailPath = $thumbnailFile->storeAs('public/anime_episodes/thumbnails', $thumbnailName);
            
            $validated['thumbnail_path'] = $thumbnailName;
        }

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $episode->update($validated);

        return response()->json($episode);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Episode $episode)
    {
        // Supprimer les fichiers associés
        Storage::delete([
            'public/anime_episodes/' . $episode->video_path,
            'public/anime_episodes/thumbnails/' . $episode->thumbnail_path
        ]);

        $season = $episode->season;
        $episode->delete();

        // Mettre à jour le compteur d'épisodes
        $season->episodes_count = $season->episodes()->count();
        $season->save();

        return response()->json(null, 204);
    }

    /**
     * Obtenir l'URL de streaming de la vidéo
     */
    public function stream(Episode $episode)
    {
        $path = storage_path('app/public/anime_episodes/' . $episode->video_path);
        return response()->file($path);
    }
}