<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Genre;
use App\Models\Chapter;
use App\Models\Season;
use App\Models\Episode;
use App\Models\Page;
use App\Models\Favorite;
use App\Models\File;
use App\Models\Download;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\File;
use App\Services\FileEncryptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ContentInteractionController extends Controller
{
    private $encryptedPath;

    public function __construct(FileEncryptionService $encryptedPath)
    {
        $this->encryptedPath = $encryptedPath;
    }

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

                    
                    switch (class_basename($favorite->favoritable_type)) {
                        case 'Content':
                            $data += [
                                'title' => $item->title,
                                'description' => $item->description,
                                'cover' => $item->cover,
                                
                            ];
                            break;

                        case 'Chapter':
                            $data += [
                                'title' => $item->title,
                                'number' => $item->number,
                                'content_id' => $item->content_id,
                                
                            ];
                            break;

                        case 'Season':
                            $data += [
                                'title' => $item->title,
                                'number' => $item->number,
                                'content_id' => $item->content_id,
                                
                            ];
                            break;

                        case 'Episode':
                            $data += [
                                'title' => $item->title,
                                'number' => $item->number,
                                'season_id' => $item->season_id,
                                
                            ];
                            break;

                        case 'Page':
                            $data += [
                                'title' => $item->title,
                                'number' => $item->number,
                                'chapter_id' => $item->chapter_id,
                                
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

    public function downloadForOffline($type, $id)
    {
        try {
            $user = auth()->guard('sanctum')->user();
            
            if (!$user) {
                return response()->json(['error' => 'Non authentifié'], 401);
            }

            if (!$user->hasActiveSubscription()) {
                return response()->json(['error' => 'Abonnement non actif'], 403);
            }

            $subscription = $user->subscription;

            // Vérification de la limite de téléchargements
            if ($subscription->hasDownloadLimitReached($user)) {
                return response()->json(['error' => 'Limite de téléchargement atteinte'], 403);
            }

            // Logique polymorphique pour différents types
            $modelClass = $this->getOfflineModel($type);
            
            if (!$modelClass) {
                return response()->json(['error' => 'Type non valide'], 400);
            }

            $item = $modelClass::findOrFail($id);

            // Cryptage du contenu
            $encryptedPath = $this->encryptItemForOffline($item, $type);

            // Enregistrement du téléchargement hors ligne
            $download = Download::updateOrCreate(
                [
                    'user_id' => $user->id, 
                    'downloadable_id' => $item->id,
                    'downloadable_type' => $modelClass
                ],
                [
                    'is_offline' => true,
                    'downloaded_at' => now()
                ]
            );

            return response()->json([
                'message' => 'Élément disponible hors ligne',
                'item' => [
                    'id' => $item->id,
                    'type' => $type,
                    'name' => $this->getItemName($item, $type),
                    'path' => $encryptedPath,
                    'encrypted' => true
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur de téléchargement hors ligne : ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Erreur lors du téléchargement',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    
    protected function getOfflineModel($type)
    {
        $model = match ($type) {
            'episode' => Episode::class,
            'page' => Page::class,
            'season' => Season::class,
            'chapter' => Chapter::class,
            default => null,
        };

        if (!$model) {
            Log::warning("Type invalide fourni à getOfflineModel", ['type' => $type]);
        }

        return $model;
    }


    
    protected function encryptItemForOffline($item, $type)
    {
        try {
            Log::info("Début du cryptage pour un item", ['id' => $item->id, 'type' => $type]);

            switch ($type) {
                case 'episode':
                    return FileEncryptionService::encryptAndStore(Storage::path($item->video_path));

                case 'page':
                    return FileEncryptionService::encryptAndStore(Storage::path($item->image_path));

                case 'season':
                    $season = Season::with('episodes')->findOrFail($item->id);
                    
                    if (!$season || $season->episodes->isEmpty()) {
                        Log::error("Saison ou épisodes introuvables", ['season_id' => $item->id]);
                        throw new \Exception("Saison ou épisodes introuvables.");
                    }
                    
                    $encryptedPaths = [];
                    foreach ($season->episodes as $episode) {
                        if (!$episode->video_path) {
                            Log::warning("Chemin vidéo manquant pour l'épisode", ['episode_id' => $episode->id]);
                            continue;
                        }

                        $encryptedPaths[] = FileEncryptionService::encryptAndStore(Storage::path($episode->video_path));
                    }

                    if (empty($encryptedPaths)) {
                        throw new \Exception("Aucun fichier vidéo crypté pour cette saison.");
                    }

                    return $encryptedPaths;

                case 'chapter':
                    $chapter = Chapter::with('pages')->findOrFail($item->id);

                    if ($chapter->pages->isEmpty()) {
                        throw new \Exception("Aucune page trouvée pour ce chapitre.");
                    }

                    $encryptedPaths = [];
                    foreach ($chapter->pages as $page) {
                        if ($page->image_path) {
                            $encryptedPaths[] = FileEncryptionService::encryptAndStore(Storage::path($page->image_path));
                        }
                    }

                    if (empty($encryptedPaths)) {
                        throw new \Exception("Aucun fichier image crypté pour ce chapitre.");
                    }

                    return $encryptedPaths;

                default:
                    throw new \InvalidArgumentException("Type non supporté pour le cryptage hors ligne.");
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors du cryptage pour un item", [
                'id' => $item->id,
                'type' => $type,
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }




    
    protected function getItemName($item, $type)
    {
        $name = match ($type) {
            'episode' => $item->title ?? "Épisode {$item->number}",
            'page' => $item->title ?? "Page {$item->number}",
            'season' => $item->title ?? "Saison {$item->number}",
            'chapter' => $item->title ?? "Chapitre {$item->number}",
            default => "Élément hors ligne",
        };

        if ($name === "Élément hors ligne") {
            Log::warning("Type inconnu dans getItemName", ['id' => $item->id, 'type' => $type]);
        }

        return $name;
    }



    public function handle($request, Closure $next)
    {
        $user = $request->user();

        // Vérifier l'abonnement et les limites
        $downloadsThisMonth = $user->downloads()
            ->whereMonth('downloaded_at', now()->month)
            ->count();

        if ($downloadsThisMonth >= $user->subscription->download_limit) {
            return response()->json(['error' => 'Limite de téléchargement atteinte.'], 403);
        }

        return $next($request);
    }


    public function getOfflineItems()
    {
        $user = auth()->guard('sanctum')->user();
        
        $offlineItems = Download::where('user_id', $user->id)
            ->where('is_offline', true)
            ->with('downloadable')
            ->get()
            ->map(function ($download) {
                $item = $download->downloadable;
                return [
                    'id' => $item->id,
                    'type' => class_basename(get_class($item)),
                    'name' => $this->getItemName($item, class_basename(get_class($item))),
                    'downloaded_at' => $download->downloaded_at
                ];
            });

        return response()->json([
            'offline_items' => $offlineItems,
            'total' => $offlineItems->count()
        ]);
    }


    public function downloadCollectionForOffline($type, $id)
    {
        try {
            $user = auth()->guard('sanctum')->user();

            if (!$user) {
                Log::warning("Utilisateur non authentifié tentant de télécharger une collection.", ['type' => $type, 'id' => $id]);
                return response()->json(['error' => 'Non authentifié'], 401);
            }

            if (!$user->hasActiveSubscription()) {
                Log::warning("Utilisateur sans abonnement actif tentant de télécharger une collection.", ['user_id' => $user->id]);
                return response()->json(['error' => 'Abonnement non actif'], 403);
            }

            $subscription = $user->subscription;

            if ($subscription->hasDownloadLimitReached($user)) {
                Log::warning("Limite de téléchargement atteinte pour l'utilisateur.", ['user_id' => $user->id]);
                return response()->json(['error' => 'Limite de téléchargement atteinte'], 403);
            }

            $modelClass = $this->getOfflineModel($type);

            if (!$modelClass) {
                Log::error("Type de collection invalide fourni.", ['type' => $type]);
                return response()->json(['error' => 'Type non valide'], 400);
            }

            $collection = $modelClass::with($this->getCollectionItemsRelation($type))->findOrFail($id);

            Log::info("Collection récupérée avec succès", ['collection_id' => $collection->id, 'type' => $type]);

            $itemsRelation = $this->getCollectionItemsRelation($type);
            $items = $collection->{$itemsRelation};

            $downloadedItems = [];

            foreach ($items as $item) {
                if ($subscription->hasDownloadLimitReached($user)) {
                    Log::warning("Limite de téléchargement atteinte en cours de traitement.", ['user_id' => $user->id]);
                    break;
                }

                // Ajuster le type pour l'élément individuel
                $itemType = class_basename($item); // Exemple : Episode, Page

                $encryptedPath = $this->encryptItemForOffline($item, strtolower($itemType));

                Download::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'downloadable_id' => $item->id,
                        'downloadable_type' => get_class($item)
                    ],
                    [
                        'is_offline' => true,
                        'downloaded_at' => now()
                    ]
                );

                $downloadedItems[] = [
                    'id' => $item->id,
                    'name' => $this->getItemName($item, strtolower($itemType)),
                    'path' => $encryptedPath,
                    'encrypted' => true
                ];
            }

            return response()->json([
                'message' => 'Collection disponible hors ligne',
                'collection' => [
                    'id' => $collection->id,
                    'type' => $type,
                    'name' => $this->getItemName($collection, $type),
                    'items_downloaded' => count($downloadedItems),
                    'items' => $downloadedItems
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors du téléchargement hors ligne d'une collection", [
                'type' => $type,
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erreur lors du téléchargement',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    
    protected function getCollectionItemsRelation($type)
    {
        $relations = [
            'season' => 'episodes',
            'chapter' => 'pages',
        ];
    
        return $relations[$type] ?? null;
    }
    


}
