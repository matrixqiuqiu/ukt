<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
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
                'stats' => ['totalTagihan' => 0, 'sudahBayar' => 0, 'belumBayar' => 0],
            ]);
        }

        $tagihans = Tagihan::where('mahasiswa_id', $mahasiswa->id)
            ->with(['pembayarans'])
            ->latest()
            ->get();

        $totalTagihan = $tagihans->count();
        $sudahBayar = $tagihans->where('status', 'sudah_dibayar')->count();
        $belumBayar = $totalTagihan - $sudahBayar;

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
                    })->with(['beasiswa.jenisBeasiswa','beasiswa.tahunAkademik'])->first();
            }

            return [
                'id' => $t->id,
                'semester' => $t->semester,
                'tahun_akademik' => $t->tahun_akademik,
                'nominal' => $t->nominal,
                'status' => $t->status,
                'jatuh_tempo' => $t->jatuh_tempo->format('d/m/Y'),
                'last_pembayaran_id' => $confirmed ? $confirmed->id : null,
                'pending_pembayaran_id' => $pending ? $pending->id : null,
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

        return Inertia::render('Mahasiswa/Dashboard', [
            'tagihans' => $tagihansFormatted,
            'stats' => [
                'totalTagihan' => $totalTagihan,
                'sudahBayar' => $sudahBayar,
                'belumBayar' => $belumBayar,
            ],
        ]);
    }
}
