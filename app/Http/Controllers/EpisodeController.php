<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use FFMpeg;
use FFMpeg\FFProbe;
use Illuminate\Support\Facades\DB;

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
            'videos' => 'required|array',
            'videos.*' => 'required|file|mimetypes:video/mp4,video/webm',
            'thumbnails' => 'required|array',
            'thumbnails.*' => 'required|image|max:2048',
            'episode_numbers' => 'required|array',
            'episode_numbers.*' => 'required|integer|min:1',
            'titles' => 'required|array',
            'descriptions' => 'array',
            'statuses' => 'required|array',
        ]);

        $uploadedEpisodes = [];
        $ffprobe = FFProbe::create();

        foreach ($request->file('videos') as $index => $videoFile) {
            // Extraire la durée automatiquement
            $tempPath = $videoFile->getRealPath();
            $duration = (int)$ffprobe
                ->streams($tempPath)
                ->videos()
                ->first()
                ->get('duration');

            $title = $request->titles[$index];
            $episodeNumber = $request->episode_numbers[$index];
            $seasonId=$season->id;
            $slug = Str::slug($title . '-episode-' . $episodeNumber . '-' . $seasonId);
            
            // Télécharger la vidéo
            $videoName = Str::slug($title) . '_' . time() . '.' . $videoFile->getClientOriginalExtension();
            $videoPath = $videoFile->storeAs('public/anime_episodes', $videoName);
            $videoPath = 'anime_episodes/' . $videoName;

            // Télécharger la miniature
            $thumbnailFile = $request->file('thumbnails')[$index];
            $thumbnailName = 'thumb_' . $videoName . '.' . $thumbnailFile->getClientOriginalExtension();
            $thumbnailPath = $thumbnailFile->storeAs('public/anime_episodes/thumbnails', $thumbnailName);
            $thumbnailPath = 'anime_episodes/thumbnails/' . $thumbnailName;

            // Créer l'épisode
            $episode = $season->episodes()->create([
                'title' => $title,
                'episode_number' => $episodeNumber,
                'description' => $request->descriptions[$index] ?? null,
                'video_path' => $videoPath,
                'thumbnail_path' => $thumbnailPath,
                'duration' => $duration, 
                'status' => $request->statuses[$index],
                'slug' => $slug,
                'season_number' => $season->number,
                'file_size' => $videoFile->getSize(),
            ]);
        
            $uploadedEpisodes[] = $episode;
        }

        $season->update(['episodes_count' => $season->episodes()->count()]);

        return response()->json($uploadedEpisodes, 201);
    }


    /**
     * Display the specified resource. /etc/php/8.1/cli/php.ini
     */
    public function show($seasonId, $episodeId)
    {
        // Récupérer l'épisode correspondant au seasonId et à l'episodeId
        $episode = Episode::where('season_id', $seasonId)->findOrFail($episodeId);
    
        // Incrémenter le compteur de vues
        $episode->increment('views_count');
    
        // Retourner l'épisode en JSON
        return response()->json($episode);
    }
    

   
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Season $season, Episode $episode)
    {
        $validated = $request->validate([
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm',
            'thumbnail' => 'nullable|image|max:2048',
            'episode_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $ffprobe = FFProbe::create();

        // Mise à jour des informations de l'épisode
        $title = $request->title;
        $episodeNumber = $request->episode_number;
        $slug = Str::slug($title . '-episode-' . $episodeNumber);

        // Met à jour le chemin vidéo si une nouvelle vidéo est fournie
        if ($request->hasFile('video')) {
            $videoFile = $request->file('video');
            $tempPath = $videoFile->getRealPath();
            $duration = (int)$ffprobe
                ->streams($tempPath)
                ->videos()
                ->first()
                ->get('duration');

            $videoName = Str::slug($title) . '_' . time() . '.' . $videoFile->getClientOriginalExtension();
            $videoPath = $videoFile->storeAs('public/anime_episodes', $videoName);
            $videoPath = 'anime_episodes/' . $videoName;

            // Supprime l'ancienne vidéo
            if ($episode->video_path) {
                Storage::delete('public/' . $episode->video_path);
            }

            $episode->video_path = $videoPath;
            $episode->duration = $duration; 
            $episode->file_size = $videoFile->getSize(); 
        }

        // Met à jour la miniature si une nouvelle image est fournie
        if ($request->hasFile('thumbnail')) {
            $thumbnailFile = $request->file('thumbnail');
            $thumbnailName = 'thumb_' . Str::slug($title) . '_' . time() . '.' . $thumbnailFile->getClientOriginalExtension();
            $thumbnailPath = $thumbnailFile->storeAs('public/anime_episodes/thumbnails', $thumbnailName);
            $thumbnailPath = 'anime_episodes/thumbnails/' . $thumbnailName;

            // Supprime l'ancienne miniature
            if ($episode->thumbnail_path) {
                Storage::delete('public/' . $episode->thumbnail_path);
            }

            $episode->thumbnail_path = $thumbnailPath;
        }

        // Met à jour les autres champs
        $episode->title = $title;
        $episode->episode_number = $episodeNumber;
        $episode->description = $request->description ?? $episode->description;
        $episode->status = $request->status;
        $episode->slug = $slug;
        $episode->save();

        return response()->json([
            'success' => true,
            'message' => 'Épisode mis à jour avec succès.',
            'episode' => $episode,
        ], 200);
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


    public function toggleLike(Request $request, Episode $episode)
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
            
            $like = $episode->likes()->where('user_id', $userId)->first();
            
            if ($like) {
                // Si le like existe, on le supprime
                $like->delete();
                $episode->decrement('likes_count');
                $action = 'unliked';
            } else {
                // Si le like n'existe pas, on le crée
                $episode->likes()->create(['user_id' => $userId]);
                $episode->increment('likes_count');
                $action = 'liked';
            }
            
            DB::commit();

            // Recharger le chapter pour avoir le nombre exact de likes
            $episode->refresh();
            
            return response()->json([
                'status' => 'success',
                'action' => $action,
                'likes_count' => $episode->likes_count
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