<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['project_id']);

            // Make project_id nullable
            $table->unsignedBigInteger('project_id')->nullable()->change();

            // Re-add foreign key with set null on delete
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->nullOnDelete();

            // Add donation_type column to distinguish platform vs project donations
            $table->string('donation_type', 20)->default('project')->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            // Remove donation_type column
            $table->dropColumn('donation_type');

            // Drop foreign key
            $table->dropForeign(['project_id']);

            // Make project_id required again
            $table->unsignedBigInteger('project_id')->nullable(false)->change();

            // Re-add original foreign key
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
        });
    }
};
