<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{

    public function getAllClients()
{
    // Récupérer tous les clients
    $clients = Client::all();

    // Retourner la liste des clients
    return response()->json([
        'clients' => $clients
    ]);
}

    public function getClientById($id)
    {
        // Récupérer le client par son ID
        $client = Client::find($id);
    
        // Vérifier si le client existe
        if (!$client) {
            return response()->json(['message' => 'Client non trouvé.'], 404);
        }
    
        // Retourner les informations du client
        return response()->json([
            'client' => $client
        ]);
    }

    
    public function updateProfile(Request $request)
    {
        $client = auth()->guard('sanctum')->user()->client;

        if (!$client) {
            return response()->json(['error' => 'Vous n\'êtes pas un client enregistré.'], 403);
        }

        \Log::info('Update profile request data', $request->all());

        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
            ]);

            $client->update($validated);

            if ($request->has('name')) {
                $firstLetter = strtoupper(substr($request->name, 0, 1)); // Première lettre du nom
                $avatar = $this->generateAvatar($firstLetter);

                $client->avatar = $avatar;
                $client->save();
            }

            \Log::info('Client updated', $client->toArray());

            return response()->json([
                'message' => 'Profil mis à jour avec succès',
                'client' => $client->refresh()  // Rafraîchir les données du client après la mise à jour
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating profile: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la mise à jour du profil', 'details' => $e->getMessage()], 500);
        }
    }

    private function generateAvatar($letter)
    {
        
        $backgroundColor = $this->generateRandomColor();

        $image = \Image::canvas(50, 50, $backgroundColor);
        $image->text($letter, 25, 25, function($font) {
            $font->file(1); 
            $font->size(30);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        $path = 'avatars/' . uniqid() . '.png';
        $image->save(public_path('storage/' . $path));

        return $path; // Retourner le chemin vers l'avatar généré
    }

    private function generateRandomColor()
    {
        // Générer une couleur hexadécimale aléatoire (format #RRGGBB)
        $randomColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        return $randomColor;
    }



    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'company_name' => $request->company_name
        ]);

        $token = $client->createToken('auth_token')->plainTextToken;

        return response()->json([
            'client' => $client,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $client->createToken('auth_token')->plainTextToken;

        return response()->json([
            'client' => $client,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }
}