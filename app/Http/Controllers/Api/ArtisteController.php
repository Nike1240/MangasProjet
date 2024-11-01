<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ArtistAuthController extends Controller
{
    /**
     * Handle artist registration.
     */
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
