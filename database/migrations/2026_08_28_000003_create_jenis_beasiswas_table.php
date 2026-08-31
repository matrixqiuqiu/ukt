<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_beasiswas', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });

        // Seed default jenis
        \Illuminate\Support\Facades\DB::table('jenis_beasiswas')->insert([
            ['kode' => 'JB001', 'nama' => 'Prestasi', 'deskripsi' => 'Beasiswa prestasi akademik/non-akademik', 'status_aktif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'JB002', 'nama' => 'Tidak Mampu', 'deskripsi' => 'Beasiswa bagi mahasiswa kurang mampu', 'status_aktif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'JB003', 'nama' => 'Tahfidz', 'deskripsi' => 'Beasiswa tahfidz Al-Qur\'an', 'status_aktif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'JB004', 'nama' => 'Kerjasama', 'deskripsi' => 'Beasiswa program kerjasama', 'status_aktif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'JB005', 'nama' => 'Lainnya', 'deskripsi' => 'Jenis beasiswa lainnya', 'status_aktif' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_beasiswas');
    }
};
