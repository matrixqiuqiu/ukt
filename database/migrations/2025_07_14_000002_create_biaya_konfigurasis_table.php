<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biaya_konfigurasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komponen_biaya_id')->constrained('komponen_biayas')->cascadeOnDelete();
            $table->year('angkatan');
            $table->string('jurusan');
            $table->decimal('nominal', 12, 2);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->unique(['komponen_biaya_id', 'angkatan', 'jurusan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biaya_konfigurasis');
    }
};
