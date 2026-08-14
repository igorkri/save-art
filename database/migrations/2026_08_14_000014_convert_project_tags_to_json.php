<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->json('tags_json')->nullable()->after('tags');
        });

        DB::table('projects')
            ->select(['id', 'tags'])
            ->whereNotNull('tags')
            ->orderBy('id')
            ->chunkById(100, function ($projects): void {
                foreach ($projects as $project) {
                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['tags_json' => json_encode(
                            $this->normalizeTags($project->tags),
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        )]);
                }
            });

        $this->replaceColumn('tags', 'tags_json');
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('tags_string')->nullable()->after('tags');
        });

        DB::table('projects')
            ->select(['id', 'tags'])
            ->whereNotNull('tags')
            ->orderBy('id')
            ->chunkById(100, function ($projects): void {
                foreach ($projects as $project) {
                    $tags = json_decode($project->tags, true);

                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['tags_string' => is_array($tags) ? implode(', ', $tags) : $tags]);
                }
            });

        $this->replaceColumn('tags', 'tags_string');
    }

    /**
     * @return list<string>|null
     */
    private function normalizeTags(string $value): ?array
    {
        if (strtolower(trim($value)) === 'null') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            $value = $decoded['uk'] ?? $decoded['en'] ?? $decoded;
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $tag): string => trim((string) $tag), $value),
            static fn (string $tag): bool => $tag !== '',
        ));
    }

    private function replaceColumn(string $column, string $temporary): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn($column));
        Schema::table('projects', fn (Blueprint $table) => $table->renameColumn($temporary, $column));
    }
};
