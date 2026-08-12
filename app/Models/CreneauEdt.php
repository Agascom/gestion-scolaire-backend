<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Créneau de l'emploi du temps (classe, enseignant, matière, salle, jour, heure).
 */
class CreneauEdt extends Model
{
    use HasTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'classe_id',
        'enseignant_id',
        'matiere_id',
        'salle_id',
        'jour',
        'heure_debut',
        'heure_fin',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }
}