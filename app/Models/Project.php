<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'partner', 'description', 'started_at', 'ended_at', 'is_active',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
        'is_active' => 'boolean',
    ];
public function healthCenters(): BelongsToMany
{
    return $this->belongsToMany(
        HealthCenter::class,
        'project_health_center',
        'project_id',
        'health_center_id'
    );
}

    public function kits(): HasMany
    {
        return $this->hasMany(Kit::class);
    }
}
