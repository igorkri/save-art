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
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name')->comment('URL-адреса профілю митця');
        });

        // Генеруємо slug для існуючих користувачів
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            $user->slug = \Illuminate\Support\Str::slug($user->name).'-'.\Illuminate\Support\Str::random(6);
            $user->saveQuietly();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
    }
};
