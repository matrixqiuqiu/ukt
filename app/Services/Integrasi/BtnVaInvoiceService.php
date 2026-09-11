<?php

namespace App\Services\Integrasi;

use App\Services\Admin\CommonAdminService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class BtnVaInvoiceService
{
    /** @return array<string,mixed>|null */
    public function build(int $transactionId): ?array
    {
        if ($transactionId <= 0 || ! Schema::hasTable('tb_btnva_transaction')) {
            return null;
        }

        $hasRegisterTable = Schema::hasTable('tb_mhs_register') && Schema::hasColumn('tb_mhs_register', 'kode_mhs');
        $hasProdiTable = Schema::hasTable('tb_prodi') && Schema::hasColumn('tb_prodi', 'kode_prodi');
        $query = DB::table('tb_btnva_transaction as t');
        $columns = [
            't.id',
            't.kode_mhs',
            't.domain',
            't.kode_payment',
            't.payment_item_key',
            't.metode_pembayaran',
            't.va_number',
            't.va_name',
            't.tagihan',
            't.status_bank',
            't.status_message',
            't.bank_r_code',
            't.bank_r_code_description',
            't.request_payload',
            't.response_payload',
            't.expired_at',
            't.paid_at',
            't.created_at',
            't.updated_at',
        ];

        if ($hasRegisterTable) {
            $query->leftJoin('tb_mhs_register as m', 't.kode_mhs', '=', 'm.kode_mhs');
            $columns[] = Schema::hasColumn('tb_mhs_register', 'no_register') ? 'm.no_register' : DB::raw('NULL as no_register');
            $columns[] = Schema::hasColumn('tb_mhs_register', 'kode_daftar_ulang') ? 'm.kode_daftar_ulang' : DB::raw('NULL as kode_daftar_ulang');
            $columns[] = Schema::hasColumn('tb_mhs_register', 'nama') ? 'm.nama' : DB::raw('NULL as nama');
            $columns[] = Schema::hasColumn('tb_mhs_register', 'email') ? 'm.email' : DB::raw('NULL as email');
            $columns[] = Schema::hasColumn('tb_mhs_register', 'no_hp') ? 'm.no_hp' : DB::raw('NULL as no_hp');

            if ($hasProdiTable && Schema::hasColumn('tb_mhs_register', 'kode_prodi_1')) {
                $query->leftJoin('tb_prodi as p', 'm.kode_prodi_1', '=', 'p.kode_prodi');
                $columns[] = Schema::hasColumn('tb_prodi', 'nama_prodi') ? 'p.nama_prodi' : DB::raw('NULL as nama_prodi');
            } else {
                $columns[] = DB::raw('NULL as nama_prodi');
            }
        } else {
            $columns[] = DB::raw('NULL as no_register');
            $columns[] = DB::raw('NULL as kode_daftar_ulang');
            $columns[] = DB::raw('NULL as nama');
            $columns[] = DB::raw('NULL as email');
            $columns[] = DB::raw('NULL as no_hp');
            $columns[] = DB::raw('NULL as nama_prodi');
        }

        $row = $query
            ->where('t.id', $transactionId)
            ->first($columns);
        if (! $row) {
            return null;
        }

        $domain = strtolower(trim((string) ($row->domain ?? '')));
        if (! in_array($domain, ['admisi', 'daftar_ulang'], true)) {
            return null;
        }

        $statusBank = strtolower(trim((string) ($row->status_bank ?? 'pending')));
        $kodeMhs = trim((string) ($row->kode_mhs ?? ''));
        $isPaid = in_array($statusBank, ['paid', 'success', 'settlement', 'lunas'], true);
        $isExpired = in_array($statusBank, ['expired', 'expired_va'], true);

        $parentPaidTime = null;

        if (! $isPaid) {
            if ($domain === 'admisi') {
                $kodePaymentVal = (int) ($row->kode_payment ?? 0);
                if ($kodePaymentVal > 0 && Schema::hasTable('tb_payment')) {
                    $paymentRow = DB::table('tb_payment')
                        ->where('kode_payment', $kodePaymentVal)
                        ->first(['status_proses', 'tanggal_verifikasi', 'updated']);
                    if ($paymentRow && (int) ($paymentRow->status_proses ?? 0) === 1) {
                        $isPaid = true;
                        $parentPaidTime = $paymentRow->tanggal_verifikasi ?? $paymentRow->updated ?? null;
                    }
                }
                if (! $isPaid && Schema::hasTable('tb_mhs_register')) {
                    $registerRow = DB::table('tb_mhs_register')
                        ->whereRaw('TRIM(kode_mhs) = ?', [trim((string) ($row->kode_mhs ?? ''))])
                        ->first(['status_bayar', 'tanggal_verifikasi', 'updated']);
                    if ($registerRow && (int) ($registerRow->status_bayar ?? 0) === 1) {
                        $isPaid = true;
                        $parentPaidTime = $registerRow->tanggal_verifikasi ?? $registerRow->updated ?? null;
                    }
                }
            } elseif ($domain === 'daftar_ulang' && Schema::hasTable('du_tagihan_mhs')) {
                $itemKey = strtolower(trim((string) ($row->payment_item_key ?? '')));
                $duQuery = DB::table('du_tagihan_mhs')
                    ->whereRaw('TRIM(kode_mhs) = ?', [trim((string) ($row->kode_mhs ?? ''))]);
                if ($itemKey !== '' && $itemKey !== 'all') {
                    $duQuery->whereRaw("LOWER(TRIM(COALESCE(payment_item_key, ''))) = ?", [$itemKey]);
                }
                $hasStatusBayar = Schema::hasColumn('du_tagihan_mhs', 'status_bayar');
                $hasStatusVerifikasi = Schema::hasColumn('du_tagihan_mhs', 'status_verifikasi');
                $hasVerifiedAt = Schema::hasColumn('du_tagihan_mhs', 'verified_at');
                $hasUpdatedAt = Schema::hasColumn('du_tagihan_mhs', 'updated_at');

                if ($hasStatusBayar || $hasStatusVerifikasi) {
                    $selectCols = [];
                    if ($hasStatusBayar) $selectCols[] = 'status_bayar';
                    if ($hasStatusVerifikasi) $selectCols[] = 'status_verifikasi';
                    if ($hasVerifiedAt) $selectCols[] = 'verified_at';
                    if ($hasUpdatedAt) $selectCols[] = 'updated_at';

                    $duRecords = $duQuery->get($selectCols);
                    if ($duRecords->isNotEmpty()) {
                        $allPaid = true;
                        $latestTime = null;
                        foreach ($duRecords as $duRecord) {
                            $recordPaid = false;
                            if ($hasStatusBayar && (int) ($duRecord->status_bayar ?? 0) === 1) {
                                $recordPaid = true;
                            }
                            if ($hasStatusVerifikasi && (int) ($duRecord->status_verifikasi ?? 0) === 1) {
                                $recordPaid = true;
                            }
                            if (! $recordPaid) {
                                $allPaid = false;
                                break;
                            }
                            $timeCandidate = ($hasVerifiedAt ? $duRecord->verified_at : null)
                                ?? ($hasUpdatedAt ? $duRecord->updated_at : null);
                            if ($timeCandidate) {
                                if (! $latestTime || $timeCandidate > $latestTime) {
                                    $latestTime = $timeCandidate;
                                }
                            }
                        }
                        if ($allPaid) {
                            $isPaid = true;
                            $parentPaidTime = $latestTime;
                        }
                    }
                }
            }

            if ($isPaid) {
                try {
                    DB::table('tb_btnva_transaction')
                        ->where('id', $transactionId)
                        ->update([
                            'status_bank' => 'paid',
                            'status_message' => 'Pembayaran sinkron otomatis dari status master.',
                            'paid_at' => $parentPaidTime ?? now()->toDateTimeString(),
                            'is_active' => 0,
                            'updated_at' => now()->toDateTimeString(),
                        ]);
                } catch (\Throwable $e) {
                    // Ignore DB write error during PDF generation
                }
            }
        }

        if ($isPaid) {
            $statusLabel = 'Paid';
            $statusColor = '#16a34a'; // Green
        } elseif ($isExpired) {
            $statusLabel = 'Expired';
            $statusColor = '#ef4444'; // Red
        } else {
            $statusLabel = 'Pending';
            $statusColor = '#d97706'; // Amber/Orange
        }

        $requestPayload = $this->decodeJson($row->request_payload ?? null);
        $responsePayload = $this->decodeJson($row->response_payload ?? null);
        $amount = (int) round((float) ($row->tagihan ?? 0));
        $kodePayment = (int) ($row->kode_payment ?? 0);
        $paymentItemKey = strtolower(trim((string) ($row->payment_item_key ?? '')));
        $paymentItemLabel = $this->resolvePaymentItemLabel($paymentItemKey);
        $domainLabel = $domain === 'admisi' ? 'Pendaftaran' : 'Daftar Ulang';
        $identifierLabel = $domain === 'admisi' ? 'Nomor Pendaftaran' : 'Nomor Daftar Ulang';
        $identifierValue = $domain === 'admisi'
            ? trim((string) ($row->no_register ?? ''))
            : $this->firstFilledString([$row->kode_daftar_ulang ?? null, $row->no_register ?? null]);
        $description = $domain === 'admisi'
            ? 'Pembayaran Pendaftaran PMB'
            : 'Pembayaran Daftar Ulang PMB';
        if ($paymentItemLabel !== '') {
            $description .= ' - '.$paymentItemLabel;
        }
        $documentNoRegister = trim((string) ($row->no_register ?? ''));
        if ($documentNoRegister === '') {
            $documentNoRegister = $identifierValue !== '' ? $identifierValue : $kodeMhs;
        }
        $documentStudentName = $this->firstFilledString([$row->nama ?? null, $row->va_name ?? null, 'Calon Mahasiswa']);
        // CommonAdminService hanya ada di aplikasi PMB — fallback lokal bila tidak tersedia
        if (class_exists(CommonAdminService::class)) {
            $documentTitle = app(CommonAdminService::class)->buildDocumentIdentityTitle(
                $documentNoRegister,
                $documentStudentName
            );
            $fileName = app(CommonAdminService::class)->buildDocumentIdentityFileName(
                $documentNoRegister,
                $documentStudentName,
                $kodeMhs !== '' ? $kodeMhs : 'dokumen'
            );
        } else {
            $documentTitle = trim($documentNoRegister . ' - ' . $documentStudentName, ' -');
            $fileName = 'dokumen-' . ($kodeMhs !== '' ? $kodeMhs : 'btnva') . '.pdf';
        }

        $paidAtRaw = $this->firstFilledString([
            $row->paid_at ?? null,
            $parentPaidTime,
            data_get($responsePayload, 'payload.datetime_payment'),
            data_get($responsePayload, 'datetime_payment'),
            data_get($responsePayload, 'data.datetime_payment'),
            data_get($responsePayload, 'payload.paid_at'),
            data_get($responsePayload, 'paid_at'),
            data_get($responsePayload, 'payment_time'),
        ]);

        if ($isPaid && $paidAtRaw === '') {
            $paidAtRaw = $this->firstFilledString([
                $row->updated_at ?? null,
                $row->created_at ?? null,
                now()->toDateTimeString(),
            ]);
        }
        $invoiceDateRaw = $this->firstFilledString([
            $paidAtRaw,
            $row->updated_at ?? null,
            $row->created_at ?? null,
        ]);
        $referenceNo = $this->firstFilledString([
            data_get($responsePayload, 'payload.reference_no'),
            data_get($responsePayload, 'reference_no'),
            data_get($responsePayload, 'data.reference_no'),
            data_get($responsePayload, 'payload.ref_no'),
            data_get($responsePayload, 'ref_no'),
            data_get($requestPayload, 'reference_no'),
        ]);
        $channel = $this->firstFilledString([
            data_get($responsePayload, 'payload.channel'),
            data_get($responsePayload, 'channel'),
            data_get($responsePayload, 'data.channel'),
        ]);
        $bankMessage = $this->firstFilledString([
            data_get($responsePayload, 'payload.message'),
            data_get($responsePayload, 'message'),
            data_get($responsePayload, 'data.message'),
            $row->status_message ?? null,
            $row->bank_r_code_description ?? null,
        ]);
        $method = $channel !== '' ? 'BTN VA - '.$channel : 'BTN Virtual Account';
        $invoiceNumber = 'INV-PMB-BTNVA-'.str_pad((string) ($row->id ?? 0), 6, '0', STR_PAD_LEFT);
        $appUrl = config('app.url');
        if (!empty($appUrl)) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        }
        $transactionId = (int) ($row->id ?? 0);

        // Return signed route to verify.invoice or default URL
        $verificationUrl = URL::signedRoute('verify.invoice', ['transaction' => $transactionId]);
        $invoiceUrl = URL::signedRoute('admin.maintenance.payment-gateway.btnva.invoice', ['transaction' => $transactionId]);

        $kwitansiUrl = '';
        if ($kodeMhs !== '') {
            if ($domain === 'admisi') {
                $kwitansiParams = [
                    'kodeMhs' => $kodeMhs,
                    'preview' => 1,
                ];
                if ($kodePayment > 0) {
                    $kwitansiParams['kodePayment'] = $kodePayment;
                }
                $kwitansiUrl = URL::signedRoute('daftar.receipt', $kwitansiParams);
            } else {
                $kwitansiParams = [
                    'kodeMhs' => $kodeMhs,
                ];
                if ($paymentItemKey !== '' && $paymentItemKey !== 'all') {
                    $kwitansiParams['stage'] = $paymentItemKey;
                }
                $kwitansiUrl = URL::signedRoute('daftar.daftar-ulang-payment-receipt', $kwitansiParams);
            }
        }

        return [
            'invoice_number' => $invoiceNumber,
            'document_title' => $documentTitle,
            'file_name' => $fileName,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'domain' => $domain,
            'domain_label' => $domainLabel,
            'invoice_date_label' => $this->formatDate($invoiceDateRaw),
            'institution' => $this->resolveInstitutionPayload(),
            'logo_url' => $this->resolveAssetDataUri('images/logo_ubg_black.png'),
            'ntb_logo_url' => '', // Hide NTB logo for BTN
            'verification_url' => $verificationUrl,
            'invoice_url' => $invoiceUrl,
            'kwitansi_url' => $kwitansiUrl,
            'student' => [
                'name' => $this->firstFilledString([$row->nama ?? null, $row->va_name ?? null, 'Calon Mahasiswa']),
                'kode_mhs' => $kodeMhs,
                'email' => trim((string) ($row->email ?? '')),
                'phone' => trim((string) ($row->no_hp ?? '')),
                'program_studi' => trim((string) ($row->nama_prodi ?? '')),
                'identifier_label' => $identifierLabel,
                'identifier_value' => $identifierValue,
            ],
            'item' => [
                'description' => $description,
                'meta' => array_values(array_filter([
                    $kodePayment > 0 ? 'Kode Payment: '.$kodePayment : '',
                    $paymentItemLabel !== '' ? 'Komponen: '.$paymentItemLabel : '',
                ], static fn (string $value): bool => trim($value) !== '')),
                'amount' => $amount,
                'amount_label' => $this->formatMoney($amount),
            ],
            'subtotal' => $amount,
            'subtotal_label' => $this->formatMoney($amount),
            'total' => $amount,
            'total_label' => $this->formatMoney($amount),
            'payment' => [
                'paid_at_label' => $this->formatDate($paidAtRaw, true),
                'method' => $method,
                'va_number' => trim((string) ($row->va_number ?? '')),
                'reference_no' => $referenceNo,
                'r_code' => trim((string) ($row->bank_r_code ?? '')),
                'message' => $bankMessage,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function decodeJson(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolvePaymentItemLabel(string $paymentItemKey): string
    {
        $key = strtolower(trim($paymentItemKey));
        if ($key === '' || $key === 'all') {
            return '';
        }

        if (preg_match('/^dpp([234]+)$/', $key, $matches) === 1) {
            return 'DPP Tahap '.implode(', ', str_split((string) $matches[1]));
        }

        if (preg_match('/^disp-(\d+)$/', $key, $matches) === 1) {
            return 'Dispensasi Tahap '.$matches[1];
        }

        return strtoupper($key);
    }

    /** @return array<string,string> */
    private function resolveInstitutionPayload(): array
    {
        $setting = Schema::hasTable('cms_setting') ? DB::table('cms_setting')->first() : null;
        $address = $this->firstFilledString([
            $setting->hubungi_alamat ?? null,
            'Jl. Ismail Marzuki No. 22, Mataram, Nusa Tenggara Barat',
        ]);
        $phone = $this->firstFilledString([
            $setting->hubungi_telp ?? null,
            '(0370) 634498',
        ]);
        $email = $this->firstFilledString([
            $setting->hubungi_email ?? null,
            $setting->email_google ?? null,
            'pmb@universitasbumigora.ac.id',
        ]);

        return [
            'name' => 'PMB Universitas Bumigora',
            'institution' => 'Universitas Bumigora',
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'website' => config('app.url', ''),
        ];
    }

    private function resolveAssetDataUri(string $relativePath): string
    {
        $path = public_path(ltrim($relativePath, '/'));
        if (! File::exists($path) || ! File::isFile($path)) {
            return '';
        }

        try {
            $content = File::get($path);
            $mimeType = File::mimeType($path) ?: 'image/png';
        } catch (\Throwable) {
            return '';
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($content);
    }

    /**
     * @param  array<int,mixed>  $values
     */
    private function firstFilledString(array $values): string
    {
        foreach ($values as $value) {
            if (! is_scalar($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
                continue;
            }

            $text = trim((string) $value);
            if ($text === '' || $text === '-' || strtolower($text) === 'null') {
                continue;
            }

            return $text;
        }

        return '';
    }

    private function formatMoney(int|float $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    private function formatDate(string $value, bool $withTime = false): string
    {
        $date = $this->parseDate($value);
        if (! $date) {
            return '-';
        }

        return $date->locale('id')->translatedFormat($withTime ? 'd F Y H:i' : 'd F Y');
    }

    private function parseDate(string $value): ?Carbon
    {
        $text = trim($value);
        if ($text === '' || str_starts_with($text, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($text, 'Asia/Makassar')->timezone('Asia/Makassar');
        } catch (\Throwable) {
            return null;
        }
    }
}
