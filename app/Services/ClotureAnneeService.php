<?php

namespace App\Services;

use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Niveau;
use App\Models\PassageClasse;
use App\Models\Trimestre;
use Illuminate\Support\Facades\DB;

/**
 * Clôture d'une année académique et passage automatique en classe supérieure.
 */
class ClotureAnneeService
{
    public function __construct(
        private readonly CalculMoyennesService $moyennes,
    ) {
    }

    /**
     * Clôture une année : pour chaque élève encore inscrit, décide du passage
     * (admis si moyenne >= seuil configuré, redoublant sinon) et enregistre
     * la décision + l'inscription dans la classe supérieure de la nouvelle année.
     *
     * @param  float  $seuilAdmission  note /20 sous laquelle l'élève redouble
     */
    public function cloturer(AnneeAcademique $annee, float $seuilAdmission = 10.0): array
    {
        $rapport = [
            'admis' => 0,
            'redoublants' => 0,
            'erreurs' => [],
        ];

        // Nouvelle année (année suivante) : libellé calculé à partir de l'année clôturée.
        $nouvelleAnnee = $this->creerAnneeSuivante($annee);

        // Dernier trimestre de l'année à clôturer (moyenne annuelle)
        $dernierTrimestre = $annee->trimestres()->orderByDesc('numero')->value('id');

        // Classes de l'année clôturée
        $classes = $annee->classes()->get();

        DB::transaction(function () use ($annee, $nouvelleAnnee, $dernierTrimestre, $classes, $seuilAdmission, &$rapport) {
            foreach ($classes as $classe) {
                foreach ($classe->eleves()->get() as $eleve) {
                    $moyenne = $this->moyennes->moyenneGenerale($eleve->id, $classe->id, $dernierTrimestre);

                    // Classe supérieure dans la nouvelle année
                    $classeCible = $this->classeSuperieure($classe, $nouvelleAnnee);

                    $decision = $moyenne >= $seuilAdmission
                        ? PassageClasse::DECISION_ADMIS
                        : PassageClasse::DECISION_REDOUBLANT;

                    PassageClasse::create([
                        'school_id' => $annee->school_id,
                        'eleve_id' => $eleve->id,
                        'annee_academique_id' => $annee->id,
                        'classe_source_id' => $classe->id,
                        'classe_cible_id' => $decision === PassageClasse::DECISION_ADMIS && $classeCible ? $classeCible->id : null,
                        'moyenne_generale' => $moyenne,
                        'decision' => $decision,
                        'decide_par' => auth()->id(),
                    ]);

                    // Si admis et classe cible trouvée : nouvelle inscription.
                    if ($decision === PassageClasse::DECISION_ADMIS && $classeCible) {
                        $classeCible->eleves()->syncWithoutDetaching([
                            $eleve->id => [
                                'school_id' => $annee->school_id,
                                'annee_academique_id' => $nouvelleAnnee->id,
                            ],
                        ]);
                        $rapport['admis']++;
                    } else {
                        $rapport['redoublants']++;
                    }
                }
            }
        });

        $annee->update(['cloturee' => true, 'archivee' => true]);

        return $rapport;
    }

    /**
     * Crée (ou récupère) l'année académique suivante.
     */
    private function creerAnneeSuivante(AnneeAcademique $annee): AnneeAcademique
    {
        $anneeSuivante = (int) substr($annee->libelle, 0, 4) + 1;
        $libelle = $anneeSuivante.'-'.($anneeSuivante + 1);

        return AnneeAcademique::firstOrCreate(
            ['school_id' => $annee->school_id, 'libelle' => $libelle],
            [
                'date_debut' => $annee->date_fin?->copy()->addMonths(3)?->startOfYear()->setMonth(9)->setDay(1),
                'date_fin' => $annee->date_fin?->copy()->addMonths(15)?->endOfYear(),
            ]
        );
    }

    /**
     * Classe de niveau supérieur équivalent dans la nouvelle année.
     */
    private function classeSuperieure(Classe $classe, AnneeAcademique $nouvelleAnnee): ?Classe
    {
        $niveauSuperieur = Niveau::where('cycle_id', $classe->niveau?->cycle_id)
            ->where('ordre', '>', $classe->niveau?->ordre ?? 99)
            ->orderBy('ordre')
            ->first();

        if (! $niveauSuperieur) {
            // dernier niveau du cycle (ex : Terminale) : diplômé, pas de classe cible.
            return null;
        }

        return Classe::firstOrCreate(
            [
                'school_id' => $classe->school_id,
                'annee_academique_id' => $nouvelleAnnee->id,
                'niveau_id' => $niveauSuperieur->id,
                'section' => $classe->section,
            ],
            ['libelle' => $niveauSuperieur->libelle.($classe->section ? ' '.$classe->section : '')]
        );
    }
}