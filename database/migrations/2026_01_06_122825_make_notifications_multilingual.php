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
        Schema::table('app_notifications', function (Blueprint $table) {
            // Спочатку конвертуємо існуючі дані в JSON формат
        });

        // Конвертуємо існуючі title в JSON
        DB::table('app_notifications')->orderBy('id')->chunk(100, function ($notifications) {
            foreach ($notifications as $notification) {
                $title = $notification->title;
                $message = $notification->message;

                // Якщо вже JSON - пропускаємо
                if (is_string($title) && ! str_starts_with($title, '{')) {
                    DB::table('app_notifications')
                        ->where('id', $notification->id)
                        ->update([
                            'title' => json_encode(['uk' => $title, 'en' => $title]),
                            'message' => $message ? json_encode(['uk' => $message, 'en' => $message]) : null,
                        ]);
                }
            }
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            // Змінюємо тип колонок на JSON
            $table->json('title')->change();
            $table->json('message')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->string('title')->change();
            $table->text('message')->nullable()->change();
        });
    }
};
