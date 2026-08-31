<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\SemesterHelper;
use App\Models\Mahasiswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PembayaranApiController extends Controller
{
    /**
     * GET /api/v1/pembayaran/status/{nim?}
     * GET /api/v1/pembayaran/status?nim=xxx&tahun_akademik=2026/2027&semester=1
     *
     * Kontrak SIAKAD: cek pembayaran via nim + tahun_akademik + semester.
     *   - nim            -> NIM mahasiswa (wajib)
     *   - tahun_akademik -> "2026/2027" (jika kosong, pakai tahun akademik aktif)
     *   - semester       -> 1=Ganjil, 2=Genap (jika kosong, dihitung dari tahun akademik aktif + angkatan)
     *                      Sesuai flag SIAKAD 1 untuk ganjil, 2 untuk genap.
     */
    public function status(Request $request): JsonResponse
    {
        // Dukungan NIM via path URL (/status/{nim}) saat query string tidak tersedia
        if (! $request->has('nim') && $request->route('nim') !== null) {
            $request->merge(['nim' => $request->route('nim')]);
        }

        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:12'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'integer', 'in:1,2'],
        ], [
            'nim.required' => 'Parameter nim wajib diisi.',
            'semester.in' => 'Parameter semester harus 1 (Ganjil) atau 2 (Genap).',
        ]);

        // Toleransi bila ditulis "nim=25080110013" pada path
        $nim = preg_replace('/^nim\s*=/i', '', trim((string) $validated['nim']));

        if ($nim === '') {
            return response()->json([
                'status' => false,
                'message' => 'Parameter nim wajib diisi.',
                'contoh_penggunaan' => 'GET /api/v1/pembayaran/status?nim=25080110013',
            ], 400);
        }

        // Cari Mahasiswa
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();
        if (! $mahasiswa) {
            return response()->json([
                'status' => false,
                'message' => "Mahasiswa dengan NIM {$nim} tidak ditemukan.",
            ], 404);
        }

        // Tentukan Tahun Akademik: dari parameter, atau tahun akademik aktif
        if (! empty($validated['tahun_akademik'])) {
            $ta = TahunAkademik::where('nama', $validated['tahun_akademik'])->first();
            if (! $ta) {
                return response()->json([
                    'status' => false,
                    'message' => "Tahun akademik '{$validated['tahun_akademik']}' tidak ditemukan.",
                ], 404);
            }
        } else {
            $ta = TahunAkademik::where('is_aktif', true)->first();
            if (! $ta) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tahun akademik aktif belum diatur.',
                ], 404);
            }
        }

        // Semester saat ini (dihitung dari angkatan + tahun akademik aktif)
        $semesterSaatIni = SemesterHelper::hitung(
            $mahasiswa->angkatan,
            $ta->nama,
            $ta->semester
        );

        // Semester tagihan: SIAKAD kirim flag 1=Ganjil, 2=Genap -> konversi ke nomor semester riil via SemesterHelper
        // Jika tidak ada parameter semester, gunakan semester saat ini.
        if (isset($validated['semester'])) {
            $semesterFlag = (int) $validated['semester'];
            $semesterTagihan = SemesterHelper::hitungByFlag(
                $mahasiswa->angkatan,
                $ta->nama,
                $semesterFlag
            );
            $semesterLabelDiminta = SemesterHelper::labelFromFlag($semesterFlag);
        } else {
            $semesterTagihan = $semesterSaatIni;
            $semesterFlag = SemesterHelper::flagFromLabel($ta->semester);
            $semesterLabelDiminta = $ta->semester;
            //   return response()->json([
            //     'status' => false,
            //     'message' => 'semester required.',
            // ], 404);
        }

        // Ambil tagihan sesuai semester & tahun akademik yang diminta
        $tagihan = Tagihan::where('mahasiswa_id', $mahasiswa->id)
            ->where('semester', $semesterTagihan)
            ->where('tahun_akademik', $ta->nama)
            ->with(['pembayarans' => fn ($q) => $q->where('status', 'dikonfirmasi')])
            ->first();

        // Logika Penentuan Status Pembayaran
        $isLunas = false;
        $isDispen = false;
        $statusTagihan = 'belum_dibayar';
        $nominalTagihan = 0.0;
        $jatuhTempo = null;

        if ($tagihan) {
            $nominalTagihan = (float) $tagihan->nominal;
            $jatuhTempo = $tagihan->jatuh_tempo?->format('Y-m-d');
            $statusTagihan = $tagihan->status;

            if ($statusTagihan === 'sudah_dibayar' || $tagihan->pembayarans->isNotEmpty()) {
                $isLunas = true;
                $statusTagihan = 'lunas';
            } elseif ($statusTagihan === 'dispen') {
                $isDispen = true;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil status pembayaran.',
            'data' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_lengkap,
                'angkatan' => $mahasiswa->angkatan,
                'tahun_akademik' => $ta->nama,
                'semester' => $semesterFlag,
                'semester_label' => $semesterLabelDiminta,
                'semester_saat_ini' => $semesterSaatIni,
                'semester_tagihan' => $semesterTagihan,
                'nominal' => $nominalTagihan,
                'jatuh_tempo' => $jatuhTempo,
                'status_pembayaran' => $statusTagihan,
                'is_lunas' => $isLunas,
                'is_dispen' => $isDispen,
                'boleh_isi_krs' => $isLunas || $isDispen,
            ],
        ], 200);
    }
}
