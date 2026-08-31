<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beasiswa_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beasiswa_id')->constrained('beasiswas')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->foreignId('tagihan_id')->nullable()->constrained('tagihans')->nullOnDelete();
            $table->decimal('diskon_diterapkan', 12, 2)->default(0);
            $table->string('status')->default('diajukan')->comment('diajukan, disetujui, ditolak, aktif');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['beasiswa_id', 'mahasiswa_id', 'tagihan_id'], 'uniq_beasiswa_mhs_tagihan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa_mahasiswa');
    }
};
