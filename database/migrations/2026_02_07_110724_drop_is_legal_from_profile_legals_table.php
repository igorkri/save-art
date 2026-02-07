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
        Schema::table('profile_legals', function (Blueprint $table) {
            $table->dropColumn('is_legal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_legals', function (Blueprint $table) {
            $table->boolean('is_legal')->default(false)->comment('Признак приватної особи або юридичної');
        });
    }
};
