<?php

namespace App\Http\Controllers;
use App\Models\Package;
use App\Models\DKey;
use App\Services\DKeyConsumptionService;
use Illuminate\Http\Request;

class DKeyConsumptionController extends Controller
{
    private $dkeyService;

    public function __construct(DKeyConsumptionService $dkeyService)
    {
        $this->dkeyService = $dkeyService;
    }

    /**
     * Gère l'accès aux pages de manga
     */
    public function readMangaPages(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'pages_count' => 'required|integer|min:1'
        ]);

        $package = Package::findOrFail($validated['package_id']);
        $user = auth()->user();

        $result = $this->dkeyService->handlePageConsumption(
            $user,
            $package,
            $validated['pages_count']
        );

        return response()->json($result);
    }

    /**
     * Gère l'accès aux épisodes d'anime
     */
    public function watchAnimeEpisodes(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'episodes_count' => 'required|integer|min:1'
        ]);

        $package = Package::findOrFail($validated['package_id']);
        $user = auth()->user();

        $result = $this->dkeyService->handleEpisodeConsumption(
            $user,
            $package,
            $validated['episodes_count']
        );

        return response()->json($result);
    }

    /**
     * Vérifie si l'utilisateur peut accéder au contenu
     */
    public function checkAccess()
    {
        $user = auth()->user();
        $canAccess = $this->dkeyService->canAccessContent($user);

        return response()->json([
            'can_access' => $canAccess,
            'remaining_keys' => DKey::where('user_id', $user->id)
                                  ->active()
                                  ->sum('key_remaining')
        ]);
    }
}
