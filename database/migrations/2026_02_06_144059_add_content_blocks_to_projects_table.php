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
        if (Schema::hasColumn('projects', 'content_blocks')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->json('content_blocks')
                ->nullable()
                ->after('additional_info')
                ->comment('Динамічні контент-блоки проєкту (heading, paragraph, image)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('content_blocks');
        });
    }
};
