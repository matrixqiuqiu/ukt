<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispensasi;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalMahasiswa = Mahasiswa::count();
        $pendingPayments = Pembayaran::where('status', 'pending')->count();
        $confirmedPayments = Pembayaran::where('status', 'dikonfirmasi')->count();
        $totalPendapatan = Pembayaran::where('status', 'dikonfirmasi')->sum('jumlah_bayar');
        $totalTagihan = Tagihan::count();
        $unpaidTagihan = Tagihan::where('status', '!=', 'sudah_dibayar')->count();
        $dispensasiPending = Dispensasi::where('status', 'pending')->count();

        // Payment methods breakdown
        $vaCount = Pembayaran::whereNotNull('va_number')->where('status', 'dikonfirmasi')->count();
        $manualCount = Pembayaran::whereNull('va_number')->where('status', 'dikonfirmasi')->count();
        $vaTotal = (float) Pembayaran::whereNotNull('va_number')->where('status', 'dikonfirmasi')->sum('jumlah_bayar');
        $manualTotal = (float) Pembayaran::whereNull('va_number')->where('status', 'dikonfirmasi')->sum('jumlah_bayar');

        // Recent Payments
        $recentPayments = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'created_at' => $p->created_at->toIso8601String(),
                    'jumlah_bayar' => $p->jumlah_bayar,
                    'status' => $p->status,
                    'metode_nama' => $p->metodePembayaran->nama_metode ?? ($p->va_number ? 'Virtual Account' : 'Transfer Manual'),
                    'is_va' => !empty($p->va_number),
                    'tagihan' => [
                        'semester' => $p->tagihan->semester ?? '-',
                        'tahun_akademik' => $p->tagihan->tahun_akademik ?? '-',
                        'mahasiswa' => [
                            'nama_lengkap' => $p->tagihan->mahasiswa->nama_lengkap ?? '-',
                            'nim' => $p->tagihan->mahasiswa->nim ?? '-',
                        ],
                    ],
                ];
            });

        // Quick Pending Verifications
        $pendingVerifications = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'created_at' => $p->created_at->toIso8601String(),
                    'jumlah_bayar' => $p->jumlah_bayar,
                    'nama_pengirim' => $p->nama_pengirim,
                    'bukti_pembayaran' => $p->bukti_pembayaran,
                    'metode_nama' => $p->metodePembayaran->nama_metode ?? 'Transfer Bank',
                    'tagihan' => [
                        'semester' => $p->tagihan->semester ?? '-',
                        'mahasiswa' => [
                            'nama_lengkap' => $p->tagihan->mahasiswa->nama_lengkap ?? '-',
                            'nim' => $p->tagihan->mahasiswa->nim ?? '-',
                        ],
                    ],
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalMahasiswa' => $totalMahasiswa,
                'pendingPayments' => $pendingPayments,
                'confirmedPayments' => $confirmedPayments,
                'totalPendapatan' => (float) $totalPendapatan,
                'totalTagihan' => $totalTagihan,
                'unpaidTagihan' => $unpaidTagihan,
                'dispensasiPending' => $dispensasiPending,
                'vaCount' => $vaCount,
                'manualCount' => $manualCount,
                'vaTotal' => $vaTotal,
                'manualTotal' => $manualTotal,
            ],
            'recentPayments' => $recentPayments,
            'pendingVerifications' => $pendingVerifications,
        ]);
    }
}
