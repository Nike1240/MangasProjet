<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PriceConfigurationController extends Controller
{
    /**
     * Crée une nouvelle configuration de package
     */
    public function store(Request $request)
    {

        $adminName = auth()->guard('admin')->user()->last_name . '-' . $request->first_name;


        if (!$adminName) {
            return response()->json(['error' => 'Vous n\'êtes pas un administrateur enregistré.'], 403);
        }

        $validated = $request->validate([
            'package_type_id' => 'required|exists:package_types,id',
            'name' => 'required|string|max:255',
            'is_pack' => 'required|boolean',
            'unit_price' => 'required_if:is_pack,false|nullable|numeric|min:0',
            'pack_price' => 'required_if:is_pack,true|nullable|numeric|min:0',
            'pack_quantity' => 'required_if:is_pack,true|nullable|integer|min:1',
            'duration' => 'nullable|string',
            'pages_per_dkey' => 'required|integer|min:1',
            'episodes_per_dkey' => 'required|integer|min:1',
            'min_quantity' => 'required|integer|min:1',
            'max_quantity' => 'nullable|integer|gt:min_quantity',
            'is_active' => 'boolean'
        ]);

        try {
            $package = DB::transaction(function () use ($validated) {
                return Package::create($validated);
            });

            return response()->json([
                'message' => 'Configuration créée avec succès',
                'package' => $package
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour le prix d'un package
     */
    public function updatePrice(Request $request, Package $package)
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return response()->json(['error' => 'Vous n\'êtes pas un administrateur enregistré.'], 403);
        }

        $adminName = $admin->last_name . '-' . $admin->first_name;

        $validated = $request->validate([
            'unit_price' => 'required_if:is_pack,false|nullable|numeric|min:0',
            'pack_price' => 'required_if:is_pack,true|nullable|numeric|min:0',
            'reason' => 'required|string'
        ]);

        try {
            DB::transaction(function () use ($request, $package, $validated, $adminName) {
                // Enregistrer l'historique pour le prix unitaire si applicable
                if (!$package->is_pack && isset($validated['unit_price'])) {
                    $this->createPriceHistory($package, 'unit_price', $validated['unit_price'], $validated['reason'], $adminName);
                }

                // Enregistrer l'historique pour le prix du pack si applicable
                if ($package->is_pack && isset($validated['pack_price'])) {
                    $this->createPriceHistory($package, 'pack_price', $validated['pack_price'], $validated['reason'], $adminName);
                }

                // Mettre à jour les prix
                $package->update(array_filter($validated, function ($key) {
                    return in_array($key, ['unit_price', 'pack_price']);
                }, ARRAY_FILTER_USE_KEY));
            });

            return response()->json([
                'message' => 'Prix mis à jour avec succès',
                'package' => $package,
                'admin_name' => $adminName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du prix',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Met à jour les autres paramètres du package
     */
    public function update(Request $request, Package $package)
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return response()->json(['error' => 'Vous n\'êtes pas un administrateur enregistré.'], 403);
        }

        $adminName = $admin->last_name . '-' . $admin->first_name;


        if (!$adminName) {
            return response()->json(['error' => 'Vous n\'êtes pas un administrateur enregistré.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'duration' => 'nullable|string',
            'pages_per_dkey' => 'sometimes|integer|min:1',
            'episodes_per_dkey' => 'sometimes|integer|min:1',
            'min_quantity' => 'sometimes|integer|min:1',
            'max_quantity' => 'nullable|integer|gt:min_quantity',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $package->update($validated);

            return response()->json([
                'message' => 'Configuration mise à jour avec succès',
                'package' => $package
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée un enregistrement dans l'historique des prix
     */
    private function createPriceHistory(Package $package, string $priceType, float $newPrice, string $reason, string $adminName): void
    {
        $oldPrice = $package->$priceType;

        PriceHistory::create([
            'package_id' => $package->id,
            'price_type' => $priceType,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'changed_at' => now(),
            'changed_by' => $adminName, // Utilisation de l'adminName passé en paramètre
            'reason' => $reason
        ]);
    }

}