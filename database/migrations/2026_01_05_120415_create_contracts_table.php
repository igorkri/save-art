<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Contracts table stores contracts between artists and the platform.
     * This corresponds to screens 03.7.5 and 03.7.5.1 from Figma specification.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('template_version')->default('1.0');
            $table->string('file_path')->nullable();
            $table->string('signed_file_path')->nullable();
            $table->enum('status', ['pending', 'signed', 'rejected', 'expired'])->default('pending');
            $table->enum('sign_service', ['diia', 'vchasno', 'iit', 'manual'])->nullable();
            $table->longText('signature_base64')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
