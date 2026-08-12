<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Affectation Matière ↔ Classe (+ Enseignant) avec coefficient.
 */
class MatiereClasse extends Model
{
    use HasFactory, HasTenant;

    /**
     * Table pivot nominale (le trait Multi-écoles s'applique via `school_id`).
     *
     * @var string
     */
    protected $table = 'matiere_classe';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'coefficient',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }
}