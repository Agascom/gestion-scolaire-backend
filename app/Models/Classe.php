<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Classe (ex : 6eme A) liée à un niveau, une année académique et une école.
 */
class Classe extends Model
{
    use HasFactory, HasTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'annee_academique_id',
        'niveau_id',
        'section',
        'libelle',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    /**
     * @return HasMany<MatiereClasse>
     */
    public function matiereClasses(): HasMany
    {
        return $this->hasMany(MatiereClasse::class);
    }

    /**
     * Matières affectées à la classe (pivot matiere_classe).
     *
     * @return BelongsToMany<Matiere>
     */
    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(Matiere::class, 'matiere_classe')
            ->withPivot(['school_id', 'enseignant_id', 'coefficient']);
    }

    /**
     * Élèves inscrits dans la classe (pivot classe_eleve).
     *
     * @return BelongsToMany<Eleve>
     */
    public function eleves(): BelongsToMany
    {
        return $this->belongsToMany(Eleve::class, 'classe_eleve')
            ->withPivot(['school_id', 'annee_academique_id'])
            ->whereNull('eleves.deleted_at');
    }

    /**
     * @return HasMany<Evaluation>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}