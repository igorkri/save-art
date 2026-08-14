<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('tags_json')->nullable()->after('tags');
        });

        DB::table('users')
            ->select(['id', 'tags'])
            ->whereNotNull('tags')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['tags_json' => json_encode(
                            $this->normalizeTags($user->tags),
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        )]);
                }
            });

        $this->replaceColumn('tags', 'tags_json');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tags_string')->nullable()->after('tags');
        });

        DB::table('users')
            ->select(['id', 'tags'])
            ->whereNotNull('tags')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $tags = json_decode($user->tags, true);

                    DB::table('users')
                        ->where('id', $user->id)
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
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
        Schema::table('users', fn (Blueprint $table) => $table->renameColumn($temporary, $column));
    }
};
