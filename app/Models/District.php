<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['region_id', 'name', 'code'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function healthCenters(): HasMany
    {
        return $this->hasMany(HealthCenter::class);
    }
}
