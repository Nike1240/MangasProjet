<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckArtistAccountStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $artist = auth()->guard('sanctum')->user();

        if ($artist && !$artist->isAccountActive()) {
            // Déconnexion de tous les tokens
            $artist->tokens()->delete();

            return response()->json([
                'status' => 'error',
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        return $next($request);
    }
}
