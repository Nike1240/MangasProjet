<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
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