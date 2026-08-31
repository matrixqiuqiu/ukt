<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tagihans MODIFY COLUMN status ENUM('belum_dibayar', 'sudah_dibayar', 'terlambat', 'dispen') NOT NULL DEFAULT 'belum_dibayar'");

        // Backfill: tagihan yang dispensasinya sudah disetujui -> status dispen
        DB::statement("
            UPDATE tagihans t
            JOIN dispensasis d ON d.tagihan_id = t.id AND d.status = 'disetujui'
            SET t.status = 'dispen'
            WHERE t.status NOT IN ('sudah_dibayar', 'dispen')
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tagihans MODIFY COLUMN status ENUM('belum_dibayar', 'sudah_dibayar', 'terlambat') NOT NULL DEFAULT 'belum_dibayar'");
    }
};
