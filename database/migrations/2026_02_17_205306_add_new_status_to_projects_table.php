<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL-специфічний ENUM існує лише на production/dev-з'єднанні (mysql).
        // Колонка створена як звичайний string (create_projects_table), тому на
        // sqlite (тести, in-memory) вона й так приймає будь-яке значення — ALTER не потрібен.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Обновляем ENUM для добавления нового статуса 'new'
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('new', 'draft', 'moderation', 'announced', 'in_progress', 'paused', 'completed', 'sold', 'rejected') DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Сначала обновляем все записи со статусом 'new' на 'draft'
        DB::table('projects')->where('status', 'new')->update(['status' => 'draft']);

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Затем убираем 'new' из ENUM
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('draft', 'moderation', 'announced', 'in_progress', 'paused', 'completed', 'sold', 'rejected') DEFAULT 'draft'");
    }
};
