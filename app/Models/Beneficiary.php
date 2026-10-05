<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'phone', 'preferred_language',
        'health_center_id', 'registered_by',
        'expected_delivery_date', 'actual_delivery_date',
        'delivery_location', 'ivr_consent',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'ivr_consent' => 'boolean',
    ];

    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function kits(): HasMany
    {
        return $this->hasMany(Kit::class);
    }

    public function ivrCalls(): HasMany
    {
        return $this->hasMany(IvrCall::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
