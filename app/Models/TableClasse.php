<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot Élève ↔ Classe pour une année académique (historique de parcours).
 */
class TableClasse extends Model
{
    /**
     * Table pivot `classe_eleve`.
     *
     * @var string
     */
    protected $table = 'classe_eleve';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'classe_id',
        'eleve_id',
        'annee_academique_id',
    ];

    /**
     * Indique que le modèle ne gère pas les timestamps automatiques.
     *
     * @var bool
     */
    public $timestamps = true;

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }
}