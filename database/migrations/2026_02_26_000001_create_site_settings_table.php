<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default watermark settings
        $defaults = [
            'image_watermark_enabled' => '0',
            'image_watermark_type' => 'text',
            'image_watermark_text' => '© منتدى أرثوذكس',
            'image_watermark_image_path' => '',
            'image_watermark_position' => 'bottom-right',
            'image_watermark_opacity' => '50',
            'image_watermark_font_size' => '24',
            'image_watermark_margin' => '15',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('site_settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
