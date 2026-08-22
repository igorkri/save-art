<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parameters', function (Blueprint $table): void {
            $table->json('name_json')->nullable();
        });

        foreach (DB::table('parameters')->select(['id', 'name'])->cursor() as $parameter) {
            DB::table('parameters')
                ->where('id', $parameter->id)
                ->update(['name_json' => json_encode(['uk' => $parameter->name, 'en' => $parameter->name])]);
        }

        Schema::table('parameters', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('parameters', function (Blueprint $table): void {
            $table->renameColumn('name_json', 'name');
        });
    }

    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table): void {
            $table->string('name_varchar')->nullable();
        });

        foreach (DB::table('parameters')->select(['id', 'name'])->cursor() as $parameter) {
            $decoded = json_decode((string) $parameter->name, true);
            $name = is_array($decoded) ? ($decoded['uk'] ?? '') : (string) $parameter->name;

            DB::table('parameters')
                ->where('id', $parameter->id)
                ->update(['name_varchar' => $name]);
        }

        Schema::table('parameters', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('parameters', function (Blueprint $table): void {
            $table->renameColumn('name_varchar', 'name');
        });
    }
};
