<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EcoleResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Authentification via jetons Sanctum.
 */
class AuthController extends ApiController
{
    /**
     * Connexion : retourne un jeton Sanctum rattaché à l'utilisateur.
     *
     * Body : { email, password }
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('Identifiants invalides.', 401);
        }

        $token = $user->createToken('api-gestion-scolaire')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('ecole')),
        ], 'Connexion réussie.');
    }

    /**
     * Déconnexion : révocation du jeton courant de l'utilisateur.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Déconnexion réussie.');
    }

    /**
     * Utilisateur connecté (avec école et rôles).
     */
    public function user(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user()->load('ecole')),
            'Utilisateur récupéré.'
        );
    }
}