<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid', 'kit_id', 'user_id', 'health_center_id', 'beneficiary_id',
        'event_type', 'payload', 'occurred_at', 'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
