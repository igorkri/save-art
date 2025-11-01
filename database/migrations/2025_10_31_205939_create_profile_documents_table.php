<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * documents (
     * id INT,
     * user_id INT,
     * file_path VARCHAR,
     * hash VARCHAR,
     * signed_file_path VARCHAR,
     * sign_status ENUM('pending','signed','failed'),
     * signed_at DATETIME,
     * service ENUM('diia','vchasno','iit'),
     * signature_base64 LONGTEXT
     * )
     */
    public function up(): void
    {
        Schema::create('profile_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('file_path');
            $table->string('hash')->unique();
            $table->string('signed_file_path')->nullable();
            $table->enum('sign_status', ['pending', 'signed', 'failed'])->default('pending');
            $table->dateTime('signed_at')->nullable();
            $table->enum('service', ['diia', 'vchasno', 'iit']);
            $table->longText('signature_base64')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_documents');
    }
};
