<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;

class Camera extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'username',
        'password',
        'ip_address',
        'port',
        'stream_path',
        'youtube_url',
        'is_active',
        'is_public',
        'is_streaming_to_youtube',
        'faculty_id',
        'rotation',
    ];

    protected $hidden = [
        'password',
        'username',
        'stream_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_streaming_to_youtube' => 'boolean',
        'rotation' => 'integer',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (! $value) return null;
                try {
                    return Crypt::decryptString($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    return $value; // Graceful fallback for pre-existing plaintext
                }
            },
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function peopleCounts(): HasMany
    {
        return $this->hasMany(PeopleCount::class);
    }

    public function getRtspUrlAttribute(): string
    {
        $auth = '';
        if ($this->username) {
            $auth = $this->username;
            if ($this->password) {
                $auth .= ":{$this->password}";
            }
            $auth .= '@';
        }

        $path = ltrim($this->stream_path, '/');

        return "rtsp://{$auth}{$this->ip_address}:{$this->port}/{$path}";
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('latest_snapshot')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('medium_webp')
            ->format('webp')
            ->fit(Fit::Max, 960, 540)
            ->quality(82)
            ->queued();
    }

    public function latestSnapshotMedia(): ?Media
    {
        return $this->getFirstMedia('latest_snapshot');
    }

    public function latestSnapshotUrl(): ?string
    {
        $media = $this->latestSnapshotMedia();

        if (! $media) {
            return null;
        }

        return $media->hasGeneratedConversion('medium_webp')
            ? $media->getUrl('medium_webp')
            : $media->getUrl();
    }

    public function latestSnapshotTimestamp(): ?int
    {
        $media = $this->latestSnapshotMedia();

        if (! $media) {
            return null;
        }

        $capturedAt = $media->getCustomProperty('captured_at');

        if (is_numeric($capturedAt)) {
            return (int) $capturedAt;
        }

        return $media->updated_at?->timestamp;
    }
}
