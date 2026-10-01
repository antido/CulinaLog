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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            // Deleting a user deletes their recipes.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // A category that still has recipes cannot be deleted.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->string('slug', 100)->unique();
            $table->text('description');
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('prep_time')->comment('minutes');
            $table->unsignedSmallInteger('cook_time')->comment('minutes');
            $table->unsignedSmallInteger('servings');
            $table->string('difficulty', 10);
            $table->string('status', 10)->default('draft');
            // Set by moderators. A hidden recipe is not public whatever its status.
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // The public recipe list filters on these and sorts by publish date.
            $table->index(['status', 'is_hidden', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
