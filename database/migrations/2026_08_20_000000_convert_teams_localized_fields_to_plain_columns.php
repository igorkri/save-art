<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const STRING_FIELDS = [
        'name' => 255,
        'country' => 255,
        'city' => 255,
        'region' => 255,
        'zip' => 20,
        'specialization' => 255,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            foreach (self::STRING_FIELDS as $field => $length) {
                $table->string("{$field}_plain", $length)->nullable()->after($field);
            }
            $table->text('description_plain')->nullable()->after('description');
        });

        DB::table('teams')
            ->orderBy('id')
            ->chunkById(100, function ($teams): void {
                foreach ($teams as $team) {
                    $update = [];
                    foreach (array_keys(self::STRING_FIELDS) as $field) {
                        $update["{$field}_plain"] = $this->extractLocalized($team->{$field});
                    }
                    $update['description_plain'] = $this->extractLocalized($team->description);

                    DB::table('teams')->where('id', $team->id)->update($update);
                }
            });

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn([...array_keys(self::STRING_FIELDS), 'description']);
        });

        DB::table('teams')->whereNull('name_plain')->update(['name_plain' => '']);

        Schema::table('teams', function (Blueprint $table): void {
            foreach (array_keys(self::STRING_FIELDS) as $field) {
                $table->renameColumn("{$field}_plain", $field);
            }
            $table->renameColumn('description_plain', 'description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            foreach (self::STRING_FIELDS as $field => $length) {
                $table->json("{$field}_json")->nullable()->after($field);
            }
            $table->json('description_json')->nullable()->after('description');
        });

        DB::table('teams')
            ->orderBy('id')
            ->chunkById(100, function ($teams): void {
                foreach ($teams as $team) {
                    $update = [];
                    foreach (array_keys(self::STRING_FIELDS) as $field) {
                        $update["{$field}_json"] = $this->restoreLocalized($team->{$field});
                    }
                    $update['description_json'] = $this->restoreLocalized($team->description);

                    DB::table('teams')->where('id', $team->id)->update($update);
                }
            });

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn([...array_keys(self::STRING_FIELDS), 'description']);
        });

        Schema::table('teams', function (Blueprint $table): void {
            foreach (array_keys(self::STRING_FIELDS) as $field) {
                $table->renameColumn("{$field}_json", $field);
            }
            $table->renameColumn('description_json', 'description');
        });
    }

    private function extractLocalized(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return $decoded['uk'] ?? $decoded['en'] ?? null;
        }

        return $value;
    }

    private function restoreLocalized(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(['uk' => $value, 'en' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
};
