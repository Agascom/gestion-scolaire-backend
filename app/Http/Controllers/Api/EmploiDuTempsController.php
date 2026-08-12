<?php

namespace App\Http\Controllers\Api;

use App\Models\CreneauEdt;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Module Emploi du temps : création par classe/enseignant avec contrôle
 * anti-conflit (un enseignant, une salle ou une classe ne peut être doublé).
 */
class EmploiDuTempsController extends ApiController
{
    use UtiliseAnneeCourante;

    public const JOURS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Créneau de cours pour une classe (avec matière, enseignant, salle).
     */
    public function creerCreneau(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'enseignant_id' => ['nullable', 'exists:enseignants,id'],
            'matiere_id' => ['nullable', 'exists:matieres,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
            'jour' => ['required', 'in:'.implode(',', self::JOURS)],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
        ]);

        $this->verifierAntiConflit($data);

        $creneau = CreneauEdt::create([...$data, 'school_id' => $this->schoolId()]);
        $this->audit->log('emploi_du_temps', 'creation', "Créneau {$data['jour']} {$data['heure_debut']}-{$data['heure_fin']}");

        return $this->success($creneau->load('classe', 'matiere', 'enseignant', 'salle'), 'Créneau créé.', 201);
    }

    /**
     * Emploi du temps d'une classe (ou d'un enseignant si enseignant_id fourni).
     */
    public function consulter(Request $request): JsonResponse
    {
        $creneaux = CreneauEdt::with(['classe', 'matiere', 'enseignant', 'salle'])
            ->when($request->integer('classe_id'), fn ($q, $v) => $q->where('classe_id', $v))
            ->when($request->integer('enseignant_id'), fn ($q, $v) => $q->where('enseignant_id', $v))
            ->orderBy('jour')
            ->get()
            ->groupBy('jour');

        return $this->success($creneaux, 'Emploi du temps.');
    }

    public function supprimer(int $id): JsonResponse
    {
        $creneau = CreneauEdt::findOrFail($id);
        $creneau->delete();
        $this->audit->log('emploi_du_temps', 'suppression', "Suppression du créneau #{$id}");

        return $this->success(null, 'Créneau supprimé.');
    }

    /**
     * Anti-conflit : un enseignant, une salle ou une classe ne peuvent pas
     * être affectés à deux créneaux qui se chevauchent.
     */
    private function verifierAntiConflit(array $data): void
    {
        $conflit = CreneauEdt::where('jour', $data['jour'])
            ->where(fn ($q) => $q->where(function ($w) use ($data) {
                // Chevauchement horaire : début < fin_existant ET fin > début_existant
                $w->where('heure_debut', '<', $data['heure_fin'])
                    ->where('heure_fin', '>', $data['heure_debut']);
            }))
            ->where(function ($q) use ($data) {
                $q->orWhere('classe_id', $data['classe_id'])
                    ->when($data['enseignant_id'] ?? null, fn ($w) => $w->orWhere('enseignant_id', $data['enseignant_id']))
                    ->when($data['salle_id'] ?? null, fn ($w) => $w->orWhere('salle_id', $data['salle_id']));
            })
            ->exists();

        if ($conflit) {
            abort(422, 'Conflit d\'emploi du temps : un même enseignant, salle ou classe est déjà occupé sur cette plage.');
        }
    }
}