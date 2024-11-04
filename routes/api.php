<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArtisteController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes pour authentification des clients
Route::post('/register/client', [AuthController::class, 'registerClient']);
Route::post('/login/client', [AuthController::class, 'loginClient']);

// Routes pour authentification des artistes
Route::post('/register/artist', [AuthController::class, 'registerArtist']);
Route::post('/login/artist', [AuthController::class, 'loginArtist']);

// Routes pour authentification de Admin
Route::prefix('admin')->group(function () {
    Route::post('loginAdmin', [AuthController::class, 'loginAdmin']);
});

// Routes communes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Routes pour authentification avec google
Route::get('auth/google/redirect/{userType}', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Routes pour la Reinitialisation de mots de passe 
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);





// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    });
});