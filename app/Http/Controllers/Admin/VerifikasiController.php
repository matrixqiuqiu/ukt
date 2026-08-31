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

        $pembayarans = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $pembayarans->getCollection()->transform(function ($p) {
            $p->created_at = $p->created_at->toIso8601String();
            return $p;
        });

        $expiredPembayarans = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->where('status', 'expired')
            ->latest()
            ->limit(10)
            ->get()
            ->each(function ($p) {
                $p->created_at = $p->created_at->toIso8601String();
            });

        return Inertia::render('Admin/Verifikasi/Index', [
            'pembayarans' => $pembayarans,
            'expiredPembayarans' => $expiredPembayarans,
            'expiredCount' => Pembayaran::where('status', 'expired')->count(),
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
