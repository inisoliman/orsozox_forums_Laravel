<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ThemeSettings
{
    protected string $path = 'settings.json';
    protected array $settings = [];

    public function __construct()
    {
        $this->load();
    }

    protected function load()
    {
        if (Storage::exists($this->path)) {
            $this->settings = json_decode(Storage::get($this->path), true) ?? [];
        }
    }

    public function get($key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function set($key, $value)
    {
        data_set($this->settings, $key, $value);
        return $this;
    }

    public function all()
    {
        return $this->settings;
    }

    public function setAll(array $data)
    {
        $this->settings = $data;
        return $this;
    }

    public function save()
    {
        Storage::put($this->path, json_encode($this->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // Clear global cache if you use it for settings
        Cache::forget('theme_settings');
    }

    public function shouldShowAds($currentForumId = null): bool
    {
        // Global switch
        if (!$this->get('ads.enabled', true)) {
            return false;
        }

        // Check exclusions if we are in a forum context
        if ($currentForumId) {
            $excludedIds = $this->getExcludedForumIds();
            if (in_array($currentForumId, $excludedIds)) {
                return false;
            }
        }

        return true;
    }

    public function getExcludedForumIds(): array
    {
        $raw = $this->get('ads.excluded_forums', '');
        if (empty($raw)) {
            return [];
        }

        // Split by comma, trim whitespace, and filter empty values
        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
