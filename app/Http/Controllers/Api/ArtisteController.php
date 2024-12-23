<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ArtisteController extends Controller
{
    /**
     * Handle artist registration.
     * 
     */
    public function getAllArtists()
    {
        $artists = Artist::all();

        if ($artists->isEmpty()) {
            return response()->json(['message' => 'Aucun artiste trouvé.'], 404);
        }
    
        return response()->json([
            'artists' => $artists
        ]);
    }
    

     public function getProfile()
     {
         $artist = auth()->guard('sanctum')->user()->artist;
     
         if (!$artist) {
             return response()->json(['error' => 'Vous n\'êtes pas un artiste enregistré.'], 403);
         }
         return response()->json([
             'artist' => $artist
         ]);
     }

     public function getArtistById($id)
    {
        $artist = Artist::find($id);

        if (!$artist) {
            return response()->json(['message' => 'Artiste non trouvé.'], 404);
        }

        return response()->json([
            'artist' => $artist,
            'age' => $artist->age 
        ]);
    }


     
     public function updateProfile(Request $request)
    {
        $artist = auth()->guard('sanctum')->user()->artist;

        if (!$artist) {
            return response()->json(['error' => 'Vous n\'êtes pas un artiste enregistré.'], 403);
        }

        \Log::info('Update profile request data', $request->all());

        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'nullable|string',
                'bio' => 'nullable|string',
                'avatar' => 'nullable|image|max:2048',
                'website' => 'nullable|url',
                'social_links' => 'nullable|array',
                'social_links.*' => 'nullable|url',
                'nationality' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date'
            ]);

            if ($request->hasFile('avatar')) {
                if ($artist->avatar) {
                    Storage::disk('public')->delete($artist->avatar);
                }
                $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
                \Log::info('Avatar stored at: ' . $validated['avatar']);
            }

            $artist->update($validated);
            \Log::info('Artist updated', $artist->toArray());

            return response()->json([
                'message' => 'Profil mis à jour avec succès',
                'artist' => $artist->refresh() // Recharger les données depuis la base
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating profile: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la mise à jour du profil', 'details' => $e->getMessage()], 500);
        }
    }

     

        public function getAgeAttribute()
    {
        return $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null;
    }



    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:artists',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string',
            'bio' => 'nullable|string',
            'speciality' => 'nullable|string',
            'portfolio_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $artist = Artist::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'bio' => $request->bio,
            'speciality' => $request->speciality,
            'portfolio_url' => $request->portfolio_url
        ]);

        $token = $artist->createToken('auth_token')->plainTextToken;

        return response()->json([
            'artist' => $artist,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Handle artist login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $artist = Artist::where('email', $request->email)->first();

        if (!$artist || !Hash::check($request->password, $artist->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $artist->createToken('auth_token')->plainTextToken;

        return response()->json([
            'artist' => $artist,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }
}
