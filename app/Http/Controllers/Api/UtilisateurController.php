<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Module Utilisateurs : CRUD par école, rôles, activation/désactivation,
 * réinitialisation de mot de passe.
 */
class UtilisateurController extends ApiController
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Liste des utilisateurs (de l'école pour les rôles non superadmin).
     */
    public function index(Request $request): JsonResponse
    {
        $cible = $this->autoriserGestion($request);
        if ($cible) {
            return $cible;
        }

        $users = User::with('roles')
            ->when(! $request->user()->hasRole('superadmin'), fn ($q) => $q->where('school_id', $request->user()->school_id))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->success(UserResource::collection($users), 'Utilisateurs récupérés.');
    }

    /**
     * Création d'un utilisateur rattaché à l'école + rôle(s).
     */
    public function store(Request $request): JsonResponse
    {
        $cible = $this->autoriserGestion($request);
        if ($cible) {
            return $cible;
        }

        $estSuperadmin = $request->user()->hasRole('superadmin');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        // Seul un superadmin peut créer un autre superadmin.
        if (! $estSuperadmin && in_array('superadmin', $data['roles'], true)) {
            return $this->error('Créer un super administrateur est réservé au super administrateur.', 403);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'school_id' => $estSuperadmin
                ? ($request->integer('school_id') ?: null)
                : $request->user()->school_id,
        ]);

        $user->syncRoles($data['roles']);
        $this->audit->log('utilisateurs', 'creation', "Création de l'utilisateur {$user->email}", $user->school_id);

        return $this->success(new UserResource($user->load('roles')), 'Utilisateur créé.', 201);
    }

    /**
     * Activation / désactivation.
     */
    public function activerDesactiver(User $user, Request $request): JsonResponse
    {
        $refus = $this->verifierCible($request, $user);
        if ($refus) {
            return $refus;
        }

        $user->update(['actif' => ! $user->actif]);
        $this->audit->log('utilisateurs', 'statut', $user->actif ? 'Utilisateur activé' : 'Utilisateur désactivé', $user->school_id);

        return $this->success(null, $user->actif ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }

    /**
     * Réinitialisation du mot de passe.
     */
    public function reinitialiserMotDePasse(User $user, Request $request): JsonResponse
    {
        $refus = $this->verifierCible($request, $user);
        if ($refus) {
            return $refus;
        }

        $data = $request->validate(['password' => ['required', 'string', 'min:8']]);

        $user->update(['password' => $data['password']]);
        $this->audit->log('utilisateurs', 'mot_de_passe', "Réinitialisation du mot de passe de {$user->email}", $user->school_id);

        return $this->success(null, 'Mot de passe réinitialisé.');
    }

    /**
     * Suppression (soft) d'un utilisateur.
     */
    public function destroy(User $user, Request $request): JsonResponse
    {
        $refus = $this->verifierCible($request, $user);
        if ($refus) {
            return $refus;
        }

        $user->delete();
        $this->audit->log('utilisateurs', 'suppression', "Suppression de l'utilisateur {$user->email}", $user->school_id);

        return $this->success(null, 'Utilisateur supprimé.');
    }

    /**
     * Vérifie que l'utilisateur courant peut gérer les comptes.
     * Retourne une réponse d'erreur à renvoyer, ou null si autorisé.
     */
    private function autoriserGestion(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('superadmin') && ! $user->hasRole('admin_ecole')) {
            return $this->error('Réservé à l\'administration.', 403);
        }

        return null;
    }

    /**
     * Vérifie que le compte ciblé peut être modifié/désactivé/supprimé :
     * gestion réservée à l'administration, école identique pour un
     * non-superadmin, et interdiction de toucher son propre compte ou un
     * superadmin. Retourne une réponse d'erreur, ou null si autorisé.
     */
    private function verifierCible(Request $request, User $cible): ?JsonResponse
    {
        $refus = $this->autoriserGestion($request);
        if ($refus) {
            return $refus;
        }

        $user = $request->user();

        if (! $user->hasRole('superadmin')) {
            if ($cible->school_id !== $user->school_id) {
                return $this->error('Accès refusé.', 403);
            }

            if ($cible->id === $user->id || $cible->hasRole('superadmin')) {
                return $this->error('Action non autorisée sur ce compte.', 403);
            }
        }

        return null;
    }
}