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
        Schema::create('donation_chart_data', function (Blueprint $table) {
            $table->id();

            // Тип періоду: day, week, month, year, all
            $table->string('period_type', 20)->index();

            // Загальна сума за період
            $table->decimal('total', 15, 2)->default(0);

            // Labels для графіка (JSON масив)
            $table->json('labels');

            // Values для графіка (JSON масив)
            $table->json('values');

            // Дата/час оновлення даних
            $table->timestamp('data_collected_at')->nullable();

            // Чи введено вручну (false = зібрано кроном)
            $table->boolean('is_manual')->default(false);

            $table->timestamps();

            // Унікальний індекс на тип періоду (тільки один запис на період)
            $table->unique('period_type');
        });

        // Додаємо булевий флаг в home_pages для контролю автозбору
        if (! Schema::hasColumn('home_pages', 'chart_auto_collect')) {
            Schema::table('home_pages', function (Blueprint $table) {
                $table->boolean('chart_auto_collect')->default(true)->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_chart_data');

        if (Schema::hasColumn('home_pages', 'chart_auto_collect')) {
            Schema::table('home_pages', function (Blueprint $table) {
                $table->dropColumn('chart_auto_collect');
            });
        }
    }
};
