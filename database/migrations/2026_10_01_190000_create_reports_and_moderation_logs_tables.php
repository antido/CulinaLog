<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // The member who reported. Empty for guests. Kept (as empty) if the member leaves.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // For guests, a hash of their cookie token, used only to stop repeat reports.
            $table->char('guest_token_hash', 64)->nullable();
            // What was reported: a recipe, comment, forum topic or forum reply.
            $table->morphs('reportable');
            $table->string('reason', 20);
            $table->text('description')->nullable();
            $table->string('status', 10)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // A record of what moderators did, shown on the moderator dashboard.
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40);
            $table->morphs('subject');
            // A short copy of the title or text, so the entry still reads
            // sensibly after the content itself is deleted.
            $table->string('subject_label', 160);
            $table->timestamps();
        });

        // Laravel's standard table for database notifications.
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('moderation_logs');
        Schema::dropIfExists('reports');
    }
};
