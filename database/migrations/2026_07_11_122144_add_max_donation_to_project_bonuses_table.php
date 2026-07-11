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
        Schema::table('project_bonuses', function (Blueprint $table) {
            $table->decimal('max_donation', 15, 2)->nullable()->after('min_donation')->comment('Максимальна сума донату для отримання бонусу (null = без обмеження)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_bonuses', function (Blueprint $table) {
            $table->dropColumn('max_donation');
        });
    }
};
