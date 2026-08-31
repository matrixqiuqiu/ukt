<?php

namespace App\Helpers;

use App\Models\TahunAkademik;

class SemesterHelper
{
    public const GANJIL = 1;
    public const GENAP = 2;

    /**
     * Map SIAKAD flag (1=ganjil, 2=genap) to label.
     */
    public static function labelFromFlag(int $flag): string
    {
        return $flag === self::GENAP ? 'Genap' : 'Ganjil';
    }

    /**
     * Map label/Ganjil-Genap or numeric flag to flag integer.
     */
    public static function flagFromLabel(string|int $label): int
    {
        if (is_int($label)) {
            return $label === self::GENAP ? self::GENAP : self::GANJIL;
        }
        return strtolower(trim($label)) === 'genap' ? self::GENAP : self::GANJIL;
    }

    /**
     * Normalize any semester input (1, 2, "Ganjil", "Genap") to flag 1/2.
     */
    public static function normalizeToFlag(string|int $value): int
    {
        if (is_int($value) || ctype_digit((string) $value)) {
            $int = (int) $value;
            if (in_array($int, [self::GANJIL, self::GENAP], true)) {
                return $int;
            }
        }
        return self::flagFromLabel((string) $value);
    }

    /**
     * Calculate semester number from angkatan + tahun akademik.
     *
     * Formula:
     *   startYear = first 4 chars of tahun_akademik (e.g. "2026" from "2026/2027")
     *   difference = startYear - angkatan
     *   if Ganjil (1): semester = (difference * 2) + 1
     *   if Genap  (2): semester = (difference * 2) + 2
     *
     * Example: angkatan=2025, "2026/2027" + "Genap" → (1*2)+2 = 4
     * SIAKAD flag: 1=Ganjil, 2=Genap (sesuai kontrak SIAKAD -> nim, tahun_akademik, semester)
     */
    public static function hitung(string $angkatan, string $tahunAkademik, string|int $semesterGanjilGenap): int
    {
        $startYear = (int) substr($tahunAkademik, 0, 4);
        $difference = $startYear - (int) $angkatan;

        $flag = self::normalizeToFlag($semesterGanjilGenap);

        if ($flag === self::GENAP) {
            return ($difference * 2) + 2;
        }

        return ($difference * 2) + 1;
    }

    /**
     * Hitung via SIAKAD flag integer (1=ganjil, 2=genap) — alias eksplisit untuk kontrak SIAKAD.
     */
    public static function hitungByFlag(string $angkatan, string $tahunAkademik, int $semesterFlag): int
    {
        return self::hitung($angkatan, $tahunAkademik, $semesterFlag);
    }

    /**
     * Get the next semester number (for tagihan generation).
     */
    public static function hitungNext(string $angkatan, string $tahunAkademik, string|int $semesterGanjilGenap): int
    {
        return self::hitung($angkatan, $tahunAkademik, $semesterGanjilGenap) + 1;
    }

    /**
     * Get semester from active TahunAkademik.
     */
    public static function fromActive(?TahunAkademik $ta = null): ?int
    {
        $ta = $ta ?? TahunAkademik::where('is_aktif', true)->first();
        if (!$ta) return null;

        // We need angkatan — this method only returns semester for a specific mahasiswa
        // Use hitung() instead for per-mahasiswa calculation
        return null;
    }
}
