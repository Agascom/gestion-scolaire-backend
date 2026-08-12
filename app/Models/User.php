<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'school_id',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * École de rattachement (design multi-écoles : school_id sur User).
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Alias pratique pour les endpoints de type "utilisateur + école".
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * @return HasMany<Ecole>
     */
    public function ecoles(): HasMany
    {
        return $this->hasMany(School::class, 'id', 'school_id');
    }

    /**
     * Fiches parents rattachées à ce compte (un parent peut avoir plusieurs enfants).
     *
     * @return HasMany<ParentEleve>
     */
    public function parentEleves(): HasMany
    {
        return $this->hasMany(ParentEleve::class, 'user_id');
    }

    /**
     * Profil enseignant rattaché à ce compte.
     *
     * @return HasOne<Enseignant>
     */
    public function profilEnseignant(): HasOne
    {
        return $this->hasOne(Enseignant::class, 'user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}