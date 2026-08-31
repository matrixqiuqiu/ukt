<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beasiswas', function (Blueprint $table) {
            $table->foreignId('jenis_beasiswa_id')->nullable()->after('jenis')->constrained('jenis_beasiswas')->nullOnDelete();
        });

        // Backfill dari kolom jenis string ke FK
        $map = [
            'prestasi' => 'JB001',
            'tidak_mampu' => 'JB002',
            'tahfidz' => 'JB003',
            'kerjasama' => 'JB004',
            'lain' => 'JB005',
        ];

        foreach ($map as $jenisStr => $kode) {
            $jenis = \App\Models\JenisBeasiswa::where('kode', $kode)->first();
            if ($jenis) {
                \Illuminate\Support\Facades\DB::table('beasiswas')
                    ->where('jenis', $jenisStr)
                    ->update(['jenis_beasiswa_id' => $jenis->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('beasiswas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenis_beasiswa_id');
        });
    }
};
