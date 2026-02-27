<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_KEY = 'site_settings_cache';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value (cached)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    /**
     * Get all settings (cached)
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SiteSetting::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Set a single setting value (updates DB + busts cache)
     */
    public function set(string $key, mixed $value): void
    {
        SiteSetting::setValue($key, $value);
        $this->clearCache();
    }

    /**
     * Set multiple settings at once (updates DB + busts cache)
     */
    public function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            SiteSetting::setValue($key, $value);
        }
        $this->clearCache();
    }

    /**
     * Clear the settings cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get watermark-specific settings as a structured array
     */
    public function getWatermarkSettings(): array
    {
        return [
            'enabled' => (bool) $this->get('image_watermark_enabled', false),
            'type' => $this->get('image_watermark_type', 'text'),
            'text' => $this->get('image_watermark_text', ''),
            'image_path' => $this->get('image_watermark_image_path', ''),
            'position' => $this->get('image_watermark_position', 'bottom-right'),
            'opacity' => (int) $this->get('image_watermark_opacity', 50),
            'font_size' => (int) $this->get('image_watermark_font_size', 24),
            'margin' => (int) $this->get('image_watermark_margin', 15),
        ];
    }
}
