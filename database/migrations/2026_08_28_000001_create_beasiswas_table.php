<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beasiswas', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama_beasiswa');
            $table->string('jenis')->default('prestasi')->comment('prestasi, tidak_mampu, tahfidz, kerjasama, lain');
            $table->string('sumber_dana')->nullable()->comment('internal, eksternal, pemerintah');
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademiks')->nullOnDelete();
            $table->tinyInteger('semester')->nullable()->comment('1=Ganjil, 2=Genap');
            $table->string('tipe_diskon')->default('persen')->comment('persen, nominal, full');
            $table->decimal('nilai_diskon', 12, 2)->default(0)->comment('persen 0-100 atau nominal rupiah');
            $table->foreignId('komponen_biaya_id')->nullable()->constrained('komponen_biayas')->nullOnDelete();
            $table->integer('kuota')->default(0)->comment('jumlah_mahasiswa - kuota penerima');
            $table->integer('terpakai')->default(0);
            $table->date('tanggal_buka')->nullable();
            $table->date('tanggal_tutup')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswas');
    }
};
