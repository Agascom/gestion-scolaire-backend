<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Trimestre;

/**
 * Génération des bulletins scolaires.
 */
class BulletinService
{
    public function __construct(
        private readonly CalculMoyennesService $moyennes,
    ) {
    }

    /**
     * Génère (ou met à jour) un bulletin pour un élève sur un trimestre.
     */
    public function generer(Eleve $eleve, Classe $classe, Trimestre $trimestre): Bulletin
    {
        $moyenne = $this->moyennes->moyenneGenerale($eleve->id, $classe->id, $trimestre->id);

        $classement = $this->moyennes->classementClasse($classe->id, $trimestre->id)
            ->first(fn ($row) => $row['eleve_id'] === $eleve->id);

        return Bulletin::updateOrCreate(
            [
                'school_id' => $classe->school_id,
                'annee_academique_id' => $trimestre->annee_academique_id,
                'trimestre_id' => $trimestre->id,
                'eleve_id' => $eleve->id,
                'classe_id' => $classe->id,
            ],
            [
                'moyenne_generale' => $moyenne,
                'rang' => $classement['rang'] ?? null,
                'mention' => $this->moyennes->mention($moyenne),
                'appreciation' => $this->appreciation($moyenne),
            ]
        );
    }

    /**
     * Tous les bulletins d'une classe pour un trimestre.
     *
     * @return array<int, Bulletin>
     */
    public function genererClasse(Classe $classe, Trimestre $trimestre): array
    {
        $bulletins = [];
        foreach ($classe->eleves()->get() as $eleve) {
            $bulletins[] = $this->generer($eleve, $classe, $trimestre);
        }

        return $bulletins;
    }

    /**
     * Appréciation automatique d'après la moyenne.
     */
    private function appreciation(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => "Excellent trimestre, félicitations.",
            $moyenne >= 14 => 'Très bon travail, continuez ainsi.',
            $moyenne >= 12 => 'Bon résultat, des efforts réguliers.',
            $moyenne >= 10 => 'Résultats corrects, des progrès à faire.',
            default => 'Travail insuffisant, un sérieux rattrapage est nécessaire.',
        };
    }
}