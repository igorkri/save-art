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
        Schema::table('reports', function (Blueprint $table) {
            $table->string('title_uk')->nullable()->after('title');
            $table->text('description_uk')->nullable()->after('description');
        });

        DB::table('reports')->update([
            'title_uk' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk'))"),
        ]);

        DB::table('reports')
            ->whereNotNull('description')
            ->update([
                'description_uk' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.uk'))"),
            ]);

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->renameColumn('title_uk', 'title');
            $table->renameColumn('description_uk', 'description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->json('title_json')->nullable()->after('title');
            $table->json('description_json')->nullable()->after('description');
        });

        DB::table('reports')->orderBy('id')->chunk(100, function ($reports) {
            foreach ($reports as $report) {
                DB::table('reports')
                    ->where('id', $report->id)
                    ->update([
                        'title_json' => json_encode(['uk' => $report->title, 'en' => $report->title]),
                        'description_json' => $report->description !== null
                            ? json_encode(['uk' => $report->description, 'en' => $report->description])
                            : null,
                    ]);
            }
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->renameColumn('title_json', 'title');
            $table->renameColumn('description_json', 'description');
        });
    }
};
