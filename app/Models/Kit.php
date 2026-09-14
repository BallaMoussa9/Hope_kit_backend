<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kit extends Model
{
    use HasFactory;

    protected $fillable = [
        'qr_code', 'batch_number', 'project_id', 'status',
        'current_health_center_id', 'beneficiary_id',
        'received_at', 'distributed_at', 'used_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'distributed_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class, 'current_health_center_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(KitEvent::class);
    }

    /**
     * Délai (en jours) entre la distribution et l'utilisation confirmée —
     * l'un des indicateurs demandés pour le tableau de bord.
     */
    public function getDaysInStockAttribute(): ?int
    {
        if (! $this->distributed_at) {
            return null;
        }

        $end = $this->used_at ?? now();

        return (int) $this->distributed_at->diffInDays($end);
    }
}
