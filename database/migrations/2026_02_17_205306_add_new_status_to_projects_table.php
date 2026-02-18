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
        // Обновляем ENUM для добавления нового статуса 'new'
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('new', 'draft', 'moderation', 'announced', 'in_progress', 'paused', 'completed', 'sold', 'rejected') DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем обратно к старому ENUM без статуса 'new'
        // Сначала обновляем все записи со статусом 'new' на 'draft'
        DB::table('projects')->where('status', 'new')->update(['status' => 'draft']);

        // Затем убираем 'new' из ENUM
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('draft', 'moderation', 'announced', 'in_progress', 'paused', 'completed', 'sold', 'rejected') DEFAULT 'draft'");
    }
};
