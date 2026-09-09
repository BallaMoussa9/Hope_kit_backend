<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public const ROLE_DIRECTION = 'direction';
    public const ROLE_COORDINATEUR = 'coordinateur_projet';
    public const ROLE_LOGISTIQUE = 'agent_logistique';
    public const ROLE_AGENT_SANTE = 'agent_sante';

    protected $fillable = [
        'matricule',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'job_title',
        'health_center_id',
        'project_id',
        'preferred_language',
        'avatar_path',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }

    /** Relation principale utilisée lorsque project_id est renseigné. */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Relation many-to-many pour les coordinateurs pouvant gérer plusieurs projets. */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_user',
            'user_id',
            'project_id'
        );
    }

    public function kitStatusHistories(): HasMany
    {
        return $this->hasMany(KitEvent::class, 'user_id');
    }

    public function registeredBeneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class, 'registered_by');
    }

    public function isDirection(): bool
    {
        return $this->role === self::ROLE_DIRECTION;
    }

    public function isCoordinateur(): bool
    {
        return $this->role === self::ROLE_COORDINATEUR;
    }

    public function isAgent(): bool
    {
        return in_array($this->role, [
            self::ROLE_LOGISTIQUE,
            self::ROLE_AGENT_SANTE,
        ], true);
    }

    public function accessibleProjectIds(): ?array
    {
        if ($this->isDirection()) {
            return null;
        }

        $ids = $this->projects()->pluck('projects.id')->all();

        if ($this->project_id && ! in_array($this->project_id, $ids, true)) {
            $ids[] = $this->project_id;
        }

        return array_values(array_unique($ids));
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }
}
