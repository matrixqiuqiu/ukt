<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metode_pembayarans', function (Blueprint $table) {
            $table->text('instruksi')->nullable()->after('no_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('metode_pembayarans', function (Blueprint $table) {
            $table->dropColumn('instruksi');
        });
    }
};
