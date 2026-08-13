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
        // Патерн A: `title` — проста top-level мультимовна структура {"uk":..,"en":..}.
        Schema::table('news', function (Blueprint $table) {
            $table->string('title_uk')->nullable()->after('title');
        });

        DB::statement("UPDATE news SET title_uk = JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk')) WHERE title IS NOT NULL");

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->renameColumn('title_uk', 'title');
        });

        // Патерн B: `text_blocks` — json-колонка зі списком блоків, у кожному з яких
        // поле `paragraphs` є вкладеною мультимовною структурою {"uk": [...], "en": [...]}.
        // Колонка залишається json/array, лише `paragraphs` кожного блоку "згортається"
        // до українського масиву абзаців.
        DB::table('news')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                if ($row->text_blocks === null) {
                    continue;
                }

                $decoded = json_decode($row->text_blocks, true);
                $collapsed = $this->collapseTextBlocksToUkrainian($decoded);

                DB::table('news')->where('id', $row->id)->update([
                    'text_blocks' => json_encode($collapsed),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * УВАГА: англійський контент (en) безповоротно втрачений після up().
     * down() відновлює лише СТРУКТУРУ {"uk": ..., "en": ...}, дублюючи
     * українське значення в обидва ключі.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->json('title_json')->nullable()->after('title');
        });

        DB::table('news')->whereNotNull('title')->get()->each(function ($row) {
            DB::table('news')->where('id', $row->id)->update([
                'title_json' => json_encode(['uk' => $row->title, 'en' => $row->title]),
            ]);
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->renameColumn('title_json', 'title');
        });

        DB::table('news')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                if ($row->text_blocks === null) {
                    continue;
                }

                $decoded = json_decode($row->text_blocks, true);
                $restored = $this->restoreTextBlocksLanguageStructure($decoded);

                DB::table('news')->where('id', $row->id)->update([
                    'text_blocks' => json_encode($restored),
                ]);
            }
        });
    }

    /**
     * Перетворює список текстових блоків, замінюючи в кожному блоці
     * `paragraphs` (мультимовну структуру {"uk": [...], "en": [...]})
     * на плаский масив українських абзаців.
     */
    private function collapseTextBlocksToUkrainian(mixed $textBlocks): mixed
    {
        if (! is_array($textBlocks)) {
            return $textBlocks;
        }

        return array_map(function ($block) {
            if (! is_array($block)) {
                return $block;
            }

            $paragraphs = $block['paragraphs'] ?? null;

            if (is_array($paragraphs) && (array_key_exists('uk', $paragraphs) || array_key_exists('en', $paragraphs))) {
                $block['paragraphs'] = $paragraphs['uk'] ?? [];
            }

            return $block;
        }, $textBlocks);
    }

    /**
     * Відновлює мультимовну структуру `paragraphs` у кожному текстовому блоці,
     * дублюючи наявний (український) масив абзаців у ключі "uk" та "en".
     */
    private function restoreTextBlocksLanguageStructure(mixed $textBlocks): mixed
    {
        if (! is_array($textBlocks)) {
            return $textBlocks;
        }

        return array_map(function ($block) {
            if (! is_array($block)) {
                return $block;
            }

            $paragraphs = $block['paragraphs'] ?? null;

            if (is_array($paragraphs)) {
                $block['paragraphs'] = ['uk' => $paragraphs, 'en' => $paragraphs];
            }

            return $block;
        }, $textBlocks);
    }
};
