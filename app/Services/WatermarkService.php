<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Typography\FontFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WatermarkService
{
    protected SettingsService $settings;
    protected array $config;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
        $this->config = $settings->getWatermarkSettings();
    }

    /**
     * Apply watermark to an Intervention Image instance.
     * Returns the image (modified or unchanged if watermark is disabled/fails).
     */
    public function apply($image)
    {
        if (!$this->config['enabled']) {
            return $image;
        }

        try {
            $type = $this->config['type'];

            if ($type === 'image' || $type === 'both') {
                $image = $this->applyImageWatermark($image);
            }

            if ($type === 'text' || $type === 'both') {
                $image = $this->applyTextWatermark($image);
            }
        } catch (\Throwable $e) {
            Log::error('Watermark failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            // Return image unchanged — do not crash
        }

        return $image;
    }

    /**
     * Apply text watermark using Cairo font
     */
    protected function applyTextWatermark($image)
    {
        $text = $this->config['text'];
        if (empty($text)) {
            return $image;
        }

        $fontPath = storage_path('app/fonts/Cairo-Bold.ttf');
        if (!file_exists($fontPath)) {
            Log::warning('Watermark font not found: ' . $fontPath);
            return $image;
        }

        $fontSize = $this->config['font_size'];
        $opacity = $this->config['opacity'];
        $margin = $this->config['margin'];
        $position = $this->config['position'];

        // Calculate position coordinates
        $imgWidth = $image->width();
        $imgHeight = $image->height();

        // Estimate text dimensions (approximate)
        $textWidth = mb_strlen($text) * $fontSize * 0.6;
        $textHeight = $fontSize * 1.4;

        [$x, $y, $alignH, $alignV] = $this->calculatePosition(
            $position,
            $imgWidth,
            $imgHeight,
            $textWidth,
            $textHeight,
            $margin
        );

        // Apply text with opacity
        // Intervention Image v3 text method
        $opacityPercent = max(0, min(100, $opacity));

        $image->text($text, $x, $y, function (FontFactory $font) use ($fontPath, $fontSize, $opacityPercent) {
            $font->filename($fontPath);
            $font->size($fontSize);
            $font->color('rgba(255, 255, 255, ' . ($opacityPercent / 100) . ')');
            $font->align('center');
            $font->valign('middle');
        });

        return $image;
    }

    /**
     * Apply image watermark (PNG with transparency)
     */
    protected function applyImageWatermark($image)
    {
        $watermarkPath = $this->config['image_path'];
        if (empty($watermarkPath)) {
            return $image;
        }

        // Resolve the full path
        $fullPath = Storage::disk('public')->path($watermarkPath);
        if (!file_exists($fullPath)) {
            Log::warning('Watermark image not found: ' . $fullPath);
            return $image;
        }

        $manager = new ImageManager(new Driver());
        $watermark = $manager->read($fullPath);

        $opacity = $this->config['opacity'];
        $margin = $this->config['margin'];
        $position = $this->config['position'];

        $imgWidth = $image->width();
        $imgHeight = $image->height();

        // Resize watermark if too large (max 30% of image width)
        $maxWatermarkWidth = (int) ($imgWidth * 0.3);
        if ($watermark->width() > $maxWatermarkWidth) {
            $watermark->scale(width: $maxWatermarkWidth);
        }

        // Apply opacity to watermark
        if ($opacity < 100) {
            // Intervention Image v3: use opacity modifier
            // We reduce the alpha channel on the watermark
            $watermark->reduceColors(256); // Ensure indexed mode for transparency
        }

        // Map position to Intervention's place method position string
        $positionMap = [
            'top-left' => 'top-left',
            'top-center' => 'top',
            'top-right' => 'top-right',
            'center-left' => 'left',
            'center' => 'center',
            'center-right' => 'right',
            'bottom-left' => 'bottom-left',
            'bottom-center' => 'bottom',
            'bottom-right' => 'bottom-right',
        ];

        $placementPosition = $positionMap[$position] ?? 'bottom-right';

        $image->place(
            $watermark,
            $placementPosition,
            $margin,
            $margin,
            $opacity
        );

        return $image;
    }

    /**
     * Calculate x,y coordinates for text based on position setting
     */
    protected function calculatePosition(
        string $position,
        int $imgWidth,
        int $imgHeight,
        float $textWidth,
        float $textHeight,
        int $margin
    ): array {
        $alignH = 'center';
        $alignV = 'middle';

        switch ($position) {
            case 'top-left':
                $x = $margin + (int) ($textWidth / 2);
                $y = $margin + (int) ($textHeight / 2);
                break;
            case 'top-center':
                $x = (int) ($imgWidth / 2);
                $y = $margin + (int) ($textHeight / 2);
                break;
            case 'top-right':
                $x = $imgWidth - $margin - (int) ($textWidth / 2);
                $y = $margin + (int) ($textHeight / 2);
                break;
            case 'center-left':
                $x = $margin + (int) ($textWidth / 2);
                $y = (int) ($imgHeight / 2);
                break;
            case 'center':
                $x = (int) ($imgWidth / 2);
                $y = (int) ($imgHeight / 2);
                break;
            case 'center-right':
                $x = $imgWidth - $margin - (int) ($textWidth / 2);
                $y = (int) ($imgHeight / 2);
                break;
            case 'bottom-left':
                $x = $margin + (int) ($textWidth / 2);
                $y = $imgHeight - $margin - (int) ($textHeight / 2);
                break;
            case 'bottom-center':
                $x = (int) ($imgWidth / 2);
                $y = $imgHeight - $margin - (int) ($textHeight / 2);
                break;
            case 'bottom-right':
            default:
                $x = $imgWidth - $margin - (int) ($textWidth / 2);
                $y = $imgHeight - $margin - (int) ($textHeight / 2);
                break;
        }

        return [$x, $y, $alignH, $alignV];
    }
}
