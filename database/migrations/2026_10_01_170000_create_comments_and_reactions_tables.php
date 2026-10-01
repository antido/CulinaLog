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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            // Empty for guests.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Set when the comment is a reply. Deleting a comment deletes its replies.
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            // 0 for a top-level comment, 1 for a reply, 2 for a reply to a reply.
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('guest_name', 40)->nullable();
            // A SHA-256 hash of the guest's cookie token. Never the token itself,
            // and never an IP address.
            $table->char('guest_token_hash', 64)->nullable();
            $table->text('body');
            $table->string('status', 10)->default('visible');
            $table->timestamps();

            $table->index(['recipe_id', 'status', 'parent_id']);
            $table->index('status');
        });

        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('guest_token_hash', 64)->nullable();
            $table->string('type', 10);
            $table->timestamps();

            // One reaction per person per recipe, enforced by the database.
            $table->unique(['recipe_id', 'user_id']);
            $table->unique(['recipe_id', 'guest_token_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
        Schema::dropIfExists('comments');
    }
};
