<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('va_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->boolean('success')->default(false);
            $table->integer('status_code')->nullable();
            $table->string('rcode')->nullable();
            $table->string('message')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('va_api_logs');
    }
};
