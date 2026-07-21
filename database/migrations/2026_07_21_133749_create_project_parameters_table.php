<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parameter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parameter_value_id')->nullable()->constrained()->cascadeOnDelete()->comment('Обране значення зі списку (для type=list)');
            $table->json('custom_value')->nullable()->comment('Довільне значення (uk, en) (для type=custom)');
            $table->timestamps();

            $table->unique(['project_id', 'parameter_value_id']);
            $table->index(['project_id', 'parameter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_parameters');
    }
};
