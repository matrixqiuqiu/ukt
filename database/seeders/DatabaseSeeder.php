<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MetodePembayaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin UBT',
            'email' => 'admin@ubt.ac.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Sample Metode Pembayaran
        $metode = [
            ['nama_metode' => 'Bank BRI', 'kode' => 'BRI', 'no_rekening' => '1234567890', 'kategori' => 'rekening_universitas'],
            ['nama_metode' => 'Bank BNI', 'kode' => 'BNI', 'no_rekening' => '0987654321', 'kategori' => 'rekening_universitas'],
            ['nama_metode' => 'Bank Mandiri', 'kode' => 'MANDIRI', 'no_rekening' => '1122334455', 'kategori' => 'rekening_universitas'],
            ['nama_metode' => 'Bank BCA', 'kode' => 'BCA', 'no_rekening' => '5566778899', 'kategori' => 'rekening_universitas'],
            ['nama_metode' => 'Virtual Account', 'kode' => 'VA', 'no_rekening' => '03141', 'kategori' => 'virtual_account'],
        ];

        foreach ($metode as $m) {
            MetodePembayaran::create($m);
        }

        // Mahasiswa di-sinkronkan dari API Siakad (bukan di-seed)
    }
}
