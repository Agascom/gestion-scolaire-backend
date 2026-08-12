<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EleveResource;
use App\Http\Resources\NotificationResource;
use App\Models\Bulletin;
use App\Models\Eleve;
use App\Models\Encaissement;
use App\Models\Frais;
use App\Models\Matiere;
use App\Models\MatiereClasse;
use App\Models\Note;
use App\Models\NotificationApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Espace Parents : élèves du compte, notes, bulletins, situation financière
 * et notifications. Réservé aux comptes portant le rôle `parent`.
 */
class ParentController extends ApiController
{
    use UtiliseAnneeCourante;

    /**
     * Liste des élèves rattachés au compte parent connecté.
     */
    public function eleves(Request $request): JsonResponse
    {
        $eleves = auth()->user()->parentEleves()
            ->with('eleve')
            ->get()
            ->pluck('eleve')
            ->filter()
            ->values();

        return $this->success(EleveResource::collection($eleves), 'Élèves récupérés.');
    }

    /**
     * Notes d'un élève du parent connecté.
     * Forme : [{ id, type, matiere, note, sur, coefficient, appreciation, date }].
     */
    public function notes(Request $request, int $eleve): JsonResponse
    {
        $eleve = $this->eleveDuParentOuAbort($eleve);
        $classe = $eleve->classeActuelle();

        $notes = Note::with('evaluation.matiere')
            ->where('eleve_id', $eleve->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success($notes->map(fn (Note $note) => [
            'id' => $note->id,
            'type' => $note->evaluation?->type,
            'matiere' => $note->evaluation?->matiere?->libelle,
            'note' => (float) $note->note,
            'sur' => (float) ($note->evaluation?->note_sur ?? 0),
            'coefficient' => $this->coefficientMatiere($note->evaluation?->matiere_id, $classe?->id),
            'appreciation' => $note->appreciation,
            'date' => $note->evaluation?->date_evaluation?->format('d/m/Y'),
        ])->values(), 'Notes de l\'élève.');
    }

    /**
     * Bulletins d'un élève du parent connecté.
     */
    public function bulletins(Request $request, int $eleve): JsonResponse
    {
        $eleve = $this->eleveDuParentOuAbort($eleve);

        $bulletins = Bulletin::where('eleve_id', $eleve->id)
            ->with(['trimestre', 'classe'])
            ->orderBy('trimestre_id')
            ->get();

        return $this->success($bulletins->map(fn (Bulletin $bulletin) => [
            'id' => $bulletin->id,
            'trimestre' => $bulletin->trimestre?->numero,
            'trimestre_libelle' => $bulletin->trimestre?->libelle,
            'moyenne_generale' => $bulletin->moyenne_generale,
            'rang' => $bulletin->rang,
            'mention' => $bulletin->mention,
            'appreciation' => $bulletin->appreciation,
            'statut' => $bulletin->statut,
        ])->values(), 'Bulletins récupérés.');
    }

    /**
     * Situation financière d'un élève du parent connecté.
     */
    public function frais(Request $request, int $eleve): JsonResponse
    {
        $eleve = $this->eleveDuParentOuAbort($eleve);
        $annee = $this->anneeCourante();

        $encaissements = Encaissement::with('frais')
            ->where('eleve_id', $eleve->id)
            ->when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id))
            ->orderByDesc('date_encaissement')
            ->get();

        $totalPaye = $encaissements
            ->where('statut', '!=', Encaissement::STATUT_EN_ATTENTE)
            ->sum(fn ($e) => (float) $e->montant);
        $totalFrais = Frais::where('actif', true)->sum('montant');
        $reste = max(0, (float) $totalFrais - (float) $totalPaye);

        return $this->success([
            'total_frais' => round((float) $totalFrais, 2),
            'total_paye' => round($totalPaye, 2),
            'reste' => round($reste, 2),
            'encaissements' => $encaissements->map(fn (Encaissement $e) => [
                'id' => $e->id,
                'libelle_frais' => $e->frais?->libelle,
                'montant' => (float) $e->montant,
                'mode' => $e->mode,
                'reference' => $e->reference,
                'statut' => $e->statut,
                'date_encaissement' => $e->date_encaissement?->format('d/m/Y'),
            ])->values(),
        ], 'Situation financière récupérée.');
    }

    /**
     * Notifications destinées au compte parent connecté.
     */
    public function notifications(Request $request): JsonResponse
    {
        $notifications = NotificationApp::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return $this->success(NotificationResource::collection($notifications), 'Notifications récupérées.');
    }

    /**
     * Marque une notification comme lue.
     */
    public function marquerNotificationLue(int $notification): JsonResponse
    {
        $notification = NotificationApp::where('user_id', auth()->id())->findOrFail($notification);
        $notification->update(['lue' => true, 'lue_le' => now()]);

        return $this->success(new NotificationResource($notification), 'Notification marquée comme lue.');
    }

    /**
     * Coefficient de la matière dans la classe de l'élève, sinon coefficient par défaut.
     */
    private function coefficientMatiere(?int $matiereId, ?int $classeId): float
    {
        if (! $matiereId) {
            return 1;
        }

        $coefficient = MatiereClasse::where('matiere_id', $matiereId)
            ->when($classeId, fn ($q) => $q->where('classe_id', $classeId))
            ->value('coefficient');

        $coefficient ??= Matiere::whereKey($matiereId)->value('coefficient_par_defaut');

        return (float) ($coefficient ?? 1);
    }

    /**
     * Vérifie que l'élève appartient au parent connecté, sinon 403.
     */
    private function eleveDuParentOuAbort(int $eleveId): Eleve
    {
        $appartient = auth()->user()->parentEleves()
            ->where('eleve_id', $eleveId)
            ->exists();

        abort_unless($appartient, 403, 'Ce compte n\'est pas autorisé à consulter cet élève.');

        return Eleve::findOrFail($eleveId);
    }
}
