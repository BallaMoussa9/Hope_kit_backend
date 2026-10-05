<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IvrVoiceMessage extends Model
{
    protected $fillable = [
        'title', 'language', 'call_type', 'description', 'audio_path',
        'original_filename', 'mime_type', 'file_size', 'is_active', 'version',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    protected $appends = ['audio_url'];

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path ? asset('storage/' . ltrim($this->audio_path, '/')) : null;
    }
}
