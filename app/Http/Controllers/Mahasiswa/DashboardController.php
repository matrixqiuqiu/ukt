<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use App\Models\SemesterAktif;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        if (!$mahasiswa) {
            return Inertia::render('Mahasiswa/Dashboard', [
                'tagihans' => [],
                'activeTagihan' => null,
                'metodePembayarans' => [],
                'mahasiswa' => null,
                'semesterAktif' => SemesterAktif::instance(),
                'stats' => [
                    'totalTagihan' => 0,
                    'sudahBayar' => 0,
                    'belumBayar' => 0,
                    'nominalLunas' => 0,
                    'nominalBelumBayar' => 0,
                ],
                'vaExpiredAt' => now()
                    ->addDays((int) env('NTB_VA_DEFAULT_EXPIRED_DAYS', 0))
                    ->addHours((int) env('NTB_VA_DEFAULT_EXPIRED_HOURS', 0))
                    ->addMinutes((int) env('NTB_VA_DEFAULT_EXPIRED_MINUTES', 5))
                    ->toIso8601String(),
            ]);
        }

        $tagihans = Tagihan::where('mahasiswa_id', $mahasiswa->id)
            ->with(['pembayarans.metodePembayaran'])
            ->latest()
            ->get();

        $totalTagihan = $tagihans->count();
        $sudahBayar = $tagihans->where('status', 'sudah_dibayar')->count();
        $belumBayar = $totalTagihan - $sudahBayar;

        $nominalLunas = (float) $tagihans->where('status', 'sudah_dibayar')->sum('nominal');
        $nominalBelumBayar = (float) $tagihans->where('status', '!=', 'sudah_dibayar')->sum('nominal');

        // Preload beasiswa assignments untuk mahasiswa ini
        $beasiswaMap = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->with(['beasiswa.jenisBeasiswa', 'beasiswa.tahunAkademik'])
            ->get()->keyBy('tagihan_id');

        $tagihansFormatted = $tagihans->map(function ($t) use ($beasiswaMap) {
            $confirmed = $t->pembayarans->where('status', 'dikonfirmasi')->first();
            $pending = $t->pembayarans
                ->where('status', 'pending')
                ->filter(function ($p) {
                    return !$p->va_expired_at || now()->lte($p->va_expired_at);
                })
                ->sortByDesc('id')
                ->first();

            $beasiswa = $beasiswaMap->get($t->id);
            // Fallback: cari beasiswa aktif untuk periode tagihan jika tidak ada pivot tagihan_id
            if (!$beasiswa) {
                $beasiswa = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $t->mahasiswa_id)
                    ->whereHas('beasiswa', function ($q) use ($t) {
                        $q->where('status_aktif', true)
                          ->where(function ($qq) use ($t) {
                              $qq->whereHas('tahunAkademik', fn($tt) => $tt->where('nama', $t->tahun_akademik))
                                  ->where('semester', $t->semester % 2 === 1 ? 1 : 2)
                              ->orWhere(function ($sub) { $sub->whereNull('tahun_akademik_id')->whereNull('semester'); });
                          });
                    })->with(['beasiswa.jenisBeasiswa', 'beasiswa.tahunAkademik'])->first();
            }

            return [
                'id' => $t->id,
                'semester' => $t->semester,
                'tahun_akademik' => $t->tahun_akademik,
                'nominal' => (float) $t->nominal,
                'status' => $t->status,
                'jatuh_tempo' => $t->jatuh_tempo ? $t->jatuh_tempo->format('d/m/Y') : '-',
                'jatuh_tempo_raw' => $t->jatuh_tempo ? $t->jatuh_tempo->format('Y-m-d') : null,
                'keterangan' => $t->keterangan,
                'last_pembayaran_id' => $confirmed ? $confirmed->id : null,
                'pending_pembayaran_id' => $pending ? $pending->id : null,
                'pending_pembayaran' => $pending ? [
                    'id' => $pending->id,
                    'va_number' => $pending->va_number,
                    'va_expired_at' => $pending->va_expired_at ? $pending->va_expired_at->toIso8601String() : null,
                    'metode_pembayaran_id' => $pending->metode_pembayaran_id,
                    'metode_pembayaran_nama' => $pending->metodePembayaran?->nama_metode ?? 'Virtual Account',
                    'jumlah_bayar' => (float) $pending->jumlah_bayar,
                    'status' => $pending->status,
                ] : null,
                'beasiswa' => $beasiswa ? [
                    'id' => $beasiswa->beasiswa->id ?? $beasiswa->beasiswa_id,
                    'kode' => $beasiswa->beasiswa->kode ?? null,
                    'nama' => $beasiswa->beasiswa->nama_beasiswa ?? null,
                    'jenis' => $beasiswa->beasiswa->jenisBeasiswa->nama ?? $beasiswa->beasiswa->jenis ?? null,
                    'tipe' => $beasiswa->beasiswa->tipe_diskon ?? null,
                    'nilai' => $beasiswa->beasiswa->nilai_diskon ?? null,
                    'diskon' => $beasiswa->diskon_diterapkan,
                    'status' => $beasiswa->status,
                ] : null,
            ];
        });

        $semesterAktif = SemesterAktif::instance();
        
        // Pilih activeTagihan: prioritas tagihan semester aktif yang belum dibayar, atau tagihan belum bayar teratas, atau tagihan terakhir
        $activeTagihan = $tagihansFormatted->first(function ($t) use ($semesterAktif) {
            return $t['status'] !== 'sudah_dibayar' && $t['tahun_akademik'] === $semesterAktif->tahun_akademik;
        }) ?? $tagihansFormatted->first(function ($t) {
            return $t['status'] !== 'sudah_dibayar';
        }) ?? $tagihansFormatted->first();

        $metodePembayarans = MetodePembayaran::where('status_aktif', true)->get();

        return Inertia::render('Mahasiswa/Dashboard', [
            'tagihans' => $tagihansFormatted,
            'activeTagihan' => $activeTagihan,
            'metodePembayarans' => $metodePembayarans,
            'mahasiswa' => [
                'id' => $mahasiswa->id,
                'nim' => $mahasiswa->nim,
                'nama_lengkap' => $mahasiswa->nama_lengkap,
                'jurusan' => $mahasiswa->jurusan,
                'semester' => $mahasiswa->semester,
                'angkatan' => $mahasiswa->angkatan,
                'telepon' => $mahasiswa->telepon,
                'email' => $mahasiswa->email ?? $user->email,
            ],
            'semesterAktif' => $semesterAktif,
            'stats' => [
                'totalTagihan' => $totalTagihan,
                'sudahBayar' => $sudahBayar,
                'belumBayar' => $belumBayar,
                'nominalLunas' => $nominalLunas,
                'nominalBelumBayar' => $nominalBelumBayar,
            ],
            'vaExpiredAt' => now()
                ->addDays((int) env('NTB_VA_DEFAULT_EXPIRED_DAYS', 0))
                ->addHours((int) env('NTB_VA_DEFAULT_EXPIRED_HOURS', 0))
                ->addMinutes((int) env('NTB_VA_DEFAULT_EXPIRED_MINUTES', 5))
                ->toIso8601String(),
        ]);
    }
}
