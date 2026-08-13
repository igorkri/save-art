<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->toString('art_categories', 'name');
        $this->toString('parameter_values', 'value');
    }

    public function down(): void
    {
        $this->toJson('art_categories', 'name');
        $this->toJson('parameter_values', 'value');
    }

    private function toString(string $table, string $column): void
    {
        $temporary = $column.'_uk';
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string($temporary)->nullable());
        DB::statement("UPDATE `{$table}` SET `{$temporary}` = JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.uk')) WHERE `{$column}` IS NOT NULL");
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn($temporary, $column));
        DB::table($table)->whereNull($column)->update([$column => '']);
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string($column)->nullable(false)->change());
    }

    private function toJson(string $table, string $column): void
    {
        $temporary = $column.'_translations';
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json($temporary)->nullable());
        DB::statement("UPDATE `{$table}` SET `{$temporary}` = JSON_OBJECT('uk', `{$column}`, 'en', `{$column}`) WHERE `{$column}` IS NOT NULL");
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn($temporary, $column));
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json($column)->nullable(false)->change());
    }
};
