<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Dispensasi;
use App\Models\DispensasiSetting;
use App\Models\Tagihan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DispensasiController extends Controller
{
    public function index(Request $request): Response
    {
        $mahasiswa = $request->user()->getMahasiswaByNim();

        $dispensasis = Dispensasi::with(['tagihan', 'diprosesOleh'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->latest()
            ->get();

        $tagihans = Tagihan::where('mahasiswa_id', $mahasiswa->id)
            ->where('status', '!=', 'sudah_dibayar')
            ->orderBy('jatuh_tempo')
            ->get()
            ->filter(function (Tagihan $tagihan) {
                $hasConfirmed = $tagihan->pembayarans()
                    ->where('status', 'dikonfirmasi')
                    ->exists();

                return !$hasConfirmed;
            })
            ->map(function (Tagihan $tagihan) {
                $hasPending = $tagihan->dispensasis()
                    ->where('status', 'pending')
                    ->exists();

                $latest = $tagihan->dispensasis()->latest()->first();

                return [
                    'id' => $tagihan->id,
                    'semester' => $tagihan->semester,
                    'tahun_akademik' => $tagihan->tahun_akademik,
                    'nominal' => $tagihan->nominal,
                    'jatuh_tempo' => $tagihan->jatuh_tempo,
                    'status' => $tagihan->status,
                    'hasPending' => $hasPending,
                    'latest_dispensasi_status' => $latest?->status,
                    'latest_dispensasi_tempo_baru' => $latest?->tempo_baru,
                ];
            })
            ->values();

        return Inertia::render('Mahasiswa/Dispensasi/Index', [
            'dispensasis' => $dispensasis,
            'tagihans' => $tagihans,
            'template' => DispensasiSetting::instance(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $mahasiswa = $request->user()->getMahasiswaByNim();

        $validated = $request->validate([
            'tagihan_id' => ['required', 'integer'],
            'alasan' => ['required', 'string', 'max:1000'],
            'tempo_baru' => ['required', 'date', 'after:today'],
        ]);

        $tagihan = Tagihan::where('mahasiswa_id', $mahasiswa->id)->findOrFail($validated['tagihan_id']);

        if ($tagihan->pembayarans()->where('status', 'dikonfirmasi')->exists()) {
            return back()->with('error', 'Tagihan sudah lunas, tidak dapat mengajukan dispensasi.');
        }

        $hasPending = Dispensasi::where('tagihan_id', $tagihan->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return back()->with('error', 'Masih ada pengajuan dispensasi yang menunggu verifikasi untuk tagihan ini.');
        }

        $tempoBaru = $validated['tempo_baru'];
        if ($tempoBaru <= $tagihan->jatuh_tempo->format('Y-m-d')) {
            return back()->with('error', 'Tempo baru harus lebih lambat dari jatuh tempo tagihan saat ini.');
        }

        // Surat fisik diserahkan langsung ke bagian keuangan (tanpa upload file)
        Dispensasi::create([
            'mahasiswa_id' => $mahasiswa->id,
            'tagihan_id' => $tagihan->id,
            'alasan' => $validated['alasan'],
            'tempo_baru' => $tempoBaru,
            'tempo_awal' => $tagihan->jatuh_tempo->format('Y-m-d'),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan dispensasi berhasil dikirim. Jangan lupa serahkan surat fisik bermaterai ke bagian keuangan untuk pengecekan persyaratan.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $setting = DispensasiSetting::instance();

        if (!$setting->template_path || !Storage::disk('public')->exists($setting->template_path)) {
            abort(404, 'Template surat dispensasi belum tersedia.');
        }

        return Storage::disk('public')->download(
            $setting->template_path,
            $setting->template_filename ?: 'template-surat-dispensasi.pdf'
        );
    }
}
