<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\SemesterAktif;
use App\Models\Tagihan;
use App\Models\MetodePembayaran;
use App\Models\Pembayaran;
use App\Services\UktInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagihanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $tagihans = Tagihan::where('mahasiswa_id', $mahasiswa->id)
            ->with(['pembayarans'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Inject beasiswa per tagihan
        $beasiswaMap = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->with(['beasiswa.jenisBeasiswa'])
            ->get()->keyBy('tagihan_id');
        $tagihans->getCollection()->transform(function ($t) use ($beasiswaMap) {
            $bm = $beasiswaMap->get($t->id);
            if ($bm) {
                $t->setAttribute('beasiswa', [
                    'kode' => $bm->beasiswa->kode,
                    'nama' => $bm->beasiswa->nama_beasiswa,
                    'jenis' => $bm->beasiswa->jenisBeasiswa->nama ?? $bm->beasiswa->jenis,
                    'diskon' => $bm->diskon_diterapkan,
                    'tipe' => $bm->beasiswa->tipe_diskon,
                    'nilai' => $bm->beasiswa->nilai_diskon,
                ]);
            }
            return $t;
        });

        return Inertia::render('Mahasiswa/Tagihan/Index', [
            'tagihans' => $tagihans,
            'semesterAktif' => SemesterAktif::instance(),
        ]);
    }

    public function show(Request $request, $id): Response
    {
        $user = $request->user();
        $mahasiswa = $user->getMahasiswaByNim();

        $tagihan = Tagihan::where('mahasiswa_id', $mahasiswa->id)
            ->with(['pembayarans.metodePembayaran'])
            ->findOrFail($id);

        $metodePembayarans = MetodePembayaran::where('status_aktif', true)->get();

        $beasiswa = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->where('tagihan_id', $tagihan->id)
            ->with(['beasiswa.jenisBeasiswa'])
            ->first();
        if (!$beasiswa) {
            $beasiswa = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('beasiswa', fn($q)=>$q->where('status_aktif',true))
                ->with(['beasiswa.jenisBeasiswa'])
                ->first();
        }

        return Inertia::render('Mahasiswa/Tagihan/Show', [
            'tagihan' => $tagihan,
            'metodePembayarans' => $metodePembayarans,
            'mahasiswa' => $mahasiswa,
            'beasiswa' => $beasiswa ? [
                'kode' => $beasiswa->beasiswa->kode,
                'nama' => $beasiswa->beasiswa->nama_beasiswa,
                'jenis' => $beasiswa->beasiswa->jenisBeasiswa->nama ?? $beasiswa->beasiswa->jenis,
                'diskon' => $beasiswa->diskon_diterapkan,
                'tipe' => $beasiswa->beasiswa->tipe_diskon,
                'nilai' => $beasiswa->beasiswa->nilai_diskon,
                'status' => $beasiswa->status,
            ] : null,
            'vaExpiredAt' => now()
                ->addDays((int) env('NTB_VA_DEFAULT_EXPIRED_DAYS', 0))
                ->addHours((int) env('NTB_VA_DEFAULT_EXPIRED_HOURS', 0))
                ->addMinutes((int) env('NTB_VA_DEFAULT_EXPIRED_MINUTES', 5))
                ->toIso8601String(),
        ]);
    }

    public function invoice(Request $request, $tagihanId): Response
    {
        $tagihan = $this->resolveTagihan($request, $tagihanId);

        $pembayaran = $this->resolvePembayaranForInvoice($tagihan);

        if (!$pembayaran) {
            return back()->with('error', 'Tidak ada pembayaran untuk tagihan ini.');
        }

        $theme = \App\Models\ThemeSetting::instance();
        $invoiceData = (new UktInvoiceService())->build($pembayaran->id);

        return Inertia::render('Mahasiswa/Pembayaran/Invoice', [
            'pembayaran' => $pembayaran,
            'tagihan' => $tagihan,
            'mahasiswa' => $tagihan->mahasiswa,
            'canPrint' => true,
            'institution' => [
                'name' => $theme->invoice_institution_name ?? config('app.name', 'UKT System'),
                'address' => $theme->invoice_institution_address ?? '',
                'phone' => $theme->invoice_institution_phone ?? '',
                'email' => $theme->invoice_institution_email ?? '',
                'website' => $theme->invoice_institution_website ?? config('app.url', ''),
            ],
            'header_image' => $theme->invoice_header_image
                ? (str_starts_with($theme->invoice_header_image, 'http')
                    ? $theme->invoice_header_image
                    : asset(ltrim($theme->invoice_header_image, '/')))
                : null,
            'verificationUrl' => $invoiceData['verification_url'] ?? null,
            'qrCode' => $invoiceData['qr_code'] ?? null,
            'invoiceNumber' => $invoiceData['invoice_number'] ?? null,
        ]);
    }

    public function print(Request $request, $tagihanId)
    {
        $tagihan = $this->resolveTagihan($request, $tagihanId);

        $pembayaran = $this->resolvePembayaranForInvoice($tagihan);

        if (!$pembayaran) {
            return back()->with('error', 'Tidak ada pembayaran untuk tagihan ini.');
        }

        $invoiceData = (new UktInvoiceService())->build($pembayaran->id);

        if (!$invoiceData) {
            return back()->with('error', 'Gagal membangun invoice.');
        }

        $pdf = Pdf::loadView('pdf.invoice', ['data' => $invoiceData])->setPaper('a4');

        return $pdf->download($invoiceData['file_name']);
    }

    /**
     * Resolve tagihan scoped by role: admin can access any tagihan,
     * mahasiswa can only access their own tagihan.
     */
    private function resolveTagihan(Request $request, int $tagihanId): Tagihan
    {
        $query = Tagihan::with(['pembayarans.metodePembayaran']);

        if ($request->user()->role !== 'admin') {
            $query->where('mahasiswa_id', $request->user()->getMahasiswaByNim()->id);
        }

        return $query->findOrFail($tagihanId);
    }

    /**
     * Pick the most relevant payment for the invoice: prefer the confirmed one,
     * otherwise fall back to the most recent payment.
     */
    private function resolvePembayaranForInvoice(Tagihan $tagihan): ?Pembayaran
    {
        $pembayarans = $tagihan->pembayarans->sortByDesc('id');

        return $pembayarans->first(fn ($pembayaran) => $pembayaran->status === 'dikonfirmasi')
            ?? $pembayarans->first();
    }
}
