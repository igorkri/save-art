<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('direction', ['user_to_admin', 'admin_to_user', 'system_to_user'])
                ->default('user_to_admin')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('messages')
            ->where('direction', 'system_to_user')
            ->update(['direction' => 'admin_to_user']);

        Schema::table('messages', function (Blueprint $table) {
            $table->enum('direction', ['user_to_admin', 'admin_to_user'])
                ->default('user_to_admin')
                ->change();
        });
    }
};
