<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\MatiereClasse;
use Illuminate\Support\Collection;

/**
 * Calcul des moyennes (par matière, générale) selon le système gabonais /20.
 *
 * Moyenne d'une matière = Σ(note / note_sur) * 20 pondéré par trimestre...
 * Ici : moyenne = moyenne arithmétique simple des notes toutes ramenées sur /20,
 * puis moyenne générale = Σ(moyenne_matiere × coefficient) / Σ(coefficients).
 */
class CalculMoyennesService
{
    /**
     * Ramène une note sur 20 (si évaluation sur une autre base).
     */
    public function ramenerSur20(float $note, float $noteSur): float
    {
        if ($noteSur <= 0) {
            return 0;
        }

        return round(($note / $noteSur) * 20, 2);
    }

    /**
     * Moyenne sur 20 d'un élève pour une matière sur une période donnée.
     *
     * @param  int  $matiere_id  identifiant de la matière
     * @param  int|null  $trimestre_id  null = toutes les évaluations de l'année
     */
    public function moyenneMatiere(int $eleveId, int $matiereId, int $trimestreId = null): float
    {
        $notes = $this->notesMatiere($eleveId, $matiereId, $trimestreId);
        if ($notes->isEmpty()) {
            return 0;
        }

        $somme = $notes->reduce(function (float $carry, array $n) {
            return $carry + $this->ramenerSur20((float) $n['note'], (float) $n['note_sur']);
        }, 0.0);

        return round($somme / $notes->count(), 2);
    }

    /**
     * Notes (ramenées /20) d'un élève pour une matière et un trimestre.
     *
     * @return Collection<int, array{note: mixed, note_sur: mixed}>
     */
    public function notesMatiere(int $eleveId, int $matiereId, int $trimestreId = null): Collection
    {
        $query = Evaluation::where('matiere_id', $matiereId)
            ->whereNull('deleted_at')
            ->whereHas('notes', fn ($q) => $q->where('eleve_id', $eleveId));

        if ($trimestreId) {
            $query->where('trimestre_id', $trimestreId);
        }

        $notes = collect();
        $query->get()->each(function (Evaluation $e) use (&$notes, $eleveId) {
            $note = $e->notes()->where('eleve_id', $eleveId)->first();
            if ($note) {
                $notes->push(['note' => $note->note, 'note_sur' => $e->note_sur]);
            }
        });

        return $notes;
    }

    /**
     * Moyenne générale sur 20 d'un élève dans une classe (pondérée par les coefficients).
     */
    public function moyenneGenerale(int $eleveId, int $classeId, int $trimestreId = null): float
    {
        $matieresCoeffs = MatiereClasse::where('classe_id', $classeId)->where('coefficient', '>', 0)->get();

        if ($matieresCoeffs->isEmpty()) {
            return 0;
        }

        $sommePonderee = 0;
        $sommeCoeffs = 0;

        foreach ($matieresCoeffs as $mc) {
            $moyenne = $this->moyenneMatiere($eleveId, $mc->matiere_id, $trimestreId);
            if ($moyenne > 0) {
                $sommePonderee += $moyenne * (float) $mc->coefficient;
                $sommeCoeffs += (float) $mc->coefficient;
            }
        }

        if ($sommeCoeffs <= 0) {
            return 0;
        }

        return round($sommePonderee / $sommeCoeffs, 2);
    }

    /**
     * Classement (rangs) des élèves d'une classe par moyenne générale décroissante.
     *
     * @return Collection<int, array{eleve_id: int, moyenne: float, rang: int}>
     */
    public function classementClasse(int $classeId, int $trimestreId = null): Collection
    {
        $moyennes = Classe::find($classeId)?->eleves()
            ->get()
            ->map(fn (Eleve $e) => [
                'eleve_id' => $e->id,
                'moyenne' => $this->moyenneGenerale($e->id, $classeId, $trimestreId),
            ])
            ->sortByDesc('moyenne')
            ->values();

        $rang = 0;
        $precedente = null;
        $classement = $moyennes->map(function (array $row) use (&$rang, &$precedente) {
            if ($precedente !== null && $row['moyenne'] < $precedente) {
                $rang++;
            }
            $precedente = $row['moyenne'];

            return [...$row, 'rang' => $rang + 1];
        });

        return $classement;
    }

    /**
     * Mention gabonaise selon la moyenne générale.
     */
    public function mention(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }
}