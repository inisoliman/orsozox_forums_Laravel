<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageCache extends Model
{
    protected $table = 'image_cache';

    protected $fillable = [
        'url_hash',
        'original_url',
        'status',
        'response_code',
        'content_type',
        'content_length',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'response_code' => 'integer',
        'content_length' => 'integer',
    ];

    /* ---------- Scopes ---------- */

    public function scopeBroken($query)
    {
        return $query->where('status', 'broken');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Records whose TTL (24h) has expired and need re-checking.
     */
    public function scopeStale($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_checked_at')
                ->orWhere('last_checked_at', '<', now()->subHours(24));
        });
    }

    /* ---------- Helpers ---------- */

    public static function hashUrl(string $url): string
    {
        return hash('sha256', $url);
    }

    /**
     * Find by URL (uses hash for fast lookup).
     */
    public static function findByUrl(string $url): ?self
    {
        return static::where('url_hash', static::hashUrl($url))->first();
    }

    /**
     * Check if this record is still fresh (within TTL).
     */
    public function isFresh(): bool
    {
        return $this->last_checked_at && $this->last_checked_at->gt(now()->subHours(24));
    }
}
