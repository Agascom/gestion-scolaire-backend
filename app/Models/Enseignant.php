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
 * Enseignant d'une école.
 */
class Enseignant extends Model
{
    use HasFactory, HasTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'user_id',
        'nom',
        'prenom',
        'telephone',
        'email',
        'diplome',
        'specialite',
        'statut',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Affectations matières (pivot matiere_classe).
     *
     * @return HasMany<MatiereClasse>
     */
    public function matiereClasses(): HasMany
    {
        return $this->hasMany(MatiereClasse::class);
    }

    /**
     * Classes dans lesquelles l'enseignant intervient.
     *
     * @return BelongsToMany<Classe>
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'matiere_classe')
            ->withPivot(['school_id', 'matiere_id', 'coefficient']);
    }

    /**
     * @return HasMany<Salaire>
     */
    public function salaires(): HasMany
    {
        return $this->hasMany(Salaire::class);
    }

    /**
     * @return HasMany<Absence>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * @return HasMany<CreneauEdt>
     */
    public function creneauxEdt(): HasMany
    {
        return $this->hasMany(CreneauEdt::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }
}