<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArtisteController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\ContentInteractionController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\DKeyController;
use App\Http\Controllers\AdViewController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PriceConfigurationController;
use App\Http\Controllers\DKeyPurchaseController;
use App\Http\Controllers\TestPaymentController;
use App\Http\Controllers\DKeyConsumptionController;



// Routes pour authentification des clients
Route::post('/register/client', [AuthController::class, 'registerClient']);//✅
Route::post('/login/client', [AuthController::class, 'loginClient']);//✅

// Routes pour authentification des artistes
Route::post('/register/artist', [AuthController::class, 'registerArtist']);//✅
Route::post('/login/artist', [AuthController::class, 'loginArtist']);//✅

// Routes pour authentification de Admin
Route::prefix('admin')->group(function () {
    Route::post('loginAdmin', [AuthController::class, 'loginAdmin']);//✅
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


/*
|--------------------------------------------------------------------------
| API Routes Paiement
|--------------------------------------------------------------------------
*/

Route::post('/payment/process', [PaiementController::class, 'processPayment'])->name('payment.process');

Route::get('/callback', [PaiementController::class, 'callback'])->name('callback');

Route::get('/payment/success', [PaiementController::class, 'paymentSuccess'])->name('payment.success');

Route::get('/payment/failure', [PaiementController::class, 'paymentFailure'])->name('payment.failure');


// Route pour le parametrage ou la mise à jour des infos clients, admin ou artist 

// pour artists 
Route::get('artists/{id}', [ArtisteController::class, 'getArtistById']); // Route pour la récupération des infos sur un artist spécifique ✅

Route::post('artist/profil', [ArtisteController::class, 'updateProfile']); // Route de mise à jour de profil Artist ✅

Route::get('artist/profil', [ArtisteController::class, 'getProfile']); // Route de récupération des infos sur l'artist connecté ✅

Route::get('artists', [ArtisteController::class, 'getAllArtists']); // Route pour la récupération de tous les artists ✅

// Pour client 
Route::get('clients/{id}', [ClientController::class, 'getClientById']); // Route pour la récupération des infos sur un client spécifique ✅

Route::get('clients', [ClientController::class, 'getAllClients']); // Route pour la récupération de tous les clients ✅

Route::post('client/profil', [ClientController::class, 'updateProfile']); // Route de mise à jour de profil Client✅

//Pour admin
Route::middleware(['auth:admin'])->group(function () {

    Route::get('/admin/profile', [AdminController::class, 'showProfile']); //✅

    Route::post('/admin/profile/update', [AdminController::class, 'updateProfile']); //✅

    Route::post('/admin/profile/image', [AdminController::class, 'updateProfileImage']);//✅

    Route::post('/admin/profile/image/reset', [AdminController::class, 'resetProfileImage']);//✅

    Route::post('/admin/password/update', [AdminController::class, 'updatePassword']);
});

// Routes pour afficher, enrégistrer, modifier ou supprimer un contenu 

Route::post('contents/{content}', [ContentController::class, 'update']); //✅

Route::apiResource('contents', ContentController::class, ['only' => ['index', 'show', 'store', 'destroy']])->names(['index' => 'contents.index', 'show' => 'contents.show', 'store' => 'contents.store', 'destroy' => 'contents.destroy']); //✅

// Routes pour enrégistrer et afficher les chapitres 

Route::post('contents/update/{content}/chapters/{chapter}', [ChapterController::class, 'update']);//✅

Route::apiResource('contents/{content}/chapters', ChapterController::class, ['only' => ['index', 'show', 'store','destroy']])->names(['index' => 'chapters.index', 'show' => 'chapters.show', 'store' => 'chapters.store', 'destroy' => 'chapters.destroy']); //✅  

Route::post('/chapters/{chapter}/toggle-like', [ChapterController::class, 'toggleLike']); // Route de mention j'aime //✅

// Routes pour enrégistrer et afficher les saisons

Route::post('contents/update/{content}/seasons/{season}', [SeasonController::class, 'update']); //✅

Route::apiResource('contents/{content}/seasons', SeasonController::class, ['only' => ['index', 'show', 'store','destroy']])->names(['index' => 'seasons.index', 'show' => 'seasons.show', 'store' => 'seasons.store', 'destroy' => 'seasons.destroy']); //✅

Route::post('/seasons/{season}/toggle-like', [SeasonController::class, 'toggleLike']); // Route de mention j'aime ✅

// Route concernant les Pages 

Route::post('{chapter}/update/pages/{page}', [PageController::class, 'update']); //✅

Route::apiResource('{chapter}/pages', PageController::class, ['only' => ['index', 'show', 'store','destroy']])->names(['index' => 'pages.index', 'show' => 'pages.show', 'store' => 'pages.store', 'destroy' => 'pages.destroy']);;  

// Route concernant les episodes 

// Route::post('seasons/{season}/episodes', [EpisodeController::class, 'store']); 

Route::post('seasons/update/{season}/episodes/{episode}', [EpisodeController::class, 'update']); //✅

Route::apiResource('seasons/{season}/episodes', EpisodeController::class, ['only' => ['index', 'show', 'store','destroy']])->names(['index' => 'episodes.index', 'show' => 'episodes.show', 'store' => 'episodes.store', 'destroy' => 'episodes.destroy']);  //✅

Route::post('/episodes/{episode}/toggle-like', [EpisodeController::class, 'toggleLike']); // Route de mention j'aime //✅

// Routes concernant les fonctions de recherche

Route::get('/search', [ContentController::class, 'search'])->name('library.search'); // Route pour la recherche générale

Route::get('/searchManga', [ContentController::class, 'searchManga'])->name('library.searchManga'); //Route pour la recherche basée uniquement sur les mangas

Route::get('/searchAnime', [ContentController::class, 'searchAnime'])->name('library.searchAnime'); // Route pour la recherche basée uniquement sur les animés

Route::get('/categories/{genre}',[ContentController::class, 'details'])->name('categories.show'); // Route pour avoir les contenus d'une catégorie(genre) donné

// Routes pour les interactions par rapport aux contenus

Route::middleware('auth:sanctum')->group(function () {
    
Route::post('/contents/{content}/toggle-like', [ContentInteractionController::class, 'toggleLike']); // Route de mention j'aime ✅

Route::post('/contents/{content}/comments', [ContentInteractionController::class, 'addComment']); // Route d'ajout de commentaire ✅

Route::get('/contents/{content}/comments', [ContentInteractionController::class, 'getComments']); // Route de récupération des commentaires d'un contenu donné ✅

Route::post('/contents/toggleFavorite/{type}/{id}', [ContentInteractionController::class, 'toggleFavorite']); // Route de mention favoris ✅

Route::get('/favorites', [ContentInteractionController::class, 'getUserFavorites']);// Route de récupération des favoris de l'utilisateur connecté ✅

});

// Routes concernant la gestion des D-Key 

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/dkeys/balance/{userId}', [DKeyController::class, 'getBalance']); // Route pour consulter le solde de Dkey d'un client donné ✅

    Route::post('/dkeys/purchase', [DKeyPurchaseController::class, 'initiatePurchase']); // Route pour initier l'achat ✅

    Route::get('/dkey-packs', [DKeyPurchaseController::class, 'packs']); // Liste des différents packs ✅

    Route::post('/test-payment/{transaction}', [TestPaymentController::class, 'process'])->name('test-payment.process');// Route pour valider le paiement (c'est juste un test la fonction de paiement n'a pas encore été implémenté) ✅

    Route::post('/priceConfig', [PriceConfigurationController::class, 'store']); // Route pour permettre à l'admin de créer de nouvelles configurations de prix ✅

    Route::post('/priceConfig/{package}', [PriceConfigurationController::class, 'updatePrice']); // Route pour permettre à l'admin de modifier les prix des packs de D-key existants ✅

    Route::post('/priceConfig/param/{package}', [PriceConfigurationController::class, 'update']); // Route pour mettre à jour les autres paramètres de la configuration des prix ✅

});

    // Routes consommation DKey  et gestion de l'accès gratuit

// Pour les pages de manga

Route::post('/content/consume/page/{itemId}', [DKeyConsumptionController::class, 'consumeContent']) //Consommation de pages ✅
    ->middleware('auth:sanctum')
    ->defaults('type', 'page');

// Pour les épisodes d'anime

Route::post('/content/consume/episode/{itemId}', [DKeyConsumptionController::class, 'consumeContent']) //Consommation d'épisodes ✅
    ->middleware('auth:sanctum')
    ->defaults('type', 'episode');

<<<<<<< HEAD
=======
// Pour vérifier l'accès

Route::get('/content/check-access/{contentId}', [DKeyConsumptionController::class, 'checkAccess']) //✅
    ->middleware('auth:sanctum');

// Routes pour l'ajout de vidéo publicitaire par l'admin

Route::post('/ads/view', [AdViewController::class, 'recordAdView']); // Récupère le temps de visionnage et gère le calcul du nombre de dkeys en fonction de cette durée

Route::post('/ads/add', [AdViewController::class, 'addAdvertisement']); // ajout de vidéo publicitaire par l'admin



Route::get('/artist/{userId}/dashboard', [ContentController::class, 'getArtistDashboard']); // Dashboard artist ✅

Route::post('/download-offline/{type}/{id}', [ContentInteractionController::class, 'downloadForOffline']); // Route de téléléchargement d'un élément ✅

Route::get('/offline-items', [ContentInteractionController::class, 'getOfflineItems']); //Route de récupération des éléments hors ligne ✅

Route::post('/download-collection-offline/{type}/{id}', [ContentInteractionController::class, 'downloadCollectionForOffline']); // Route de téléléchargement directe d'une collection ✅


    
    

    
    
>>>>>>> 63c78c9ab80a924a0181f9bda66fe2f6841a8b2e
