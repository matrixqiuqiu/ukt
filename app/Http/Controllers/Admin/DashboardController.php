<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
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

        $recentPayments = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'created_at' => $p->created_at->toIso8601String(),
                    'jumlah_bayar' => $p->jumlah_bayar,
                    'status' => $p->status,
                    'tagihan' => [
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
            ],
            'recentPayments' => $recentPayments,
        ]);
    }
}
