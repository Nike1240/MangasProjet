<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Models\Artist;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function showProfile()
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['message' => 'Admin non trouvé.'], 404);
        }

        return response()->json(['admin' => $admin], 200);
    }


    public function updateProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user(); 
        
        \Log::info('Update profile request data', $request->all());

        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'nullable|string',
                'adresse' => 'nullable|string',
                'email' => 'required|email|unique:admins,email,' . $admin->id,
                'profil_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            
            if ($request->hasFile('profil_image')) {
                $file = $request->file('profil_image');
                $path = $file->store('profile_images', 'public'); 
                $validated['profil_image'] = $path;
            }

            $admin->update($validated);
            \Log::info('Admin updated', $admin->toArray());

            return response()->json([
                'message' => 'Profil mis à jour avec succès',
                'admin' => $admin->refresh() // Recharger les données depuis la base
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating profile: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la mise à jour du profil', 'details' => $e->getMessage()], 500);
        }
    }

    /** 
     * Réinitialiser l'image de profil.
     */
    public function resetProfileImage()
    {
        $admin = Auth::guard('admin')->user();

        if ($admin->profil_image) {
            $oldPath = public_path('storage/uploads/' . $admin->profil_image);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            $admin->update(['profil_image' => null]);
        }

        return response()->json(['message' => 'Image de profil réinitialisée avec succès.'], 200);
    }

    /**
     * Mettre à jour le mot de passe.
     */
    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->old_password, $admin->password)) {
            return response()->json(['message' => 'L\'ancien mot de passe est incorrect.'], 403);
        }

        $admin->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Mot de passe mis à jour avec succès.'], 200);
    }


}
