<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beasiswa_mahasiswa', function (Blueprint $table) {
            $table->foreignId('pencairan_id')->nullable()->after('tagihan_id')->constrained('beasiswa_pencairans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('beasiswa_mahasiswa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pencairan_id');
        });
    }
};
