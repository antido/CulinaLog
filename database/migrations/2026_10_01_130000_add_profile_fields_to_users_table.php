<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('bio', 500)->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('bio');
        });

        // Give accounts created before this migration a username based on their name.
        DB::table('users')->whereNull('username')->orderBy('id')->each(function (object $user): void {
            $base = Str::limit(Str::slug($user->name, '_'), 20, '') ?: 'cook';

            DB::table('users')->where('id', $user->id)->update([
                'username' => "{$base}_{$user->id}",
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'bio', 'avatar_path']);
        });
    }
};
