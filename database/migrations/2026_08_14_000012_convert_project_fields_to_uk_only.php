<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertJsonColumnToString('projects', 'title', false);
        $this->convertJsonColumnToText('projects', 'short_description');
        $this->convertJsonColumnToString('projects', 'tags');
        $this->convertJsonColumnToString('project_stages', 'title', false);
        $this->convertJsonColumnToText('project_stages', 'description');
        $this->convertJsonColumnToString('project_bonuses', 'title', false);
        $this->convertJsonColumnToText('project_bonuses', 'description');
        $this->convertJsonColumnToText('project_parameters', 'custom_value');

        $this->transformNestedFields('projects', 'budget_items', ['name'], false);
        $this->transformNestedFields('projects', 'content_blocks', [
            'heading_text',
            'paragraph_text',
            'image_alt',
            'image_caption',
        ], false);
        $this->transformNestedFields('project_stages', 'documents', ['description'], false);
    }

    public function down(): void
    {
        $this->transformNestedFields('projects', 'budget_items', ['name'], true);
        $this->transformNestedFields('projects', 'content_blocks', [
            'heading_text',
            'paragraph_text',
            'image_alt',
            'image_caption',
        ], true);
        $this->transformNestedFields('project_stages', 'documents', ['description'], true);

        $this->convertStringColumnToJson('projects', 'title', false);
        $this->convertStringColumnToJson('projects', 'short_description');
        $this->convertStringColumnToJson('projects', 'tags');
        $this->convertStringColumnToJson('project_stages', 'title', false);
        $this->convertStringColumnToJson('project_stages', 'description');
        $this->convertStringColumnToJson('project_bonuses', 'title', false);
        $this->convertStringColumnToJson('project_bonuses', 'description');
        $this->convertStringColumnToJson('project_parameters', 'custom_value');
    }

    private function convertJsonColumnToString(string $table, string $column, bool $nullable = true): void
    {
        $temporary = $column.'_uk';

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string($temporary)->nullable());

        DB::statement("UPDATE `{$table}` SET `{$temporary}` = JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.uk')) WHERE `{$column}` IS NOT NULL");
        $this->replaceColumn($table, $column, $temporary);

        if (! $nullable) {
            DB::table($table)->whereNull($column)->update([$column => '']);
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string($column)->nullable(false)->change());
        }
    }

    private function convertJsonColumnToText(string $table, string $column, bool $nullable = true): void
    {
        $temporary = $column.'_uk';

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->text($temporary)->nullable());

        DB::statement("UPDATE `{$table}` SET `{$temporary}` = JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.uk')) WHERE `{$column}` IS NOT NULL");
        $this->replaceColumn($table, $column, $temporary);

        if (! $nullable) {
            DB::table($table)->whereNull($column)->update([$column => '']);
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->text($column)->nullable(false)->change());
        }
    }

    private function convertStringColumnToJson(string $table, string $column, bool $nullable = true): void
    {
        $temporary = $column.'_translations';

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json($temporary)->nullable());

        DB::statement("UPDATE `{$table}` SET `{$temporary}` = JSON_OBJECT('uk', `{$column}`, 'en', `{$column}`) WHERE `{$column}` IS NOT NULL");
        $this->replaceColumn($table, $column, $temporary);

        if (! $nullable) {
            DB::table($table)->whereNull($column)->update([$column => json_encode(['uk' => '', 'en' => ''])]);
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json($column)->nullable(false)->change());
        }
    }

    private function replaceColumn(string $table, string $column, string $temporary): void
    {
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn($temporary, $column));
    }

    /**
     * @param  list<string>  $keys
     */
    private function transformNestedFields(string $table, string $column, array $keys, bool $restore): void
    {
        DB::table($table)
            ->select(['id', $column])
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column, $keys, $restore): void {
                foreach ($rows as $row) {
                    $items = json_decode($row->{$column}, true);

                    if (! is_array($items)) {
                        continue;
                    }

                    foreach ($items as &$item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        foreach ($keys as $key) {
                            if (! array_key_exists($key, $item) || $item[$key] === null) {
                                continue;
                            }

                            if ($restore) {
                                if (! is_array($item[$key])) {
                                    $item[$key] = ['uk' => $item[$key], 'en' => $item[$key]];
                                }

                                continue;
                            }

                            if (is_array($item[$key])) {
                                $item[$key] = $item[$key]['uk'] ?? null;
                            }
                        }
                    }
                    unset($item);

                    DB::table($table)->where('id', $row->id)->update([
                        $column => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }
};
