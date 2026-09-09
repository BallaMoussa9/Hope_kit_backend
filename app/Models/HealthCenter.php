<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id', 'name', 'code', 'type',
        'latitude', 'longitude', 'phone', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function projects(): BelongsToMany
{
    return $this->belongsToMany(
        Project::class,
        'project_health_center',
        'health_center_id',
        'project_id'
    );
}

    public function kits(): HasMany
    {
        return $this->hasMany(Kit::class, 'current_health_center_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
