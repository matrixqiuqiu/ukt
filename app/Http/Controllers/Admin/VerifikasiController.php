<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerifikasiController extends Controller
{
    public function index(Request $request)
    {
        // Mark VA payments that have passed their deadline as expired
        Pembayaran::where('status', 'pending')
            ->whereNotNull('va_expired_at')
            ->where('va_expired_at', '<', now())
            ->update(['status' => 'expired']);

        $query = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->where('status', 'pending');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('tagihan.mahasiswa', function ($m) use ($search) {
                    $m->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nim', 'like', "%{$search}%");
                })
                ->orWhere('va_number', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%")
                ->orWhere('nama_pengirim', 'like', "%{$search}%");
            });
        }

        $pembayarans = $query->latest()->paginate(10)->withQueryString();

        $pembayarans->getCollection()->transform(function ($p) {
            $p->created_at = $p->created_at->toIso8601String();
            return $p;
        });

        $expiredQuery = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->where('status', 'expired');

        if ($request->filled('search_expired')) {
            $searchExp = $request->search_expired;
            $expiredQuery->where(function ($q) use ($searchExp) {
                $q->whereHas('tagihan.mahasiswa', function ($m) use ($searchExp) {
                    $m->where('nama_lengkap', 'like', "%{$searchExp}%")
                      ->orWhere('nim', 'like', "%{$searchExp}%");
                })
                ->orWhere('va_number', 'like', "%{$searchExp}%");
            });
        }

        $expiredPembayarans = $expiredQuery->latest()->limit(50)->get()->each(function ($p) {
            $p->created_at = $p->created_at->toIso8601String();
        });

        $stats = [
            'pendingCount' => Pembayaran::where('status', 'pending')->count(),
            'pendingNominal' => (float) Pembayaran::where('status', 'pending')->sum('jumlah_bayar'),
            'expiredCount' => Pembayaran::where('status', 'expired')->count(),
            'confirmedTodayCount' => Pembayaran::where('status', 'dikonfirmasi')->whereDate('updated_at', today())->count(),
            'confirmedTodayNominal' => (float) Pembayaran::where('status', 'dikonfirmasi')->whereDate('updated_at', today())->sum('jumlah_bayar'),
        ];

        return Inertia::render('Admin/Verifikasi/Index', [
            'pembayarans' => $pembayarans,
            'expiredPembayarans' => $expiredPembayarans,
            'expiredCount' => $stats['expiredCount'],
            'stats' => $stats,
            'filters' => $request->only(['search', 'search_expired']),
        ]);
    }

    public function ringkasan()
    {
        $totalMenunggu = Pembayaran::where('status', 'pending')->count();
        $totalDikonfirmasi = Pembayaran::where('status', 'dikonfirmasi')->count();
        $totalDitolak = Pembayaran::where('status', 'ditolak')->count();
        $totalPendapatan = Pembayaran::where('status', 'dikonfirmasi')->sum('jumlah_bayar');

        return Inertia::render('Admin/Verifikasi/Ringkasan', [
            'summary' => [
                'totalPembayaran' => $totalMenunggu + $totalDikonfirmasi + $totalDitolak,
                'pending' => $totalMenunggu,
                'confirmed' => $totalDikonfirmasi,
                'rejected' => $totalDitolak,
                'totalPendapatan' => (float) $totalPendapatan,
            ],
        ]);
    }
}
