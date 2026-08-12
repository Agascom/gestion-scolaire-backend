<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Élève du complexe scolaire.
 */
class Eleve extends Model
{
    use HasFactory, HasTenant, SoftDeletes;

    public const STATUT_INSCRIT = 'inscrit';
    public const STATUT_REINSCRIT = 'reinscrit';
    public const STATUT_TRANSFERE = 'transfere';
    public const STATUT_RADIE = 'radie';
    public const STATUT_DIPLOME = 'diplome';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'matricule',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'commune_naissance',
        'nationalite',
        'adresse',
        'photo_path',
        'statut',
    ];

    /**
     * Classe actuelle de l'élève (dernière inscription de l'année en cours).
     */
    public function classeActuelle(): ?Classe
    {
        $annee = AnneeAcademique::where('school_id', $this->school_id)
            ->where('archivee', false)
            ->latest('date_debut')
            ->first();

        if (! $annee) {
            return null;
        }

        return $this->classes()
            ->wherePivot('annee_academique_id', $annee->id)
            ->first();
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Parent / tuteur principal de l'élève (une fiche par élève).
     */
    public function parentEleve(): HasOne
    {
        return $this->hasOne(ParentEleve::class, 'eleve_id');
    }

    /**
     * Classes fréquentées dans le temps (pivot classe_eleve).
     *
     * @return BelongsToMany<Classe>
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'classe_eleve')
            ->using(TableClasse::class)
            ->withPivot(['school_id', 'annee_academique_id'])
            ->whereNull('classes.deleted_at');
    }

    /**
     * @return HasMany<Note>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * @return HasMany<EleveDocument>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EleveDocument::class);
    }

    /**
     * @return HasMany<PasSageClasse>
     */
    public function passages(): HasMany
    {
        return $this->hasMany(PassageClasse::class);
    }

    /**
     * @return HasMany<Bulletin>
     */
    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }

    /**
     * @return HasMany<Absence>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * @return HasMany<Encaissement>
     */
    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
        ];
    }
}