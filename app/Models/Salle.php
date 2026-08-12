<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salle physique d'une école.
 */
class Salle extends Model
{
    use HasFactory, HasTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'libelle',
        'capacite',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}