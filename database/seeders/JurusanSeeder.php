<?php

namespace Database\Seeders;

use App\Models\Fakultas;
use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $fakultasTeknik = Fakultas::where('kode', 'FK009')->first();
        $fakultasPendidikan = Fakultas::where('kode', 'FK003')->first();
        $fakultasPasca = Fakultas::where('kode', 'FK002')->first();
        $fakultasSeni = Fakultas::where('kode', 'FK004')->first();
        $fakultasHumaniora = Fakultas::where('kode', 'FK005')->first();
        $fakultasEkonomi = Fakultas::where('kode', 'FK006')->first();
        $fakultasKesehatan = Fakultas::where('kode', 'FK001')->first();
        $fakultasKedokteran = Fakultas::where('kode', 'FK007')->first();

        $jurusans = [
            ['kode' => 'PRD002', 'kodeps' => '08', 'nama' => 'S2 Ilmu Komputer', 'fakultas_id' => $fakultasTeknik?->id],
            ['kode' => 'PRD003', 'kodeps' => '01', 'nama' => 'S1 Pendidikan Keolahragaan', 'fakultas_id' => $fakultasPendidikan?->id],
            ['kode' => 'PRD004', 'kodeps' => '03', 'nama' => 'S2 Sastra Inggris', 'fakultas_id' => $fakultasPasca?->id],
            ['kode' => 'PRD005', 'kodeps' => '03', 'nama' => 'S1 Pendidikan Teknologi Informasi', 'fakultas_id' => $fakultasPendidikan?->id],
            ['kode' => 'PRD006', 'kodeps' => '02', 'nama' => 'S1 Seni Pertunjukan', 'fakultas_id' => $fakultasSeni?->id],
            ['kode' => 'PRD007', 'kodeps' => '01', 'nama' => 'S1 Desain Komunikasi Visual', 'fakultas_id' => $fakultasSeni?->id],
            ['kode' => 'PRD008', 'kodeps' => '01', 'nama' => 'S1 Hukum', 'fakultas_id' => $fakultasHumaniora?->id],
            ['kode' => 'PRD009', 'kodeps' => '01', 'nama' => 'S1 Teknik Informatika', 'fakultas_id' => $fakultasTeknik?->id],
            ['kode' => 'PRD010', 'kodeps' => '01', 'nama' => 'S1 Sistem Informasi', 'fakultas_id' => $fakultasTeknik?->id],
            ['kode' => 'PRD011', 'kodeps' => '01', 'nama' => 'S1 Manajemen Informatika', 'fakultas_id' => $fakultasTeknik?->id],
            ['kode' => 'PRD012', 'kodeps' => '01', 'nama' => 'S1 Teknik Komputer', 'fakultas_id' => $fakultasTeknik?->id],
            ['kode' => 'PRD013', 'kodeps' => '05', 'nama' => 'S1 Manajemen', 'fakultas_id' => $fakultasEkonomi?->id],
            ['kode' => 'PRD014', 'kodeps' => '05', 'nama' => 'S1 Akuntansi', 'fakultas_id' => $fakultasEkonomi?->id],
            ['kode' => 'PRD015', 'kodeps' => '06', 'nama' => 'S1 Pariwisata', 'fakultas_id' => $fakultasHumaniora?->id],
            ['kode' => 'PRD016', 'kodeps' => '07', 'nama' => 'S1 Pendidikan Bahasa Inggris', 'fakultas_id' => $fakultasPendidikan?->id],
            ['kode' => 'PRD017', 'kodeps' => '07', 'nama' => 'S1 Pendidikan Matematika', 'fakultas_id' => $fakultasPendidikan?->id],
            ['kode' => 'PRD018', 'kodeps' => '08', 'nama' => 'S1 Kedokteran Umum', 'fakultas_id' => $fakultasKedokteran?->id],
            ['kode' => 'PRD019', 'kodeps' => '01', 'nama' => 'S1 Keperawatan', 'fakultas_id' => $fakultasKesehatan?->id],
            ['kode' => 'PRD020', 'kodeps' => '01', 'nama' => 'S1 Kesehatan Masyarakat', 'fakultas_id' => $fakultasKesehatan?->id],
        ];

        foreach ($jurusans as $j) {
            $fakultasId = $j['fakultas_id'];
            $fakultasNama = null;
            if ($fakultasId) {
                $f = Fakultas::find($fakultasId);
                $fakultasNama = $f?->nama;
            }

            Jurusan::updateOrCreate(
                ['kode' => $j['kode']],
                [
                    'kodeps' => $j['kodeps'],
                    'nama' => $j['nama'],
                    'fakultas_id' => $fakultasId,
                    'fakultas' => $fakultasNama,
                ]
            );
        }
    }
}
