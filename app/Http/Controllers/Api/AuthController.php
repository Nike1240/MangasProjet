<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{

     /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle($userType)
    {
        // Store the user type (client/artist) in session
        session(['intended_user_type' => $userType]);
        
        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl()
        ]);
    }

    /**
     * Handle Google callback.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Récupérer le type d'utilisateur depuis la session ou la requête
            $userType = session('intended_user_type', $request->user_type);
            
            // Chercher si l'utilisateur existe déjà
            $user = User::where('email', $googleUser->email)->first();
            
            if ($user) {
                // Vérifier si le rôle correspond
                if ($user->role !== $userType) {
                    return response()->json([
                        'message' => "Ce compte Google est déjà associé à un compte " . 
                                   ($user->role === 'client' ? 'client' : 'artiste')
                    ], 400);
                }
            } else {
                // Créer un nouvel utilisateur
                $user = User::create([
                    'email' => $googleUser->email,
                    'password' => Hash::make(str_random(24)), // Mot de passe aléatoire
                    'role' => $userType,
                    'google_id' => $googleUser->id,
                ]);
                
                // Créer le profil correspondant
                if ($userType === 'client') {
                    Client::create(['user_id' => $user->id]);
                } else {
                    Artist::create(['user_id' => $user->id]);
                }
            }
            
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'status' => 'success',
                'message' => 'Connexion Google réussie',
                'token' => $token,
                'token_type' => 'Bearer',
                'role' => $userType
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Échec de l\'authentification Google'
            ], 500);
        }
    }
     /**
     * Handle Admin Login
     */

    // public function loginAdmin(Request $request)
    // {
    //     $credentials = $request->validate([
    //         'email' => ['required', 'email'],
    //         'password' => ['required'],
    //     ]);

    //     if (Auth::guard('admin')->attempt($credentials)) {
    //         $request->session()->regenerate();
    //         return redirect()->intended('admin/dashboard');
    //     }

    //     return back()->withErrors([
    //         'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
    //     ]);
    // }
    public function loginAdmin(Request $request)
{
    try {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            // $request->session()->regenerate();
            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Email ou mot de passe invalide'
        ], 401);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue',
            'debug' => $e->getMessage()
        ], 500);
    }
}
  
  /**
     * Handle client registration.
     */
    public function registerClient(Request $request)
    {
        if ($request->has('google_token')) {
            return $this->redirectToGoogle('client');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'client' // Rôle défini automatiquement
        ]);
        
        Client::create([
            'user_id' => $user->id
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Inscription client réussie',
            'token' => $token,
            'token_type' => 'Bearer',
            'role' => 'client'
        ], 201);
    }

    /**
     * Handle artist registration.
     */
    public function registerArtist(Request $request)
    {
        if ($request->has('google_token')) {
            return $this->redirectToGoogle('client');
        }
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'artist' // Rôle défini automatiquement
        ]);

        Artist::create([
            'user_id' => $user->id
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Inscription artiste réussie',
            'token' => $token,
            'token_type' => 'Bearer',
            'role' => 'artist'
        ], 201);
    }

    /**
     * Handle client login.
     */
    public function loginClient(Request $request)
    {
        if ($request->has('google_token')) {
            return $this->redirectToGoogle('client');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Vérifier si l'utilisateur est bien un client
        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Accès non autorisé. Ce compte n\'est pas un compte client.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion client réussie',
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Handle artist login.
     */
    public function loginArtist(Request $request)
    {
        if ($request->has('google_token')) {
            return $this->redirectToGoogle('artist');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Vérifier si l'utilisateur est bien un artiste
        if ($user->role !== 'artist') {
            return response()->json([
                'message' => 'Accès non autorisé. Ce compte n\'est pas un compte artiste.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion artiste réussie',
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Retrieve the authenticated user's information.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
    * Envoyer le lien de réinitialisation du mot de passe
    */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $token = Str::random(64);

        // Stocker le token dans la base de données
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Envoyer l'email avec le token
        try {
            Mail::send('emails.reset-password', [
                'token' => $token,
                'email' => $request->email
            ], function($message) use ($request) {
                $message->to($request->email);
                $message->subject('Réinitialisation de votre mot de passe');
            });
        
            return response()->json([
                
                'status' => 'success',
                'message' => 'Email de réinitialisation envoyé avec succès'
               
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur d\'envoi de mail : '.$e->getMessage());
            \Log::error('Détails de l\'erreur', [
                'email' => $request->email,
                'token' => $token,
                'trace' => $e->getTraceAsString(),
            ]);
        
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()
            ], 500);
        }
    } 

    /**
     * Valider le token et réinitialiser le mot de passe
     */
    // public function resetPassword(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email|exists:users',
    //         'token' => 'required|string',
    //         'password' => 'required|string|min:8|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     // Vérifier le token
    //     $resetRecord = DB::table('password_reset_tokens')
    //         ->where('email', $request->email)
    //         ->first();

    //     if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Token invalide ou expiré'
    //         ], 400);
    //     }

    //     // Vérifier si le token n'est pas expiré (24h par exemple)
    //     if (Carbon::parse($resetRecord->created_at)->addHours(24)->isPast()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Le token a expiré'
    //         ], 400);
    //     }

    //     // Mettre à jour le mot de passe
    //     $user = User::where('email', $request->email)->first();
    //     $user->password = Hash::make($request->password);
    //     $user->save();

    //     // Supprimer le token utilisé
    //     DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Mot de passe réinitialisé avec succès'
    //     ]);
    // }

    public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            // Mise à jour correcte du mot de passe
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60), // Régénère le remember_token
            ])->save();

            // Déconnecter l'utilisateur de toutes ses sessions
            $user->tokens()->delete();

            // Déclencher l'événement de réinitialisation
            event(new PasswordReset($user));
        }
    );

    if ($status == Password::PASSWORD_RESET) {
        return response()->json([
            'status' => 'success',
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    return response()->json([
        'status' => 'error',
        'message' => trans($status)
    ], 400);
}

}


