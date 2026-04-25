<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Email Subscribers Table
        Schema::create('email_subscribers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->index(); // Can be nullable if we allow external subscribers, but linked to users for now
            $table->string('email')->unique();
            $table->enum('email_status', [
                'pending',
                'valid',
                'invalid_format',
                'no_mx',
                'disposable',
                'risky',
                'bounced',
                'unsubscribed',
                'unknown'
            ])->default('pending')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false); // Legacy user verify status
            $table->timestamp('last_validation_at')->nullable();
            $table->integer('validation_score')->default(0); // 0-100
            $table->integer('bounce_count')->default(0);
            $table->integer('send_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        // 2. Email Campaigns Table
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('content_html')->nullable();
            $table->enum('status', [
                'draft',
                'scheduled',
                'sending',
                'paused',
                'completed',
                'failed'
            ])->default('draft')->index();
            $table->string('target_segment')->default('all'); // all, active, birthday, etc.
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('bounce_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        // 3. Email Logs Table
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->onDelete('cascade');
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->onDelete('cascade');
            $table->enum('status', ['sent', 'bounced', 'failed'])->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('email_subscribers');
    }
};
