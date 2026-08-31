<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->string('email')->nullable()->after('nama_lengkap');
            $table->string('telepon')->nullable()->after('email');
            $table->string('program_studi_kode')->nullable()->after('jurusan');
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropColumn(['email', 'telepon', 'program_studi_kode']);
        });
    }
};
