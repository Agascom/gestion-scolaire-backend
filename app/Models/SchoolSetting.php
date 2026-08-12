<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paramètre (clé/valeur) configurable d'une école (TVAs, seuils, coefficients...).
 */
class SchoolSetting extends Model
{
    use HasTenant;

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'cle',
        'valeur',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}