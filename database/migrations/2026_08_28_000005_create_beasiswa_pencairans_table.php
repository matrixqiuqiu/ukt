<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beasiswa_pencairans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beasiswa_id')->constrained('beasiswas')->cascadeOnDelete();
            $table->integer('termin_ke')->default(1);
            $table->decimal('nominal_dijanjikan', 12, 2);
            $table->decimal('nominal_cair', 12, 2)->default(0);
            $table->date('tanggal_janji_cair')->nullable();
            $table->date('jatuh_tempo_external')->nullable()->comment('C1: jatuh tempo penagihan ke eksternal, beda dengan tagihan mahasiswa');
            $table->date('tanggal_cair')->nullable();
            $table->string('bukti_tagihan')->nullable()->comment('Bukti penagihan ke eksternal');
            $table->string('bukti_cair')->nullable()->comment('Bukti transfer dari eksternal');
            $table->string('status')->default('ditagih')->comment('ditagih, cair_sebagian, cair_penuh, gagal');
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa_pencairans');
    }
};
