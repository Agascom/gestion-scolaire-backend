<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EncaissementResource;
use App\Http\Resources\FraisResource;
use App\Models\Depense;
use App\Models\Encaissement;
use App\Models\Frais;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\Salaire;
use App\Services\AuditService;
use App\Services\FinancesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Module Finances : frais, encaissements, dépenses, paie, achats/ventes
 * et états (journal de caisse, impayés, situation).
 */
class FinanceController extends ApiController
{
    use UtiliseAnneeCourante;

    public function __construct(
        private readonly AuditService $audit,
        private readonly FinancesService $finances,
    ) {
    }

    // ---------- Frais ----------

    public function fraisIndex(Request $request): JsonResponse
    {
        $frais = Frais::with(['cycle', 'classe'])
            ->when($request->boolean('actifs_uniquement'), fn ($q) => $q->where('actif', true))
            ->orderBy('libelle')
            ->get();

        return $this->success(FraisResource::collection($frais), 'Frais récupérés.');
    }

    public function fraisStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'periodicite' => ['required', 'in:annee,trimestre,mensuel'],
            'cycle_id' => ['nullable', 'exists:cycles,id'],
            'classe_id' => ['nullable', 'exists:classes,id'],
            'actif' => ['sometimes', 'boolean'],
        ]);

        $frais = Frais::create([...$data, 'school_id' => $this->schoolId()]);
        $this->audit->log('finances', 'creation_frais', "Création du frais {$frais->libelle} ({$frais->montant} FCFA)");

        return $this->success(new FraisResource($frais), 'Frais créé.', 201);
    }

    // ---------- Encaissements ----------

    public function encaissementsIndex(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $encaissements = Encaissement::with(['eleve', 'frais'])
            ->when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id))
            ->when($request->input('eleve_id'), fn ($q, $v) => $q->where('eleve_id', $v))
            ->when($request->input('statut'), fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('date_encaissement')
            ->paginate($request->integer('per_page', 15));

        return $this->success(EncaissementResource::collection($encaissements), 'Encaissements récupérés.');
    }

    public function encaisser(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $data = $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'frais_id' => ['required', 'exists:frais,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'mode' => ['required', 'in:especes,mobile_money,virement,cheque'],
            'reference' => ['nullable', 'string', 'max:255'],
            'statut' => ['sometimes', 'in:paye,partiel,en_attente'],
            'date_encaissement' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $encaissement = Encaissement::create([
            'school_id' => $this->schoolId(),
            'annee_academique_id' => $annee?->id,
            'numero_recu' => 'RECU-'.now()->format('Ymd').'-'.str_pad((string) mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'date_encaissement' => $data['date_encaissement'] ?? today(),
            ...$data,
        ]);

        $this->audit->log('finances', 'encaissement', "Encaissement {$encaissement->montant} FCFA (reçu {$encaissement->numero_recu})");

        return $this->success(new EncaissementResource($encaissement->load('eleve', 'frais')), 'Encaissement enregistré.', 201);
    }

    /**
     * Restes à payer par élève pour chaque frais.
     */
    public function restesAPayer(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $fraisActifs = Frais::where('actif', true)->get();
        $eleves = \App\Models\Eleve::where('statut', '!=', \App\Models\Eleve::STATUT_RADIE)->get();

        $detail = $eleves->map(function ($eleve) use ($fraisActifs, $annee) {
            $impayes = [];
            foreach ($fraisActifs as $frais) {
                $reste = $this->finances->resteAPayer($eleve, $frais, $annee);
                if ($reste > 0) {
                    $impayes[] = ['frais' => $frais->libelle, 'reste' => $reste];
                }
            }

            return [
                'eleve_id' => $eleve->id,
                'eleve' => $eleve->nom_complet,
                'matricule' => $eleve->matricule,
                'impayes' => $impayes,
                'total_impaye' => round(array_sum(array_column($impayes, 'reste')), 2),
            ];
        })->filter(fn ($r) => count($r['impayes']) > 0)->values();

        return $this->success($detail, 'Restes à payer.');
    }

    // ---------- Dépenses ----------

    public function depenseStore(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $data = $request->validate([
            'nature' => ['required', 'in:achat,maintenance,facture,fourniture'],
            'libelle' => ['required', 'string', 'max:255'],
            'fournisseur' => ['nullable', 'string'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_depense' => ['sometimes', 'date'],
            'piece_jointe' => ['nullable', 'file', 'max:5120'],
            'notes' => ['nullable', 'string'],
        ]);

        $depense = Depense::create([
            'school_id' => $this->schoolId(),
            'annee_academique_id' => $annee?->id,
            'date_depense' => $data['date_depense'] ?? today(),
            'piece_jointe_path' => optional($request->file('piece_jointe'))->store('finances/depenses', 'public'),
            ...$data,
        ]);

        $this->audit->log('finances', 'depense', "Dépense {$depense->libelle} : {$depense->montant} FCFA");

        return $this->success($depense, 'Dépense enregistrée.', 201);
    }

    // ---------- Paie ----------

    public function salaireStore(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $data = $request->validate([
            'enseignant_id' => ['required', 'exists:enseignants,id'],
            'mois' => ['required', 'date_format:Y-m'],
            'salaire_base' => ['required', 'numeric', 'min:0'],
            'primes' => ['sometimes', 'numeric', 'min:0'],
            'avances' => ['sometimes', 'numeric', 'min:0'],
            'retenues' => ['sometimes', 'numeric', 'min:0'],
            'statut' => ['sometimes', 'in:paye,en_attente'],
            'date_paiement' => ['sometimes', 'date'],
        ]);

        $net = (float) $data['salaire_base']
            + (float) ($data['primes'] ?? 0)
            - (float) ($data['avances'] ?? 0)
            - (float) ($data['retenues'] ?? 0);

        $salaire = Salaire::create([
            'school_id' => $this->schoolId(),
            'annee_academique_id' => $annee?->id,
            'net_a_payer' => $net,
            ...$data,
        ]);

        $this->audit->log('finances', 'paie', "Salaire {$data['mois']} : net {$net} FCFA");

        return $this->success($salaire, 'Salaire enregistré.', 201);
    }

    // ---------- Produits / Stock ----------

    public function produitStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string'],
            'prix_achat' => ['sometimes', 'numeric', 'min:0'],
            'prix_vente' => ['sometimes', 'numeric', 'min:0'],
            'quantite_stock' => ['sometimes', 'numeric', 'min:0'],
            'unite' => ['sometimes', 'string', 'max:50'],
            'taux_tva' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ]);

        $produit = Produit::create([...$data, 'school_id' => $this->schoolId()]);

        return $this->success($produit, 'Produit créé.', 201);
    }

    public function stockStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'produit_id' => ['required', 'exists:produits,id'],
            'type' => ['required', 'in:entree,sortie,vente,achat'],
            'quantite' => ['required', 'numeric', 'min:0.01'],
            'prix_unitaire' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string'],
            'date_mouvement' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $mouvement = DB::transaction(function () use ($data) {
            $produit = Produit::findOrFail($data['produit_id']);

            $montant = (float) $data['quantite'] * (float) $data['prix_unitaire'];

            if (in_array($data['type'], ['sortie', 'vente']) && $produit->quantite_stock < $data['quantite']) {
                abort(422, 'Stock insuffisant pour ce mouvement.');
            }

            $mult = in_array($data['type'], ['sortie', 'vente']) ? -1 : 1;
            $produit->increment('quantite_stock', $data['quantite'] * $mult);

            return MouvementStock::create([
                'school_id' => $this->schoolId(),
                'date_mouvement' => $data['date_mouvement'] ?? today(),
                'montant' => $montant,
                ...$data,
            ]);
        });

        $this->audit->log('finances', 'stock', "Mouvement stock {$data['type']} : {$data['quantite']}");

        return $this->success($mouvement, 'Mouvement de stock enregistré.', 201);
    }

    // ---------- États financiers ----------

    public function journalCaisse(): JsonResponse
    {
        return $this->success($this->finances->journalCaisse($this->anneeCourante()), 'Journal de caisse.');
    }

    public function situation(): JsonResponse
    {
        $annee = $this->anneeCourante();

        return $this->success([
            'journal' => $this->finances->journalCaisse($annee),
            'paie' => $this->finances->situationPaie($annee),
            'stock' => [
                'produits' => Produit::count(),
                'valeur_stock' => Produit::get()->sum(fn ($p) => (float) $p->quantite_stock * (float) $p->prix_achat),
            ],
        ], 'Situation financière.');
    }
}