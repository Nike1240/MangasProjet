<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


use App\Services\MangaPageUploadService;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Chapter $chapter)
    {
        return response()->json($chapter->pages()->orderBy('page_number', 'asc')->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'pages' => 'required|array',
            'pages.*' => 'required|image|max:5120',
            'page_numbers' => 'required|array',
            'page_numbers.*' => 'required|integer|min:1'
        ]);

        $uploadedPages = [];
        $uploadService = app(MangaPageUploadService::class);

        foreach ($request->file('pages') as $index => $pageImage) {
            $pageNumber = $request->page_numbers[$index];
            
            $slug = Str::slug($chapter->title . '-page-' . $pageNumber);
            
            $uploadResult = $uploadService->uploadPage($pageImage, $index, $chapter->title);
            
            $page = $chapter->pages()->create([
                'page_number' => $pageNumber,
                'slug' => $slug,
                'status' => 'published',
                ...$uploadResult
            ]);
        
            $uploadedPages[] = $page;
        }

        $chapter->update(['pages_count' => $chapter->pages()->count()]);

        return response()->json($uploadedPages, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($chapterId, $pageId)
    {
        // Rechercher la page dans le contexte du chapitre spécifié
        $page = Page::where('chapter_id', $chapterId)->findOrFail($pageId);

        return response()->json($page);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'pages' => 'sometimes|array',
            'pages.*' => 'nullable|image|max:5120',
            'page_numbers' => 'sometimes|array',
            'page_numbers.*' => 'nullable|integer|min:1'
        ]);

        $updatedPages = [];
        $uploadService = app(MangaPageUploadService::class);

        if ($request->has('pages') && $request->has('page_numbers')) {
            foreach ($request->file('pages') as $index => $pageImage) {
                $pageNumber = $request->page_numbers[$index];
                $slug = Str::slug($chapter->title . '-page-' . $pageNumber);

                // On tente de retrouver la page existante par son numéro
                $existingPage = $chapter->pages()->where('page_number', $pageNumber)->first();

                if ($existingPage) {
                    // Si une page avec le même numéro existe, on met à jour l'image
                    $uploadResult = $uploadService->uploadPage($pageImage, $index, $chapter->title);
                    $existingPage->update([
                        'slug' => $slug,
                        ...$uploadResult
                    ]);
                    $updatedPages[] = $existingPage;
                } else {
                    // Sinon, on crée une nouvelle page
                    $uploadResult = $uploadService->uploadPage($pageImage, $index, $chapter->title);
                    $page = $chapter->pages()->create([
                        'page_number' => $pageNumber,
                        'slug' => $slug,
                        'status' => 'published',
                        ...$uploadResult
                    ]);
                    $updatedPages[] = $page;
                }
            }
        }

        // Mettre à jour le nombre total de pages du chapitre
        $chapter->update(['pages_count' => $chapter->pages()->count()]);

        return response()->json($updatedPages, 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        // Supprimer les fichiers associés
        Storage::delete([
            'public/manga_pages/' . $page->image_path,
            'public/manga_pages/thumbnails/' . $page->thumbnail_path
        ]);

        $chapter = $page->chapter;
        $page->delete();

        // Mettre à jour le compteur de pages
        $chapter->pages_count = $chapter->pages()->count();
        $chapter->save();

        return response()->json(null, 204);
    }

    /**
     * Réorganiser les numéros de pages
     */
    public function reorder(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'page_order' => 'required|array',
            'page_order.*' => 'required|integer|exists:pages,id'
        ]);

        foreach ($validated['page_order'] as $index => $pageId) {
            Page::where('id', $pageId)->update([
                'page_number' => $index + 1,
                'slug' => Str::slug($chapter->title . '-page-' . ($index + 1))
            ]);
        }

        return response()->json(['message' => 'Pages reordered successfully']);
    }
}