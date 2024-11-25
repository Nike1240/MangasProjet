<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Genre;
use App\Models\Chapter;
use App\Models\Season;
use App\Models\Episode;
use App\Models\Page;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ContentInteractionController extends Controller
{


    public function showContentDetails(Content $content)
    {
        $content->increment('views_count');
        
        $data = [
            'content' => $content->load(['artists', 'genres', 'tags']),
            'stats' => [
                'views' => $content->views_count,
                'likes' => $content->likes()->count(),
                'comments' => $content->comments()->count()
            ]
        ];

        // Ajouter les chapitres ou seasons selon le type
        if ($content->type === 'manga') {
            $data['chapters'] = $content->chapters()
                ->with('pages')
                ->orderBy('number')
                ->get();
        } else {
            $data['seasons'] = $content->seasons()
                ->with('episodes')
                ->orderBy('number')
                ->get();
        }

        return response()->json($data);
    }

    public function toggleLike(Request $request, Content $content)
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
            
            $like = $content->likes()->where('user_id', $userId)->first();
            
            if ($like) {
                // Si le like existe, on le supprime
                $like->delete();
                $content->decrement('likes_count');
                $action = 'unliked';
            } else {
                // Si le like n'existe pas, on le crée
                $content->likes()->create(['user_id' => $userId]);
                $content->increment('likes_count');
                $action = 'liked';
            }
            
            DB::commit();

            // Recharger le content pour avoir le nombre exact de likes
            $content->refresh();
            
            return response()->json([
                'status' => 'success',
                'action' => $action,
                'likes_count' => $content->likes_count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle like'
            ], 500);
        }
    }

    public function addComment(Request $request, Content $content)
    {
        $userId = auth()->guard('sanctum')->id();

        $validated = $request->validate([
            'body' => 'required|string|max:1000'
        ]);

        $comment = $content->comments()->create([
            'user_id' => $userId,
            'body' => $validated['body']
        ]);

        return response()->json([
            'comment' => $comment->load('user'),
            'comments_count' => $content->comments()->count()
        ]);
    }

    public function getComments(Content $content)
    {
        $comments = $content->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($comments);
    }


    public function toggleFavorite(Request $request, $type, $id)
    {
        $userId = auth()->guard('sanctum')->id();
        
        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // Récupérer le model correspondant au type
        $modelClass = $this->getModel($type);
        
        if (!$modelClass) {
            return response()->json([
                'message' => 'Type non valide'
            ], 400);
        }

        // Trouver l'élément favoritable
        $favoritable = $modelClass::find($id);
        
        if (!$favoritable) {
            return response()->json([
                'message' => 'Élément non trouvé'
            ], 404);
        }

        try {
            DB::beginTransaction();
            
            // Recherche du favori existant en utilisant le type spécifique
            $favorite = $favoritable->favorites()
                ->where('user_id', $userId)
                ->where('favoritable_type', $modelClass)
                ->first();
            
            if ($favorite) {
                $favorite->delete();
                $action = 'unfavorited';
                $message = "Favori supprimé";
            } else {
                $favoritable->favorites()->create([
                    'user_id' => $userId,
                    'favoritable_type' => $modelClass
                ]);
                $action = 'favorited';
                $message = "Favori ajouté";
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'action' => $action,
                'message' => $message,
                'data' => [
                    'type' => $type,
                    'id' => $id,
                    'model' => $modelClass
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors du toggle favorite:', [
                'error' => $e->getMessage(),
                'type' => $type,
                'id' => $id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle favorite'
            ], 500);
        }
    }
    
    protected function getModel($type)
    {
        return match ($type) {
            'content' => Content::class,
            'chapter' => Chapter::class,
            'season' => Season::class,
            'episode' => Episode::class,
            'page' => Page::class,
            default => null,
        };
    }

    public function getUserFavorites()
    {
        $userId = auth()->guard('sanctum')->id();
        
        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $favorites = Favorite::with('favoritable')  // Charge la relation polymorphique
                ->where('user_id', $userId)
                ->get()
                ->map(function ($favorite) {
                    $item = $favorite->favoritable;
                    
                    if (!$item) {
                        return null;
                    }

                    // Données de base communes à tous les types
                    $data = [
                        'id' => $item->id,
                        'type' => strtolower(class_basename($favorite->favoritable_type)),
                        'created_at' => $favorite->created_at,
                    ];

                    // Ajout des champs spécifiques selon le type
                    switch (class_basename($favorite->favoritable_type)) {
                        case 'Content':
                            $data += [
                                'title' => $item->title,
                                'description' => $item->description,
                                'cover' => $item->cover,
                                // Ajoutez d'autres champs spécifiques aux contenus
                            ];
                            break;

                        case 'Chapter':
                            $data += [
                                'title' => $item->title,
                                'number' => $item->number,
                                'content_id' => $item->content_id,
                                // Ajoutez d'autres champs spécifiques aux chapitres
                            ];
                            break;

                        case 'Season':
                            $data += [
                                'title' => $item->title,
                                'number' => $item->number,
                                'content_id' => $item->content_id,
                                // Ajoutez d'autres champs spécifiques aux saisons
                            ];
                            break;

                        case 'Episode':
                            $data += [
                                'title' => $item->title,
                                'number' => $item->number,
                                'season_id' => $item->season_id,
                                // Ajoutez d'autres champs spécifiques aux épisodes
                            ];
                            break;

                        case 'Page':
                            $data += [
                                'title' => $item->title,
                                'chapter_id' => $item->chapter_id,
                                // Ajoutez d'autres champs spécifiques aux pages
                            ];
                            break;
                    }

                    return $data;
                })
                ->filter() // Retire les éléments null (favoris dont l'élément lié n'existe plus)
                ->values(); // Réindexe le tableau

            return response()->json([
                'status' => 'success',
                'data' => [
                    'favorites' => $favorites,
                    'total' => $favorites->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des favoris:', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve favorites'
            ], 500);
        }
    }


}
