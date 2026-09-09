<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'severity', 'health_center_id', 'kit_id', 'message',
        'status', 'detected_at', 'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
