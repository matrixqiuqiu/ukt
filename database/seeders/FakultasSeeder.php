<?php

namespace Database\Seeders;

use App\Models\Fakultas;
use Illuminate\Database\Seeder;

class FakultasSeeder extends Seeder
{
    public function run(): void
    {
        $fakultas = [
            ['kode' => 'FK001', 'kodef' => '01', 'nama' => 'Fakultas Kesehatan'],
            ['kode' => 'FK002', 'kodef' => '08', 'nama' => 'Program Pasca Sarjana'],
            ['kode' => 'FK003', 'kodef' => '07', 'nama' => 'Fakultas Pendidikan'],
            ['kode' => 'FK004', 'kodef' => '02', 'nama' => 'Fakultas Seni & Desain'],
            ['kode' => 'FK005', 'kodef' => '06', 'nama' => 'Fakultas Humaniora, Hukum & Pariwisata'],
            ['kode' => 'FK006', 'kodef' => '05', 'nama' => 'Fakultas Ekonomi & Bisnis'],
            ['kode' => 'FK007', 'kodef' => '08', 'nama' => 'Fakultas Kedokteran'],
            ['kode' => 'FK009', 'kodef' => '01', 'nama' => 'Fakultas Teknik'],
        ];

        foreach ($fakultas as $f) {
            Fakultas::updateOrCreate(
                ['kode' => $f['kode']],
                $f
            );
        }
    }
}
