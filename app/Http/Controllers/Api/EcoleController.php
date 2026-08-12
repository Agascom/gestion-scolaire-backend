<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EcoleResource;
use App\Models\School;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion des écoles (multi-écoles).
 */
class EcoleController extends ApiController
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Liste des écoles :
     * - superadmin : toutes les écoles ;
     * - autres rôles : uniquement l'école de rattachement (school_id).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('superadmin')) {
            $ecoles = School::orderBy('nom')->get();
        } else {
            $ecoles = $user->school_id ? School::whereKey($user->school_id)->get() : collect();
        }

        return $this->success(EcoleResource::collection($ecoles), 'Écoles récupérées.');
    }

    /**
     * Détail d'une école (avec paramètres).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $query = School::with('settings');

        if (! $user->hasRole('superadmin') && $user->school_id !== $id) {
            return $this->error('Accès refusé.', 403);
        }

        $ecole = $query->findOrFail($id);

        return $this->success(new EcoleResource($ecole), 'École récupérée.');
    }

    /**
     * Création d'une école (superadmin uniquement).
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('superadmin')) {
            return $this->error('Réservé au super administrateur.', 403);
        }

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'numero_agrement' => ['nullable', 'string', 'unique:schools,numero_agrement'],
        ]);

        $ecole = School::create([
            ...$data,
            'logo_path' => optional($request->file('logo'))->store('ecoles/logos', 'public'),
        ]);

        $this->audit->log('ecoles', 'creation', "Création de l'école {$ecole->nom}");

        return $this->success(new EcoleResource($ecole), 'École créée.', 201);
    }

    /**
     * Mise à jour d'une école.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $ecole = School::findOrFail($id);

        if (! $user->hasRole('superadmin') && $user->school_id !== $id) {
            return $this->error('Accès refusé.', 403);
        }

        $data = $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'sigle' => ['sometimes', 'string', 'max:50'],
            'adresse' => ['sometimes', 'nullable', 'string'],
            'telephone' => ['sometimes', 'nullable', 'string'],
            'email' => ['sometimes', 'nullable', 'email'],
            'logo' => ['sometimes', 'image', 'max:2048'],
            'statut' => ['sometimes', 'boolean'],
        ]);

        $ecole->update([
            ...$data,
            'logo_path' => $request->hasFile('logo') ? $request->file('logo')->store('ecoles/logos', 'public') : $ecole->logo_path,
        ]);

        $this->audit->log('ecoles', 'modification', "Modification de l'école {$ecole->nom}");

        return $this->success(new EcoleResource($ecole), 'École mise à jour.');
    }
}