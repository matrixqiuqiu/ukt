<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $baseQuery = Pembayaran::whereHas('tagihan', function ($q) use ($mahasiswa) {
            $q->where('mahasiswa_id', $mahasiswa->id);
        });

        // Calculate all-time summary statistics for this student
        $stats = [
            'total_transaksi' => (clone $baseQuery)->count(),
            'total_lunas' => (clone $baseQuery)->where('status', 'dikonfirmasi')->count(),
            'total_nominal_lunas' => (float) (clone $baseQuery)->where('status', 'dikonfirmasi')->sum('jumlah_bayar'),
            'total_pending' => (clone $baseQuery)->where('status', 'pending')->count(),
        ];

        $query = (clone $baseQuery)->with(['tagihan.mahasiswa', 'metodePembayaran']);

        // Filter by status if requested
        if ($request->filled('status') && $request->status !== 'semua') {
            if ($request->status === 'lunas') {
                $query->where('status', 'dikonfirmasi');
            } elseif ($request->status === 'pending') {
                $query->where('status', 'pending');
            } elseif ($request->status === 'ditolak') {
                $query->whereIn('status', ['ditolak', 'expired']);
            }
        }

        // Search query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('va_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('nama_pengirim', 'like', "%{$search}%")
                  ->orWhereHas('tagihan', function ($tq) use ($search) {
                      $tq->where('semester', 'like', "%{$search}%")
                         ->orWhere('tahun_akademik', 'like', "%{$search}%");
                  })
                  ->orWhereHas('metodePembayaran', function ($mq) use ($search) {
                      $mq->where('nama_metode', 'like', "%{$search}%");
                  });
            });
        }

        $riwayat = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $beasiswaMap = \App\Models\BeasiswaMahasiswa::whereIn('tagihan_id', $riwayat->getCollection()->pluck('tagihan_id')->filter())
            ->with(['beasiswa.jenisBeasiswa'])
            ->get()->keyBy('tagihan_id');

        $riwayat->getCollection()->transform(function ($p) use ($beasiswaMap) {
            $p->created_at = $p->created_at->toIso8601String();
            if ($p->verified_at) {
                $p->verified_at = $p->verified_at->toIso8601String();
            }
            if ($p->va_expired_at) {
                $p->va_expired_at = $p->va_expired_at->toIso8601String();
            }
            $bm = $beasiswaMap->get($p->tagihan_id);
            if ($bm) {
                $p->setAttribute('beasiswa', [
                    'kode' => $bm->beasiswa->kode,
                    'nama' => $bm->beasiswa->nama_beasiswa,
                    'diskon' => $bm->diskon_diterapkan,
                ]);
            }
            return $p;
        });

        return Inertia::render('Mahasiswa/Riwayat/Index', [
            'riwayat' => $riwayat,
            'stats' => $stats,
            'filters' => [
                'status' => $request->input('status', 'semua'),
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    public function show(Request $request, $id): Response
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $pembayaran = Pembayaran::whereHas('tagihan', function ($q) use ($mahasiswa) {
                $q->where('mahasiswa_id', $mahasiswa->id);
            })
            ->with(['tagihan.mahasiswa', 'metodePembayaran', 'riwayatTransaksi'])
            ->findOrFail($id);

        $pembayaran->created_at = $pembayaran->created_at->toIso8601String();
        if ($pembayaran->verified_at) {
            $pembayaran->verified_at = $pembayaran->verified_at->toIso8601String();
        }

        $beasiswaAssignment = \App\Models\BeasiswaMahasiswa::where('tagihan_id', $pembayaran->tagihan_id)
            ->with(['beasiswa.jenisBeasiswa'])
            ->first();
        if (!$beasiswaAssignment) {
            $mhs = $mahasiswa;
            $ta = $pembayaran->tagihan;
            $beasiswaAktif = app(\App\Services\BeasiswaService::class)->cariBeasiswaAktif($mhs, $ta->tahun_akademik, $ta->semester);
            if ($beasiswaAktif) {
                $beasiswaAssignment = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mhs->id)->where('beasiswa_id', $beasiswaAktif->id)->with(['beasiswa.jenisBeasiswa'])->first();
            }
        }

        return Inertia::render('Mahasiswa/Pembayaran/Show', [
            'pembayaran' => $pembayaran,
            'beasiswa' => $beasiswaAssignment ? [
                'kode' => $beasiswaAssignment->beasiswa->kode,
                'nama' => $beasiswaAssignment->beasiswa->nama_beasiswa,
                'jenis' => $beasiswaAssignment->beasiswa->jenisBeasiswa->nama ?? $beasiswaAssignment->beasiswa->jenis,
                'diskon' => $beasiswaAssignment->diskon_diterapkan,
                'tipe' => $beasiswaAssignment->beasiswa->tipe_diskon,
                'nilai' => $beasiswaAssignment->beasiswa->nilai_diskon,
                'status' => $beasiswaAssignment->status,
            ] : null,
        ]);
    }
}
