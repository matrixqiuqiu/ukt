<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semester_aktifs', function (Blueprint $table) {
            $table->id();
            $table->string('tahun_akademik')->default('2025/2026');
            $table->date('jatuh_tempo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_aktifs');
    }
};
