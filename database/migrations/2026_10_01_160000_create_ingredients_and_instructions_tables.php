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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            // Optional: "salt, to taste" has no quantity.
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 30)->nullable();
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();

            $table->index(['recipe_id', 'sort_order']);
        });

        Schema::create('instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step_number');
            $table->text('instruction');
            $table->timestamps();

            $table->index(['recipe_id', 'step_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructions');
        Schema::dropIfExists('ingredients');
    }
};
