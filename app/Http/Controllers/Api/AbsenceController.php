<?php

namespace App\Http\Controllers\Api;

use App\Models\Absence;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Module Absences : élèves et enseignants.
 */
class AbsenceController extends ApiController
{
    use UtiliseAnneeCourante;

    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $absences = Absence::with(['eleve', 'enseignant', 'classe'])
            ->when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id))
            ->when($request->integer('eleve_id'), fn ($q, $v) => $q->where('eleve_id', $v))
            ->when($request->integer('enseignant_id'), fn ($q, $v) => $q->where('enseignant_id', $v))
            ->when($request->integer('classe_id'), fn ($q, $v) => $q->where('classe_id', $v))
            ->orderByDesc('date_absence')
            ->paginate($request->integer('per_page', 15));

        return $this->success($absences, 'Absences récupérées.');
    }

    public function store(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $data = $request->validate([
            'eleve_id' => ['nullable', 'required_without:enseignant_id', 'exists:eleves,id'],
            'enseignant_id' => ['nullable', 'required_without:eleve_id', 'exists:enseignants,id'],
            'classe_id' => ['sometimes', 'exists:classes,id'],
            'date_absence' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
            'justifiee' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $absence = Absence::create([
            'school_id' => $this->schoolId(),
            'annee_academique_id' => $annee?->id,
            'justifiee' => $data['justifiee'] ?? false,
            ...$data,
        ]);

        $this->audit->log('absences', 'creation', 'Absence enregistrée');

        return $this->success($absence->load('eleve', 'enseignant'), 'Absence enregistrée.', 201);
    }
}