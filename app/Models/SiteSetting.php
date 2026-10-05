<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * قائمة IDs الأقسام المُستثناة من خرائط الموقع (sitemap).
     *
     * تُخزَّن في مفتاح sitemap.excluded_forums كـ JSON array. الحماية: لو
     * كان الجدول أو المفتاح غير موجود، تُعاد قائمة فارغة (لا استثناء).
     *
     * @return array<int, int>
     */
    public static function excludedSitemapForumIds(): array
    {
        try {
            $raw = static::getValue('sitemap.excluded_forums', '[]');
        } catch (\Throwable $e) {
            return [];
        }

        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = json_decode((string) $raw, true);
        }

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $decoded))));
    }

    /**
     * حفظ قائمة الأقسام المُستثناة من sitemap.
     *
     * @param array<int, int|string> $forumIds
     */
    public static function setExcludedSitemapForumIds(array $forumIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $forumIds))));
        static::setValue('sitemap.excluded_forums', json_encode($ids, JSON_UNESCAPED_UNICODE));
    }
}
