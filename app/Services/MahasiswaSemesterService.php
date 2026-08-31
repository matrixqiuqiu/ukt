<?php

namespace App\Services;

use App\Helpers\SemesterHelper;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;

class MahasiswaSemesterService
{
    /**
     * Hitung semester berjalan mahasiswa berdasarkan angkatan + Tahun Akademik aktif.
     * Menggunakan SemesterHelper::hitung() (flag 1=Ganjil, 2=Genap).
     * Fallback ke nilai tersimpan jika belum ada Tahun Akademik aktif.
     */
    public function hitung(Mahasiswa $mahasiswa, ?TahunAkademik $tahunAkademik = null): int
    {
        $ta = $tahunAkademik ?? TahunAkademik::aktif() ?? TahunAkademik::orderBy('created_at', 'desc')->first();

        if (!$ta) {
            return (int) $mahasiswa->semester;
        }

        return SemesterHelper::hitung(
            (string) $mahasiswa->angkatan,
            $ta->nama,
            $ta->semester
        );
    }

    public function hitungByAngkatan(int|string $angkatan, ?TahunAkademik $tahunAkademik = null): int
    {
        $ta = $tahunAkademik ?? TahunAkademik::aktif() ?? TahunAkademik::orderBy('created_at', 'desc')->first();

        if (!$ta) {
            return 1;
        }

        return SemesterHelper::hitung(
            (string) $angkatan,
            $ta->nama,
            $ta->semester
        );
    }

    /**
     * Sinkronkan kolom semester tersimpan agar selaras dengan hitungan (optional).
     * Kembalikan jumlah yang diperbarui.
     */
    public function syncAll(?TahunAkademik $tahunAkademik = null): int
    {
        $ta = $tahunAkademik ?? TahunAkademik::aktif();
        if (!$ta) return 0;

        $updated = 0;
        Mahasiswa::chunk(200, function ($chunk) use ($ta, &$updated) {
            foreach ($chunk as $mhs) {
                $hitung = SemesterHelper::hitung((string) $mhs->angkatan, $ta->nama, $ta->semester);
                if ((int) $mhs->semester !== $hitung) {
                    $mhs->update(['semester' => $hitung]);
                    $updated++;
                }
            }
        });
        return $updated;
    }
}
