<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metode_pembayarans', function (Blueprint $table) {
            $table->string('no_rekening')->nullable()->after('kode');
            $table->enum('kategori', ['rekening_universitas', 'virtual_account'])->default('rekening_universitas')->after('no_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('metode_pembayarans', function (Blueprint $table) {
            $table->dropColumn(['no_rekening', 'kategori']);
        });
    }
};
