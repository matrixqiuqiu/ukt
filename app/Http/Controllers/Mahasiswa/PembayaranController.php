<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use App\Models\Pembayaran;
use App\Models\RiwayatTransaksi;
use App\Models\Tagihan;
use App\Services\VaNtbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PembayaranController extends Controller
{
    public function store(Request $request)
    {
        file_put_contents(storage_path('logs/store-entry.txt'), json_encode([
            'payment_type' => $request->input('payment_type', 'transfer'),
            'tagihan_id' => $request->input('tagihan_id'),
            'metode_id' => $request->input('metode_pembayaran_id'),
            'jumlah' => $request->input('jumlah_bayar'),
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toDateTimeString(),
        ], JSON_PRETTY_PRINT));

        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $paymentType = $request->input('payment_type', 'transfer');

        if ($paymentType === 'virtual_account') {
            $validated = $request->validate([
                'tagihan_id' => 'required|exists:tagihans,id',
                'metode_pembayaran_id' => 'required|exists:metode_pembayarans,id',
                'jumlah_bayar' => 'required|numeric|min:1',
                'payment_type' => 'required|in:virtual_account',
            ]);

            $tagihan = Tagihan::where('mahasiswa_id', $mahasiswa->id)
                ->with('mahasiswa')
                ->findOrFail($validated['tagihan_id']);

            $metode = MetodePembayaran::findOrFail($validated['metode_pembayaran_id']);

            $vaService = new VaNtbService();
            $vaSuffix = $vaService->generateVaNumber(
                $metode->no_rekening ?? '',
                $mahasiswa->nim,
                $tagihan->semester
            );

            $email = $mahasiswa->user?->email ?? '';
            $description = 'Pembayaran UKT ' . $tagihan->keterangan;

            $vaResult = $vaService->createVa(
                $vaSuffix,
                $mahasiswa->nama_lengkap,
                (float) $validated['jumlah_bayar'],
                $email,
                '081234567890',
                $description
            );

            // If VA already exists (rCode 004), try update instead
            $rcode = $vaResult['rcode'] ?? '';
            $vaAlreadyExist = $rcode === '004' || stripos($vaResult['message'] ?? '', 'already exist') !== false;

            if ($vaAlreadyExist) {
                $fullVaNumber = $vaResult['data']['va'] ?? '';
                if ($fullVaNumber !== '') {
                    $updateResult = $vaService->updateVa(
                        $fullVaNumber,
                        $mahasiswa->nama_lengkap,
                        (float) $validated['jumlah_bayar'],
                        $email,
                        '081234567890',
                        $description
                    );
                    if ($updateResult['success'] && isset($updateResult['data']['va'])) {
                        $vaResult = $updateResult;
                    } else {
                        return back()->withErrors([
                            'payment' => 'VA sudah ada di bank tetapi update gagal. Silakan hubungi admin.',
                        ]);
                    }
                } else {
                    return back()->withErrors([
                        'payment' => 'VA sudah ada di bank tetapi nomor VA tidak ditemukan untuk update. Silakan hubungi admin.',
                    ]);
                }
            }

            // If generic error (rCode 999), retry with last 5 digits of NIM
            if ($rcode === '999') {
                $newVaSuffix = $vaService->generateVaNumberFromLast5($mahasiswa->nim);
                $vaResult = $vaService->createVa(
                    $newVaSuffix,
                    $mahasiswa->nama_lengkap,
                    (float) $validated['jumlah_bayar'],
                    $email,
                    '081234567890',
                    $description
                );
            }

            if (!$vaResult['success'] || !isset($vaResult['data']['va'])) {
                return back()->withErrors([
                    'payment' => 'Gagal membuat VA dari bank: ' . ($vaResult['message'] ?? 'ACCESS DENIED'),
                ]);
            }

            $finalVaNumber = $vaResult['data']['va'];
            $vaExpiredAt = $vaResult['data']['datetime_expired']
                ?? $vaResult['data']['expired_at']
                ?? null;

            // Jika expired_at tidak ada atau sudah lewat, hitung dari .env defaults
            if (!$vaExpiredAt || \Carbon\Carbon::parse($vaExpiredAt)->lte(now())) {
                $vaExpiredAt = now()
                    ->addDays((int) env('NTB_VA_DEFAULT_EXPIRED_DAYS', 0))
                    ->addHours((int) env('NTB_VA_DEFAULT_EXPIRED_HOURS', 0))
                    ->addMinutes((int) env('NTB_VA_DEFAULT_EXPIRED_MINUTES', 5))
                    ->format('Y-m-d H:i:s');
            }

            DB::beginTransaction();

            try {
                $pembayaran = Pembayaran::create([
                    'tagihan_id' => $tagihan->id,
                    'metode_pembayaran_id' => $validated['metode_pembayaran_id'],
                    'jumlah_bayar' => $validated['jumlah_bayar'],
                    'nama_pengirim' => $mahasiswa->nama_lengkap,
                    'va_number' => $finalVaNumber,
                    'va_expired_at' => $vaExpiredAt,
                    'status' => 'pending',
                ]);

                RiwayatTransaksi::create([
                    'pembayaran_id' => $pembayaran->id,
                    'user_id' => $user->id,
                    'aksi' => 'va_dibuat',
                    'keterangan' => 'Virtual Account ' . $finalVaNumber . ' dibuat via ' . $metode->nama_metode,
                ]);

                DB::commit();

                return redirect()->route('mahasiswa.pembayaran.show', $pembayaran->id);
            } catch (\Exception $e) {
                DB::rollBack();

                return back()->withErrors(['payment' => 'Gagal membuat VA: ' . $e->getMessage()]);
            }
        } else {
            $validated = $request->validate([
                'tagihan_id' => 'required|exists:tagihans,id',
                'metode_pembayaran_id' => 'required|exists:metode_pembayarans,id',
                'jumlah_bayar' => 'required|numeric|min:1',
                'nama_pengirim' => 'required|string|max:255',
                'bukti_pembayaran' => 'required|file|image|mimes:jpg,jpeg,png|max:5120',
            ]);

            $tagihan = Tagihan::where('mahasiswa_id', $mahasiswa->id)->findOrFail($validated['tagihan_id']);
            $buktiPath = $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public');

            DB::beginTransaction();

            try {
                $pembayaran = Pembayaran::create([
                    'tagihan_id' => $tagihan->id,
                    'metode_pembayaran_id' => $validated['metode_pembayaran_id'],
                    'jumlah_bayar' => $validated['jumlah_bayar'],
                    'nama_pengirim' => $validated['nama_pengirim'],
                    'bukti_pembayaran' => $buktiPath,
                    'status' => 'pending',
                ]);

                RiwayatTransaksi::create([
                    'pembayaran_id' => $pembayaran->id,
                    'user_id' => $user->id,
                    'aksi' => 'pembayaran_dibuat',
                    'keterangan' => 'Pembayaran diajukan oleh mahasiswa',
                ]);

                DB::commit();

                return redirect()->route('mahasiswa.pembayaran.show', $pembayaran->id);
            } catch (\Exception $e) {
                DB::rollBack();

                return back()->withErrors(['payment' => 'Gagal mengajukan pembayaran: ' . $e->getMessage()]);
            }
        }
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $pembayaran = Pembayaran::whereHas('tagihan', function ($q) use ($mahasiswa) {
                $q->where('mahasiswa_id', $mahasiswa->id);
            })
            ->with(['tagihan.mahasiswa', 'metodePembayaran', 'riwayatTransaksi'])
            ->findOrFail($id);

        // Ensure metodePembayaran is loaded properly
        if (!$pembayaran->relationLoaded('metodePembayaran') && $pembayaran->metode_pembayaran_id) {
            $pembayaran->load('metodePembayaran');
        }

        if ($pembayaran->created_at instanceof \Carbon\Carbon) {
            $pembayaran->created_at = $pembayaran->created_at->toIso8601String();
        }
        if ($pembayaran->va_expired_at instanceof \Carbon\Carbon) {
            $pembayaran->va_expired_at = $pembayaran->va_expired_at->toIso8601String();
        }
        if ($pembayaran->verified_at instanceof \Carbon\Carbon) {
            $pembayaran->verified_at = $pembayaran->verified_at->toIso8601String();
        }

        // Beasiswa untuk tagihan ini
        $beasiswaAssignment = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->where('tagihan_id', $pembayaran->tagihan_id)
            ->with(['beasiswa.jenisBeasiswa', 'beasiswa.tahunAkademik'])
            ->first();
        if (!$beasiswaAssignment) {
            $ta = $pembayaran->tagihan;
            $beasiswaAktif = app(\App\Services\BeasiswaService::class)->cariBeasiswaAktif($mahasiswa, $ta->tahun_akademik, $ta->semester);
            if ($beasiswaAktif) {
                $beasiswaAssignment = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                    ->where('beasiswa_id', $beasiswaAktif->id)
                    ->with(['beasiswa.jenisBeasiswa', 'beasiswa.tahunAkademik'])
                    ->first();
            }
        }

        $beasiswaData = null;
        if ($beasiswaAssignment) {
            $b = $beasiswaAssignment->beasiswa;
            $beasiswaData = [
                'kode' => $b->kode ?? '-',
                'nama' => $b->nama_beasiswa ?? '-',
                'jenis' => $beasiswaAssignment->beasiswa->jenisBeasiswa?->nama ?? $b->jenis ?? '-',
                'tipe' => $b->tipe_diskon ?? '-',
                'nilai' => $b->nilai_diskon ?? 0,
                'diskon' => $beasiswaAssignment->diskon_diterapkan ?? 0,
                'status' => $beasiswaAssignment->status ?? '-',
            ];
        }

        return Inertia::render('Mahasiswa/Pembayaran/Show', [
            'pembayaran' => $pembayaran,
            'beasiswa' => $beasiswaData,
            'vaExpiredAt' => now()
                ->addDays((int) env('NTB_VA_DEFAULT_EXPIRED_DAYS', 0))
                ->addHours((int) env('NTB_VA_DEFAULT_EXPIRED_HOURS', 0))
                ->addMinutes((int) env('NTB_VA_DEFAULT_EXPIRED_MINUTES', 5))
                ->toIso8601String(),
        ]);
    }

    public function checkStatus(Request $request, $id)
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $pembayaran = Pembayaran::whereHas('tagihan', function ($q) use ($mahasiswa) {
                $q->where('mahasiswa_id', $mahasiswa->id);
            })
            ->with(['tagihan', 'metodePembayaran'])
            ->findOrFail($id);

        // LANGKAH 1: Validasi Database Lokal Prioritas
        // Jika status di database sudah 'Lunas' / 'Paid', JANGAN hit API Bank
        if ($pembayaran->status === 'dikonfirmasi' || $pembayaran->tagihan->status === 'sudah_dibayar') {
            return response()->json([
                'success' => true,
                'status' => 'paid',
                'message' => 'Pembayaran sudah terkonfirmasi',
                'data' => [
                    'va' => $pembayaran->va_number,
                    'status' => 'paid',
                    'amount' => $pembayaran->jumlah_bayar,
                ],
            ]);
        }

        if (!$pembayaran->va_number) {
            return back()->withErrors(['status' => 'VA number tidak ditemukan']);
        }

        // LANGKAH 1b: VA sudah lewat batas waktu -> tandai expired
        if ($pembayaran->status === 'pending' && $pembayaran->va_expired_at && now()->gte($pembayaran->va_expired_at)) {
            $pembayaran->update(['status' => 'expired']);

            return response()->json([
                'success' => true,
                'status' => 'expired',
                'message' => 'VA sudah melewati batas waktu pembayaran',
                'data' => [
                    'va' => $pembayaran->va_number,
                    'status' => 'expired',
                ],
            ]);
        }

        // LANGKAH 2: Implementasi Saklar "PRODUCTION" dari .env
        $isProduction = filter_var(env('PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);

        if (!$isProduction) {
            // === MODE TESTING (Bypass) ===
            // Endpoint Bank untuk test bayar sudah dimatikan, jadi jangan tembak API Cek Status Bank di mode ini.
            // Tapi tetap cek database apakah flag sudah pernah dilakukan
            
            // LANGKAH 2a: Cek database status dulu — jika sudah dikonfirmasi, return paid
            if ($pembayaran->status === 'dikonfirmasi' || $pembayaran->tagihan->status === 'sudah_dibayar') {
                return response()->json([
                    'success' => true,
                    'status' => 'paid',
                    'message' => 'Pembayaran berhasil dikonfirmasi',
                    'data' => [
                        'va' => $pembayaran->va_number,
                        'status' => 'paid',
                        'amount' => $pembayaran->jumlah_bayar,
                    ],
                ]);
            }
            
            // LANGKAH 2b: Cek apakah flag sudah pernah dilakukan untuk VA ini (di log VaApiLog)
            // Note: endpoint name is 'test-flag' (from testEndpoint), 'flagVa' (from VaNtbService), or 'simulate-payment' (from OperationsController)
            $hasFlagged = \App\Models\VaApiLog::whereIn('endpoint', ['test-flag', 'flagVa', 'simulate-payment'])
                ->where('success', true)
                ->whereJsonContains('request_data', ['va' => $pembayaran->va_number])
                ->exists();

            if ($hasFlagged) {
                // Flag sudah dilakukan, update database dan return success
                DB::beginTransaction();
                try {
                    $pembayaran->update([
                        'status' => 'dikonfirmasi',
                        'verified_at' => now(),
                    ]);

                    $pembayaran->tagihan->update(['status' => 'sudah_dibayar']);

                    RiwayatTransaksi::create([
                        'pembayaran_id' => $pembayaran->id,
                        'user_id' => $user->id,
                        'aksi' => 'pembayaran_dikonfirmasi',
                        'keterangan' => 'Pembayaran VA terkonfirmasi via flag (mode testing)',
                    ]);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                }

                return response()->json([
                    'success' => true,
                    'status' => 'paid',
                    'message' => 'Pembayaran berhasil dikonfirmasi via flag',
                    'data' => [
                        'va' => $pembayaran->va_number,
                        'status' => 'paid',
                        'amount' => $pembayaran->jumlah_bayar,
                    ],
                ]);
            }

            // Belum ada flag, kembalikan response pending
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Mode Testing Aktif: Status lokal masih pending. Silakan lakukan simulasi Flag Lunas melalui dashboard Admin.',
                'data' => [
                    'va' => $pembayaran->va_number,
                    'status' => 'pending',
                ],
            ]);
        } else {
            // === MODE PRODUCTION (Live) ===
            // 1. Lakukan request Guzzle POST ke API Cek Status Bank.
            // 2. Gunakan URL dari env, cek dokumen bank apakah menggunakan env('URL_URL_VANTB_CEKSTATUS') atau env('URL_URL_VANTB_INQVA').
            // 3. Pastikan pembentukan Signature JSON tetap mempertahankan (string) type-casting dan urutan key yang ketat!
            // 4. Jika response Bank = "Lunas", update database tagihan lokal menjadi "Lunas", lalu return success.
            // 5. Jika response Bank = "Belum", return pending.

            $vaService = new VaNtbService();
            $result = $vaService->cekStatus($pembayaran->va_number);

            if ($result['success'] && isset($result['data'])) {
                $data = $result['data'];
                $paymentStatus = $data['status'] ?? null;

                if ($paymentStatus === 'paid' || $paymentStatus === 'lunas') {
                    DB::beginTransaction();
                    try {
                        $pembayaran->update([
                            'status' => 'dikonfirmasi',
                            'verified_at' => now(),
                        ]);

                        $pembayaran->tagihan->update(['status' => 'sudah_dibayar']);

                        RiwayatTransaksi::create([
                            'pembayaran_id' => $pembayaran->id,
                            'user_id' => $user->id,
                            'aksi' => 'pembayaran_dikonfirmasi',
                            'keterangan' => 'Pembayaran VA terkonfirmasi otomatis oleh sistem bank',
                        ]);

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                    }
                }

                return response()->json([
                    'success' => $result['success'],
                    'status' => $result['data']['status'] ?? null,
                    'message' => $result['message'],
                    'data' => $result['data'],
                ]);
            }

            return response()->json([
                'success' => $result['success'],
                'status' => 'pending',
                'message' => $result['message'] ?? 'Gagal mengecek status pembayaran',
                'data' => $result['data'] ?? null,
            ]);
        }
    }

    public function riwayat(Request $request)
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $riwayat = Pembayaran::whereHas('tagihan', function ($q) use ($mahasiswa) {
                $q->where('mahasiswa_id', $mahasiswa->id);
            })
            ->with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $riwayat->getCollection()->transform(function ($p) {
            $p->created_at = $p->created_at->toIso8601String();
            return $p;
        });

        return Inertia::render('Mahasiswa/Riwayat/Index', [
            'riwayat' => $riwayat,
        ]);
    }
}
