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
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('forum_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // A forum category that still has topics cannot be deleted.
            $table->foreignId('category_id')->constrained('forum_categories')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('slug', 100)->unique();
            $table->text('body');
            $table->string('status', 10)->default('visible');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('views')->default(0);
            // When the topic was created or last got a visible reply. Lists sort by this.
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status', 'is_pinned', 'last_activity_at'], 'forum_topics_listing_index');
            $table->index(['status', 'last_activity_at']);
        });

        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('forum_topics')->cascadeOnDelete();
            // Empty for guests.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('forum_replies')->cascadeOnDelete();
            $table->unsignedTinyInteger('depth')->default(0);
            // A SHA-256 hash of the guest's cookie token, as for comments.
            $table->char('guest_token_hash', 64)->nullable();
            $table->text('body');
            $table->string('status', 10)->default('visible');
            $table->timestamps();

            $table->index(['topic_id', 'status', 'parent_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_topics');
        Schema::dropIfExists('forum_categories');
    }
};
