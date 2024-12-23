<?php

namespace App\Http\Controllers;
use Illuminate\Validation\Rule;
use App\Models\Content;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\User;
use App\Models\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;

class ContentController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $genres = Genre::all();
        $tags = Tag::all();
        
        $query = Content::with(['artist', 'genres', 'tags'])
            ->when($request->type, function($q, $type) {
                return $q->where('type', $type);
            })
            ->when($request->status, function($q, $status) {
                return $q->where('status', $status);
            })
            ->when($request->publication_status, function($q, $status) {
                return $q->where('publication_status', $status);
            })

            ->when($request->language, function($q, $language) {
                return $q->where('language', $language);
            })

            ->when($request->genre_id || $request->route('genre'), function($q) use ($request) {
                $genreId = $request->genre_id ?? $request->route('genre');
                return $q->whereHas('genres', function($query) use ($genreId) {
                    $query->where('genres.id', $genreId);
                });
            })
            ->when($request->tag_id, function($q, $tagId) {
                return $q->whereHas('tags', function($query) use ($tagId) {
                    $query->where('tags.id', $tagId);
                });
            })
            ->when($request->search, function($q, $search) {
                return $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->sort_by, function($q, $sortBy) {
                $direction = $request->sort_direction ?? 'desc';
                return $q->orderBy($sortBy, $direction);
            }, function($q) {
                return $q->latest();
            });

        $perPage = $request->per_page ?? 15;
        $contents = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'contents' => $contents,
                'genres' => $genres,
                'tags' => $tags
            ]);
        }
        
        // return view('library', compact('contents', 'genres','tags'));
    }


    public function home()
    {
        $popularManga = Content::where('type', 'manga')
            ->where('is_featured', true)
            ->with(['artist', 'genres'])
            ->take(4)
            ->get();

        $popularAnime = Content::where('type', 'anime')
            ->where('is_featured', true)
            ->with(['artist', 'genres'])
            ->take(4)
            ->get();

        return view('home', compact('popularManga', 'popularAnime'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $artist = auth()->guard('sanctum')->user()->artist;

        if (!$artist) {
            return response()->json(['error' => 'Vous n\'êtes pas un artiste enregistré.'], 403);
        }

        \Log::info('Received content creation request', $request->all());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(['manga', 'anime'])],
            'cover_image' => 'required|image|max:2048',
            'description' => 'required|string',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'publication_status' => ['required', Rule::in(['ongoing', 'completed', 'hiatus'])],
            'language' => 'required|string|size:2',
            'genres' => 'required|array',
            'genres.*' => 'exists:genres,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id'
        ]);

        \Log::info('Validation passed', $validated);

        // Assigner l'ID de l'artiste au contenu
        $validated['artist_id'] = $artist->id;

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('covers', 'public');
            \Log::info('Cover image stored at: ' . $coverPath);
            $validated['cover_image'] = $coverPath;
        }
        
        // Générer le slug de base à partir du titre et du type
        $baseSlug = Str::slug($validated['title'] . '-' . $validated['type']);
        $slug = $baseSlug;
        $counter = 1;

        // Vérifier si le slug existe déjà, quel que soit le statut du contenu (supprimé ou non)
        while (Content::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $validated['slug'] = $slug;
        try {
            // Create content
            $content = Content::create($validated);
            
            // Sync relationships
            $content->genres()->sync($request->genres);
            if ($request->has('tags')) {
                $content->tags()->sync($request->tags);
            }

            // Load relationships for response
            $content->load(['artist', 'genres', 'tags']);

            return response()->json($content, 201);

        } catch (\Exception $e) {
            \Log::error('Error creating content: ' . $e->getMessage());
            throw $e;
        }
    }



    public function show(Content $content)
    {
        $content->increment('views_count');

        View::create([
            'content_id' => $content->id,
            'viewed_at' => now(),
        ]);
        // Charger les relations nécessaires en fonction du type de contenu
        $content->load([
            'artist',
            'genres',
            'tags',
            // Chargement conditionnel des chapitres/saisons
            $content->type === 'manga' 
                ? 'chapters.pages' 
                : 'seasons.episodes'
        ]);

        // Préparer les statistiques du contenu
        $stats = [
            'views_count' => $content->views_count,
            'likes_count' => $content->likes()->count(),
            'comments_count' => $content->comments()->count(),
        ];

        // Préparer les données spécifiques au type de contenu
        $contentDetails = [];
        if ($content->type === 'manga') {
            $contentDetails = [
                'total_chapters' => $content->chapters->count(),
                'chapters' => $content->chapters->map(function ($chapter) {
                    return [
                        'id' => $chapter->id,
                        'number' => $chapter->number,
                        'title' => $chapter->title,
                        'total_pages' => $chapter->pages->count(),
                        'first_page' => $chapter->pages->first() 
                            ? [
                                'number' => $chapter->pages->first()->page_number,
                                'image_path' => $chapter->pages->first()->image_path,
                            ] 
                            : null,
                        'pages' => $chapter->pages->map(function ($page) {
                            return [
                                'number' => $page->page_number,
                                'image_path' => $page->image_path,
                            ];
                        })
                    ];
                })
            ];
        } else {
            $contentDetails = [
                'total_seasons' => $content->seasons->count(),
                'seasons' => $content->seasons->map(function ($season) {
                    return [
                        'id' => $season->id,
                        'number' => $season->number,
                        'title' => $season->title,
                        'total_episodes' => $season->episodes->count(),
                        'episodes' => $season->episodes->map(function ($episode) {
                            return [
                                'id' => $episode->id,
                                'number' => $episode->episode_number,
                                'title' => $episode->title,
                                'duration' => $episode->duration,
                            ];
                        })
                    ];
                })
            ];
        }

        // Construire la réponse JSON complète
        $response = [
            'id' => $content->id,
            'title' => $content->title,
            'type' => $content->type,
            'cover_image' => $content->cover_image,
            'description' => $content->description,
            'status' => $content->status,
            'publication_status' => $content->publication_status,
            'language' => $content->language,
            'age_rating' => $content->age_rating,
            'artist' => [
                'id' => $content->artist->id,
                // 'name' => $content->artist->name
            ],
            'genres' => $content->genres->map(function ($genre) {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name
                ];
            }),
            'tags' => $content->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name
                ];
            }),
            'stats' => $stats,
            'content_details' => $contentDetails,
        ];

        return response()->json($response);
    }


    public function update(Request $request, Content $content)
    {
        $artist = auth()->guard('sanctum')->user()->artist;

        if (!$artist) {
            return response()->json(['error' => 'Vous n\'êtes pas un artiste enregistré.'], 403);
        }

        // Vérifier si l'artiste authentifié est l'auteur du contenu
        if ($content->artist_id !== $artist->id) {
            return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier ce contenu.'], 403);
        }
        // Validation des données de la requête
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['manga', 'anime'])],
            'cover_image' => 'sometimes|image|max:2048',
            'description' => 'sometimes|string',
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'publication_status' => ['sometimes', Rule::in(['ongoing', 'completed', 'hiatus'])],
            'age_rating' => 'nullable|integer|min:0',
            'language' => 'sometimes|string|size:2',
            'is_featured' => 'sometimes|boolean',
            'genres' => 'sometimes|array',
            'genres.*' => 'exists:genres,id',
            'tags' => 'sometimes|array',
            'tags.*' => 'exists:tags,id'
        ]);

        try {
            // Mettre à jour les données validées dans le contenu
            $content->update($validated);

            // Si des genres sont fournis, les synchroniser
            if ($request->has('genres')) {
                $content->genres()->sync($request->genres);
            }

            // Si des tags sont fournis, les synchroniser
            if ($request->has('tags')) {
                $content->tags()->sync($request->tags);
            }

            // Gestion de l'image de couverture si elle est fournie
            if ($request->hasFile('cover_image')) {
                // Supprimer l'ancienne image de couverture si elle existe
                if ($content->cover_image) {
                    Storage::disk('public')->delete($content->cover_image);
                }
                $coverPath = $request->file('cover_image')->store('covers', 'public');
                $content->cover_image = $coverPath;
            }

            // Mettre à jour le slug si le titre est modifié
            if (isset($validated['title'])) {
                $content->slug = Str::slug($validated['title']);
            }

            // Charger les relations pour la réponse
            $content->load(['artist', 'genres', 'tags']);

            return response()->json([
                'message' => 'Contenu mis à jour avec succès',
                'content' => $content
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du contenu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Content $content)
    {
        $artist = auth()->guard('sanctum')->user()->artist;

        if (!$artist) {
            return response()->json(['error' => 'Vous n\'êtes pas un artiste enregistré.'], 403);
        }

        if ($content->artist_id !== $artist->id) {
            return response()->json(['error' => 'Vous n\'êtes pas autorisé à supprimer ce contenu.'], 403);
        }
        try {
            Storage::disk('public')->delete($content->cover_image);
            $content->delete();
            return response()->json([
                'message' => 'Contenu supprimé avec succès.'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la suppression du contenu.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function search(Request $request)
    {
        $query = Content::query();

        if ($request->q) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {

                $q->where('title', 'like', "%{$searchTerm}%")

                ->orWhere('language', 'like', "%{$searchTerm}%")

                ->orWhere('type', 'like', "%{$searchTerm}%")

                ->orWhereHas('genres', function($subQuery) use ($searchTerm) {

                    $subQuery->where('name', 'like', "%{$searchTerm}%");
                })

                ->orWhereHas('tags', function($subQuery) use ($searchTerm) {

                    $subQuery->where('name', 'like', "%{$searchTerm}%");
                });
            });
        }

        
        return response()->json(
            $query->with(['artist', 'genres', 'tags'])
                ->paginate($request->per_page ?? 15)
        );
    }


    public function details($genre_id)
    {
        $contents = Content::with(['artist', 'genres', 'tags'])
        ->whereHas('genres', function($query) use ($genre_id) {
            $query->where('genres.id', $genre_id);
        })
        ->paginate(15);
        
        return response()->json($contents);
        // return back()->with(compact('contents'));
       
    }


    

    public function searchManga(Request $request)
    {
        $query = Content::query()
            ->where('type', 'manga');

        if ($request->q) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('language', 'like', "%{$searchTerm}%")
                    ->orWhereHas('genres', function($subQuery) use ($searchTerm) {
                        $subQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('tags', function($subQuery) use ($searchTerm) {
                        $subQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return response()->json(
            $query->with(['artist', 'genres', 'tags'])
                ->paginate($request->per_page ?? 15)
        );
    }

    public function searchAnime(Request $request)
    {
        $query = Content::query()
            ->where('type', 'anime');

        if ($request->q) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('language', 'like', "%{$searchTerm}%")
                    ->orWhereHas('genres', function($subQuery) use ($searchTerm) {
                        $subQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('tags', function($subQuery) use ($searchTerm) {
                        $subQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return response()->json(
            $query->with(['artist', 'genres', 'tags'])
                ->paginate($request->per_page ?? 15)
        );
    }


    public function getArtistDashboard($userId)
    {
        // Récupérer l'artiste associé à l'utilisateur
        $user = User::with('artist.contents')->findOrFail($userId);

        $artist = $user->artist;
        if (!$artist) {
            return response()->json(['error' => 'Cet utilisateur n’est pas un artiste.'], 404);
        }

        // Informations générales sur l'utilisateur et l'artiste
        $phoneNumber = $artist->phone_number;
        $artistName = $artist->first_name . ' ' . $artist->last_name;
        $nationality = $artist->nationality;

        // Obtenir les contenus de l'artiste
        $contents = $artist->contents;

        // Récupérer les statistiques pour un contenu spécifique
        $selectedContent = $contents->first(); // Remplacer par un ID spécifique si nécessaire
        if ($selectedContent) {
            $dailyViews = View::where('content_id', $selectedContent->id)
                ->whereDate('viewed_at', now())
                ->count();

            $weeklyViews = View::where('content_id', $selectedContent->id)
                ->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            $monthlyViews = View::where('content_id', $selectedContent->id)
                ->whereMonth('viewed_at', now()->month)
                ->count();
        } else {
            $dailyViews = $weeklyViews = $monthlyViews = 0; // Si aucun contenu sélectionné
        }

        // Historique des contenus publiés
        $history = $contents->map(function ($content) {
            return [
                'title' => $content->title,
                'status' => $content->status,
                'publication_status' => $content->publication_status,
                'created_at' => $content->created_at->format('d M Y'),
                'views_count' => $content->views_count,
            ];
        });

        // Retourner les données
        return response()->json([
            'artist' => [
                'name' => $artistName,
                'phone' => $phoneNumber,
                'nationality' => $nationality,
            ],
            'selected_content' => [
                'title' => $selectedContent->title ?? null,
                'weekly_views' => $weeklyViews,
                'daily_views' => $dailyViews,
                'monthly_views' => $monthlyViews,
            ],
            'history' => $history,
        ]);
    }



    
}
