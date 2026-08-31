<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class UktInvoiceService
{
    public function build(int $pembayaranId): ?array
    {
        $pembayaran = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])->find($pembayaranId);
        if (!$pembayaran) {
            return null;
        }

        $tagihan = $pembayaran->tagihan;
        $mahasiswa = $tagihan->mahasiswa;
        $metode = $pembayaran->metodePembayaran;

        $amount = (int) $pembayaran->jumlah_bayar;
        $isPaid = strtolower($pembayaran->status) === 'paid' || $pembayaran->verified_at !== null;
        $isExpired = strtolower($pembayaran->status) === 'expired';

        if ($isPaid) {
            $statusLabel = 'Paid';
            $statusColor = '#16a34a';
        } elseif ($isExpired) {
            $statusLabel = 'Expired';
            $statusColor = '#ef4444';
        } else {
            $statusLabel = 'Pending';
            $statusColor = '#d97706';
        }

        $paidAtRaw = $pembayaran->verified_at ?? $pembayaran->updated_at ?? $pembayaran->created_at;
        $invoiceDateRaw = $paidAtRaw ?? $pembayaran->created_at;

        $invoiceNumber = 'INV-UKT-' . str_pad((string) $pembayaran->id, 6, '0', STR_PAD_LEFT);
        $tahunAkademik = $tagihan->tahun_akademik ?? '';
        $semester = (int) $tagihan->semester;
        // SemesterHelper: ganjil = odd (1,3,5...), genap = even (2,4,6...) — SIAKAD flag 1=ganjil, 2=genap
        $semesterLabel = $semester % 2 === 1 ? 'Ganjil' : 'Genap';

        $description = 'Pembayaran UKT ' . $tahunAkademik . ' - ' . $semesterLabel;

        $vaNumber = $pembayaran->va_number ?? '';
        $vaExpiredAt = $pembayaran->va_expired_at ?? null;

        $verificationUrl = URL::signedRoute('verify.invoice', ['pembayaran' => $pembayaran->id]);

        return [
            'invoice_number' => $invoiceNumber,
            'document_title' => 'Invoice UKT ' . $mahasiswa->nama_lengkap,
            'file_name' => 'invoice-ukt-' . $pembayaran->id . '.pdf',
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'institution' => $this->resolveInstitutionPayload(),
            'logo_url' => $this->resolveLogoUrl(),
            'colors' => $this->resolveColorSettings(),
            'verification_url' => $verificationUrl,
            'qr_code' => $this->generateQrDataUri($verificationUrl),
            'student' => [
                'name' => $mahasiswa->nama_lengkap ?? 'Mahasiswa',
                'nim' => $mahasiswa->nim ?? '',
                'email' => $mahasiswa->user?->email ?? '',
                'jurusan' => $mahasiswa->jurusan ?? '',
                'angkatan' => $mahasiswa->angkatan ?? '',
                'program_studi' => $mahasiswa->jurusan ?? '',
            ],
            'item' => [
                'description' => $description,
                'meta' => array_values(array_filter([
                    'Semester: ' . $semester . ' (' . $semesterLabel . ')',
                    'Tahun Akademik: ' . $tahunAkademik,
                ])),
                'amount' => $amount,
                'amount_label' => $this->formatMoney($amount),
            ],
            'subtotal' => $amount,
            'subtotal_label' => $this->formatMoney($amount),
            'total' => $amount,
            'total_label' => $this->formatMoney($amount),
            'payment' => [
                'paid_at_label' => $this->formatDate($paidAtRaw, true),
                'method' => $metode?->nama_metode ?? 'Virtual Account',
                'method_logo' => $metode?->logo ?? '',
                'method_instruksi' => $metode?->instruksi ?? '',
                'va_number' => $vaNumber,
                'va_expired_at' => $vaExpiredAt ? $this->formatDate($vaExpiredAt) : '',
                'reference_no' => $pembayaran->id,
                'r_code' => '',
                'message' => $pembayaran->catatan_admin ?? '',
            ],
            'invoice_date_label' => $this->formatDate($invoiceDateRaw),
        ];
    }

    private function resolveInstitutionPayload(): array
    {
        $setting = \App\Models\ThemeSetting::instance();

        return [
            'name' => $setting->invoice_institution_name ?? config('app.name', 'UKT System'),
            'address' => $setting->invoice_institution_address ?? '',
            'phone' => $setting->invoice_institution_phone ?? '',
            'email' => $setting->invoice_institution_email ?? '',
            'website' => $setting->invoice_institution_website ?? config('app.url', ''),
        ];
    }

    private function resolveHeaderImageUrl(): string
    {
        $setting = \App\Models\ThemeSetting::instance();
        $headerImage = $setting->invoice_header_image ?? '';

        if ($headerImage === '') {
            return '';
        }

        if (filter_var($headerImage, FILTER_VALIDATE_URL)) {
            return $headerImage;
        }

        return asset(ltrim($headerImage, '/'));
    }

    /**
     * Resolve the invoice logo from theme settings.
     * Local storage files are converted to data URIs so dompdf can embed them.
     */
    private function resolveLogoUrl(): string
    {
        $setting = \App\Models\ThemeSetting::instance();
        $logo = $setting->invoice_logo ?? '';

        if ($logo !== '') {
            // Already an embedded data URI
            if (str_starts_with($logo, 'data:')) {
                return $logo;
            }

            // Local file (storage or any public path) -> embed as data URI for dompdf
            if (!filter_var($logo, FILTER_VALIDATE_URL)) {
                $path = public_path(ltrim($logo, '/'));
                if (is_file($path)) {
                    // Skip oversized files: embedding them as base64 makes dompdf extremely slow
                    if (filesize($path) > 300 * 1024) {
                        return '';
                    }

                    $mime = mime_content_type($path) ?: 'image/png';
                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                }

                return $logo;
            }

            return $logo;
        }

        // Fallback: bundled logo shipped with the app
        return $this->resolveAssetDataUri('images/logo_ubg_black.png');
    }

    private function resolveColorSettings(): array
    {
        $setting = \App\Models\ThemeSetting::instance();

        return [
            'content_bg' => $setting->content_bg ?? '#f8fafc',
            'content_text' => $setting->content_text ?? '#1e293b',
            'card_bg' => $setting->card_bg ?? '#ffffff',
            'card_border' => $setting->card_border ?? '#e2e8f0',
            'primary' => $setting->primary_color ?? '#4f46e5',
        ];
    }

    private function formatMoney(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function formatDate(?string $date, bool $withTime = false): string
    {
        if (!$date) {
            return '-';
        }

        try {
            $dt = \Carbon\Carbon::parse($date);
            return $withTime
                ? $dt->translatedFormat('d F Y H:i')
                : $dt->translatedFormat('d F Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    private function resolveAssetDataUri(string $path): string
    {
        $fullPath = public_path($path);
        if (!file_exists($fullPath)) {
            return '';
        }

        $mime = mime_content_type($fullPath);
        $base64 = base64_encode(file_get_contents($fullPath));
        return 'data:' . $mime . ';base64,' . $base64;
    }

    private function generateQrDataUri(string $url): string
    {
        try {
            $qr = new QrCode(
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            );
            $writer = new PngWriter();
            $result = $writer->write($qr);
            return $result->getDataUri();
        } catch (\Throwable $e) {
            return '';
        }
    }
}
