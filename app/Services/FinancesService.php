<?php

namespace App\Services;

use App\Models\Encaissement;
use App\Models\Frais;
use App\Models\Eleve;
use App\Models\AnneeAcademique;
use Illuminate\Support\Collection;

/**
 * Gestion financière : restes à payer, impayés, journal de caisse.
 */
class FinancesService
{
    /**
     * Reste à payer d'un élève pour un frais donné (montant - total encaissé).
     */
    public function resteAPayer(Eleve $eleve, Frais $frais, AnneeAcademique|null $annee = null): float
    {
        $annee ??= $this->anneeCourante($eleve->school_id);

        $paye = Encaissement::where('eleve_id', $eleve->id)
            ->where('frais_id', $frais->id)
            ->where('statut', '!=', 'en_attente')
            ->when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id))
            ->sum('montant');

        return round(max(0, (float) $frais->montant - (float) $paye), 2);
    }

    /**
     * Liste des élèves en défaut pour au moins un frais (impayés).
     *
     * @return Collection<int, array{eleve: Eleve, frais: Frais, reste: float}>
     */
    public function elevesEnDefaut(AnneeAcademique|null $annee = null): Collection
    {
        $annee ??= $this->anneeCourante(auth()->user()?->school_id);

        $resultats = collect();
        $fraisActifs = Frais::where('actif', true)->get();

        // Élèves inscrits cette année (avec statut non radie)
        $eleves = Eleve::where('statut', '!=', Eleve::STATUT_RADIE)->get();

        foreach ($eleves as $eleve) {
            foreach ($fraisActifs as $frais) {
                $reste = $this->resteAPayer($eleve, $frais, $annee);
                if ($reste > 0) {
                    $resultats->push([
                        'eleve' => $eleve,
                        'frais' => $frais,
                        'reste' => $reste,
                    ]);
                }
            }
        }

        return $resultats;
    }

    /**
     * Journal de caisse : encaissements + dépenses de l'année, avec solde.
     */
    public function journalCaisse(AnneeAcademique|null $annee = null): array
    {
        $annee ??= $this->anneeCourante(auth()->user()?->school_id);

        $recettes = $annee ? $annee->encaissements()->where('statut', '!=', 'en_attente')->sum('montant') : 0;
        $depenses = $annee ? $annee->depenses()->sum('montant') : 0;

        return [
            'recettes' => round((float) $recettes, 2),
            'depenses' => round((float) $depenses, 2),
            'solde' => round((float) $recettes - (float) $depenses, 2),
        ];
    }

    /**
     * Casier de la paie : total payé et en attente.
     */
    public function situationPaie(AnneeAcademique|null $annee = null): array
    {
        $annee ??= $this->anneeCourante(auth()->user()?->school_id);

        if (! $annee) {
            return ['paye' => 0, 'en_attente' => 0];
        }

        return [
            'paye' => round((float) $annee->salaires()->where('statut', 'paye')->sum('net_a_payer'), 2),
            'en_attente' => round((float) $annee->salaires()->where('statut', 'en_attente')->sum('net_a_payer'), 2),
        ];
    }

    /**
     * Année académique courante (non archivée) d'une école.
     */
    private function anneeCourante(int|null $schoolId): AnneeAcademique|null
    {
        if (! $schoolId) {
            return null;
        }

        return AnneeAcademique::where('school_id', $schoolId)
            ->where('archivee', false)
            ->latest('date_debut')
            ->first();
    }
}