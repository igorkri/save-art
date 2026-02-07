<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Спочатку заповнюємо full_name з name для тих, у кого full_name порожній
        $users = DB::table('users')
            ->whereNull('full_name')
            ->orWhere('full_name', '{"uk": "", "en": ""}')
            ->orWhere('full_name', '{"uk": null, "en": null}')
            ->get(['id', 'name']);

        foreach ($users as $user) {
            if ($user->name) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'full_name' => json_encode(['uk' => $user->name, 'en' => $user->name]),
                    ]);
            }
        }

        // Видаляємо поле name
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        // Відновлюємо name з full_name
        $users = DB::table('users')->get(['id', 'full_name']);
        foreach ($users as $user) {
            $fullName = json_decode($user->full_name, true);
            $name = $fullName['uk'] ?? $fullName['en'] ?? 'User';
            DB::table('users')
                ->where('id', $user->id)
                ->update(['name' => $name]);
        }
    }
};
