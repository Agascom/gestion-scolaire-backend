<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\Eleve;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        if (! $user->actif) {
            return $this->error('Compte désactivé. Contactez l\'administrateur.', 403);
        }

        $token = $user->createToken('api-gestion-scolaire')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
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
     * Utilisateur connecté (avec école, rôles et contexte du rôle).
     */
    public function user(Request $request): JsonResponse
    {
        return $this->success(
            $this->userPayload($request->user()),
            'Utilisateur récupéré.'
        );
    }

    /**
     * Payload utilisateur : ressource standard enrichie du contexte du rôle.
     *
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return array_merge(
            (new UserResource($user->loadMissing('ecole')))->resolve(),
            $this->contexteRole($user)
        );
    }

    /**
     * Contexte spécifique au rôle : élèves du parent, profil de l'enseignant.
     *
     * @return array<string, mixed>
     */
    private function contexteRole(User $user): array
    {
        $contexte = [];

        if ($user->hasRole('parent')) {
            $contexte['parent'] = $user->parentEleves()
                ->with('eleve')
                ->get()
                ->pluck('eleve')
                ->filter()
                ->values()
                ->map(fn (Eleve $eleve) => [
                    'id' => $eleve->id,
                    'matricule' => $eleve->matricule,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'nom_complet' => $eleve->nom_complet,
                    'sexe' => $eleve->sexe,
                    'classe_actuelle' => optional($eleve->classeActuelle())?->only(['id', 'libelle']),
                ]);
        }

        if ($user->hasRole('enseignant')) {
            $contexte['profil_enseignant'] = optional($user->profilEnseignant)?->only([
                'id', 'nom', 'prenom', 'specialite', 'email', 'telephone',
            ]);
        }

        return $contexte;
    }
}
