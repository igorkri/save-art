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
        Schema::dropIfExists('donation_chart_data');

        if (Schema::hasColumn('home_pages', 'chart_auto_collect')) {
            Schema::table('home_pages', function (Blueprint $table) {
                $table->dropColumn('chart_auto_collect');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('donation_chart_data', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 20)->index();
            $table->decimal('total', 15, 2)->default(0);
            $table->json('labels');
            $table->json('values');
            $table->timestamp('data_collected_at')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
            $table->unique('period_type');
        });

        if (! Schema::hasColumn('home_pages', 'chart_auto_collect')) {
            Schema::table('home_pages', function (Blueprint $table) {
                $table->boolean('chart_auto_collect')->default(true)->after('is_active');
            });
        }
    }
};
