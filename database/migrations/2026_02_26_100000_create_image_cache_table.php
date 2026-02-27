<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('image_cache', function (Blueprint $table) {
            $table->id();
            $table->string('url_hash', 64)->unique()->comment('SHA-256 hash of original URL');
            $table->text('original_url');
            $table->enum('status', ['valid', 'broken', 'pending'])->default('pending')->index();
            $table->smallInteger('response_code')->nullable();
            $table->string('content_type', 100)->nullable();
            $table->unsignedInteger('content_length')->nullable();
            $table->timestamp('last_checked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'last_checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_cache');
    }
};
