<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parameter_values', function (Blueprint $table): void {
            $table->json('value_json')->nullable();
        });

        foreach (DB::table('parameter_values')->select(['id', 'value'])->cursor() as $parameterValue) {
            DB::table('parameter_values')
                ->where('id', $parameterValue->id)
                ->update(['value_json' => json_encode(['uk' => $parameterValue->value, 'en' => $parameterValue->value])]);
        }

        Schema::table('parameter_values', function (Blueprint $table): void {
            $table->dropColumn('value');
        });

        Schema::table('parameter_values', function (Blueprint $table): void {
            $table->renameColumn('value_json', 'value');
        });
    }

    public function down(): void
    {
        Schema::table('parameter_values', function (Blueprint $table): void {
            $table->string('value_varchar')->nullable();
        });

        foreach (DB::table('parameter_values')->select(['id', 'value'])->cursor() as $parameterValue) {
            $decoded = json_decode((string) $parameterValue->value, true);
            $value = is_array($decoded) ? ($decoded['uk'] ?? '') : (string) $parameterValue->value;

            DB::table('parameter_values')
                ->where('id', $parameterValue->id)
                ->update(['value_varchar' => $value]);
        }

        Schema::table('parameter_values', function (Blueprint $table): void {
            $table->dropColumn('value');
        });

        Schema::table('parameter_values', function (Blueprint $table): void {
            $table->renameColumn('value_varchar', 'value');
        });
    }
};
