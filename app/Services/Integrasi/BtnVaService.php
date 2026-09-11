<?php

namespace App\Services\Integrasi;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class BtnVaService
{
    private const TOKEN_CACHE_KEY = 'btnva.oauth-token';

    /** @var array<string,string> */
    private const SUCCESS_CODES = [
        'token' => '2007300', 'create' => '2002700', 'update' => '2002800',
        'inquiry' => '2003000', 'status' => '2002600', 'delete' => '2003100', 'report' => '2003500',
    ];

    public function isConfigured(): bool
    {
        return $this->config('base_url') !== ''
            && $this->credential('oauth_id') !== ''
            && $this->credential('apikey_id') !== ''
            && $this->credential('apikey_secret') !== ''
            && $this->privateKey() !== ''
            && $this->credential('partner_service_id') !== ''
            && $this->credential('channel_id') !== '';
    }

    /** @return array<string,mixed> */
    public function getToken(bool $refresh = false): array
    {
        if (! $this->isConfigured()) {
            return $this->failure('Konfigurasi ENV BTNVA belum lengkap.', 422);
        }

        if (! $refresh && ($cached = Cache::get(self::TOKEN_CACHE_KEY)) && is_array($cached)) {
            return ['ok' => true, 'token' => (string) ($cached['token'] ?? ''), 'cached' => true] + $cached;
        }

        $timestamp = $this->timestamp();
        $signature = '';

        try {
            $privateKey = $this->privateKey();
            if ($privateKey === '') {
                return $this->failure('Private key BTNVA kosong atau gagal didekode dari base64.', 422);
            }

            $pkey = @openssl_pkey_get_private($privateKey);
            if ($pkey === false) {
                return $this->failure('Private key BTNVA tidak valid (format PEM tidak didukung atau kunci rusak). Detail OpenSSL: ' . openssl_error_string(), 422);
            }

            if (! @openssl_sign($this->credential('oauth_id').'|'.$timestamp, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
                return $this->failure('Gagal membuat RSA signature BTN (OpenSSL Sign Error: ' . openssl_error_string() . ').', 500);
            }
        } catch (\Throwable $e) {
            return $this->failure('Terjadi kesalahan OpenSSL pada BTNVA: ' . $e->getMessage(), 500);
        }

        $headers = [
            'X-CLIENT-KEY' => $this->credential('oauth_id'),
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => base64_encode($signature),
        ];
        if ($this->config('origin') !== '') {
            $headers['Origin'] = $this->config('origin');
        }

        $result = $this->send('token', ['grantType' => 'client_credentials'], $headers);
        $body = is_array($result['response_payload'] ?? null) ? $result['response_payload'] : [];
        $token = trim((string) ($body['accessToken'] ?? ''));
        if (($result['response_code'] ?? '') !== self::SUCCESS_CODES['token'] || $token === '') {
            $msg = trim((string) ($body['responseMessage'] ?? 'Token BTN ditolak.'));
            if (isset($result['response_payload'])) {
                $msg .= ' | Response Bank: ' . json_encode($result['response_payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            return $result + ['ok' => false, 'message' => $msg];
        }

        $ttl = max(60, min(840, (int) ($body['expiresIn'] ?? 900) - 30));
        $value = ['token' => $token, 'expires_in' => (int) ($body['expiresIn'] ?? 900), 'obtained_at' => now()->toIso8601String()];
        Cache::put(self::TOKEN_CACHE_KEY, $value, now()->addSeconds($ttl));

        return $result + ['ok' => true] + $value;
    }

    /** @param array<string,mixed> $payload
     *  @return array<string,mixed> */
    public function operation(string $operation, array $payload): array
    {
        if (! array_key_exists($operation, self::SUCCESS_CODES) || $operation === 'token') {
            return $this->failure('Operasi BTNVA tidak valid.', 422);
        }
        $tokenResult = $this->getToken();
        if (! ($tokenResult['ok'] ?? false)) {
            return $tokenResult;
        }

        $request = $this->normalizeOperationPayload($operation, $payload);
        if (($request['error'] ?? '') !== '') {
            return $this->failure((string) $request['error'], 422);
        }
        unset($request['error']);

        $body = $this->json($request);
        $timestamp = $this->timestamp();
        $externalId = $this->externalId();
        $path = $this->endpoint($operation);
        $stringToSign = 'POST:'.$path.':'.(string) $tokenResult['token'].':'.hash('sha256', $body).':'.$timestamp;
        $signature = base64_encode(hash_hmac('sha512', $stringToSign, $this->credential('apikey_secret'), true));

        $headers = [
            'Authorization' => 'Bearer '.(string) $tokenResult['token'],
            'X-EXTERNAL-ID' => $externalId,
            'X-PARTNER-ID' => $this->credential('apikey_id'),
            'X-SIGNATURE' => $signature,
            'X-TIMESTAMP' => $timestamp,
            'CHANNEL-ID' => $this->credential('channel_id'),
        ];
        if ($this->config('origin') !== '') {
            $headers['Origin'] = $this->config('origin');
        }

        $result = $this->send($operation, $request, $headers);
        $result['external_id'] = $externalId;
        $result['ok'] = (string) ($result['response_code'] ?? '') === self::SUCCESS_CODES[$operation];
        $result['message'] = trim((string) (($result['response_payload']['responseMessage'] ?? '') ?: ($result['message'] ?? '')));

        return $result;
    }

    /**
     * Kirim payload ke BTN PERSIS sebagaimana adanya (untuk Testing Endpoint / development).
     * Hanya partnerServiceId yang dipaksa dari ENV. Tidak ada stripping atau modifikasi field lain.
     *
     * @param  array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function operationRaw(string $operation, array $payload): array
    {
        if (! array_key_exists($operation, self::SUCCESS_CODES) || $operation === 'token') {
            return $this->failure('Operasi BTNVA tidak valid.', 422);
        }
        $tokenResult = $this->getToken();
        if (! ($tokenResult['ok'] ?? false)) {
            return $tokenResult;
        }

        $body = $this->json($payload);
        $timestamp = $this->timestamp();
        $externalId = $this->externalId();
        $path = $this->endpoint($operation);
        $stringToSign = 'POST:'.$path.':'.(string) $tokenResult['token'].':'.hash('sha256', $body).':'.$timestamp;
        $signature = base64_encode(hash_hmac('sha512', $stringToSign, $this->credential('apikey_secret'), true));

        $headers = [
            'Authorization' => 'Bearer '.(string) $tokenResult['token'],
            'X-EXTERNAL-ID' => $externalId,
            'X-PARTNER-ID' => $this->credential('apikey_id'),
            'X-SIGNATURE' => $signature,
            'X-TIMESTAMP' => $timestamp,
            'CHANNEL-ID' => $this->credential('channel_id'),
        ];
        if ($this->config('origin') !== '') {
            $headers['Origin'] = $this->config('origin');
        }

        $result = $this->send($operation, $payload, $headers);
        $result['external_id'] = $externalId;
        $result['ok'] = (string) ($result['response_code'] ?? '') === self::SUCCESS_CODES[$operation];
        $result['message'] = trim((string) (($result['response_payload']['responseMessage'] ?? '') ?: ($result['message'] ?? '')));

        return $result;
    }

    /** @param array<string,mixed> $payload
     *  @return array<string,mixed> */
    public function createOrRefreshAdmisiTransaction(array $payload): array
    {
        if (! Schema::hasTable('tb_btnva_transaction')) {
            return $this->failure('Migrasi tb_btnva_transaction belum dijalankan.', 503);
        }
        $kodeMhs = trim((string) ($payload['kode_mhs'] ?? ''));
        $kodePayment = (int) ($payload['kode_payment'] ?? 0);
        $amount = max(1, (int) round((float) ($payload['tagihan'] ?? 0)));
        if ($kodeMhs === '' || $kodePayment <= 0 || $amount <= 0) {
            return $this->failure('Data transaksi BTNVA admisi belum lengkap.', 422);
        }

        $row = DB::transaction(function () use ($kodeMhs, $kodePayment, $amount, $payload): array {
            $existing = DB::table('tb_btnva_transaction')->where('domain', 'admisi')->where('kode_mhs', $kodeMhs)->where('kode_payment', $kodePayment)->orderByDesc('id')->first();
            if ($existing) {
                return (array) $existing;
            }
            $id = DB::table('tb_btnva_transaction')->insertGetId([
                'kode_mhs' => $kodeMhs, 'domain' => 'admisi', 'kode_payment' => $kodePayment,
                'metode_pembayaran' => 'va_btn', 'customer_no' => $this->customerNo($kodeMhs),
                'va_name' => $this->limit((string) ($payload['nama'] ?? $kodeMhs), 30),
                'trx_id' => $this->trxId($kodePayment), 'tagihan' => $amount,
                'status_bank' => 'pending', 'is_active' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return (array) DB::table('tb_btnva_transaction')->find($id);
        });

        if (in_array(strtolower((string) ($row['status_bank'] ?? '')), ['paid', 'success'], true)) {
            return ['ok' => true, 'is_paid' => true, 'transaction_id' => (int) $row['id'], 'va_number' => (string) ($row['va_number'] ?? '')];
        }
        if (trim((string) ($row['va_number'] ?? '')) !== '') {
            return ['ok' => true, 'transaction_id' => (int) $row['id'], 'va_number' => (string) $row['va_number'], 'message' => 'VA BTN sudah tersedia.'];
        }

        $request = $this->buildCreatePayload($row, $payload);
        $result = $this->operation('create', $request);
        $responseData = (array) (($result['response_payload']['virtualAccountData'] ?? []));
        $va = trim((string) ($responseData['virtualAccountNo'] ?? $request['virtualAccountNo']));
        $this->storeResult((int) $row['id'], $result, $request, [
            'va_number' => $va, 'va_name' => (string) ($request['virtualAccountName'] ?? ''),
            'expired_at' => $this->toDatabaseDate((string) ($request['expiredDate'] ?? '')),
            'status_bank' => ($result['ok'] ?? false) ? 'pending' : 'error',
            'is_active' => 1,
        ]);

        return $result + ['transaction_id' => (int) $row['id'], 'va_number' => $va];
    }

    /** @param array<string,mixed> $payload
     *  @return array<string,mixed> */
    public function createOrRefreshDaftarUlangTransaction(array $payload): array
    {
        if (! Schema::hasTable('tb_btnva_transaction')) {
            return $this->failure('Migrasi tb_btnva_transaction belum dijalankan.', 503);
        }
        $kodeMhs = trim((string) ($payload['kode_mhs'] ?? ''));
        $paymentItemKey = strtolower(trim((string) ($payload['payment_item_key'] ?? 'all')));
        $kodePendaftaran = trim((string) ($payload['kode_pendaftaran'] ?? ''));
        $kodeDaftarUlang = trim((string) ($payload['kode_daftar_ulang'] ?? ''));
        $amount = max(1, (int) round((float) ($payload['tagihan'] ?? 0)));
        if ($kodeMhs === '' || $amount <= 0) {
            return $this->failure('Data transaksi BTNVA daftar ulang belum lengkap.', 422);
        }

        if ($paymentItemKey === '') {
            $paymentItemKey = 'all';
        }

        $seed = crc32("{$kodeMhs}|{$paymentItemKey}");

        $row = DB::transaction(function () use ($kodeMhs, $paymentItemKey, $amount, $kodePendaftaran, $kodeDaftarUlang, $seed, $payload): array {
            $existing = DB::table('tb_btnva_transaction')
                ->where('domain', 'daftar_ulang')
                ->where('kode_mhs', $kodeMhs)
                ->where('payment_item_key', $paymentItemKey)
                ->orderByDesc('id')
                ->first();
            if ($existing) {
                return (array) $existing;
            }
            $id = DB::table('tb_btnva_transaction')->insertGetId([
                'kode_mhs' => $kodeMhs,
                'domain' => 'daftar_ulang',
                'kode_payment' => null,
                'payment_item_key' => $paymentItemKey,
                'metode_pembayaran' => 'va_btn',
                'customer_no' => $this->customerNo($kodeMhs),
                'va_name' => $this->limit((string) ($payload['nama'] ?? $kodeMhs), 30),
                'trx_id' => $this->trxId($seed),
                'tagihan' => $amount,
                'status_bank' => 'pending',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return (array) DB::table('tb_btnva_transaction')->find($id);
        });

        if (in_array(strtolower((string) ($row['status_bank'] ?? '')), ['paid', 'success'], true)) {
            return ['ok' => true, 'is_paid' => true, 'transaction_id' => (int) $row['id'], 'va_number' => (string) ($row['va_number'] ?? '')];
        }
        if (trim((string) ($row['va_number'] ?? '')) !== '') {
            return ['ok' => true, 'transaction_id' => (int) $row['id'], 'va_number' => (string) $row['va_number'], 'message' => 'VA BTN sudah tersedia.'];
        }

        $request = $this->buildCreatePayload($row, $payload);
        $result = $this->operation('create', $request);
        $responseData = (array) (($result['response_payload']['virtualAccountData'] ?? []));
        $va = trim((string) ($responseData['virtualAccountNo'] ?? $request['virtualAccountNo']));
        $this->storeResult((int) $row['id'], $result, $request, [
            'va_number' => $va, 'va_name' => (string) ($request['virtualAccountName'] ?? ''),
            'expired_at' => $this->toDatabaseDate((string) ($request['expiredDate'] ?? '')),
            'status_bank' => ($result['ok'] ?? false) ? 'pending' : 'error',
            'is_active' => 1,
        ]);

        return $result + ['transaction_id' => (int) $row['id'], 'va_number' => $va];
    }

    /** @param array<string,mixed> $payload
     *  @param array<string,string> $headers
     *  @return array<string,mixed> */
    public function handleInquiry(array $payload, array $headers, string $path): array
    {
        if (! $this->verifyInboundSignature($payload, $headers, $path)) {
            return $this->snapError('4012401', 'Unauthorized Signature', 401);
        }
        $va = trim((string) ($payload['virtualAccountNo'] ?? ''));
        $inquiryId = trim((string) ($payload['inquiryRequestId'] ?? ''));
        $row = $this->findByVa($va);
        if (! $row || ! (bool) $row->is_active) {
            return $this->snapError('4042412', 'Virtual Account Not Found', 404);
        }
        DB::table('tb_btnva_transaction')->where('id', $row->id)->update(['inquiry_request_id' => $inquiryId, 'updated_at' => now()]);
        $amount = $this->money((int) $row->tagihan);
        return ['http_status' => 200, 'body' => [
            'responseCode' => '2002400', 'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'inquiryStatus' => '00', 'inquiryReason' => ['english' => 'Success', 'indonesia' => 'Sukses'],
                'partnerServiceId' => $this->partnerServiceId(), 'customerNo' => (string) $row->customer_no,
                'virtualAccountNo' => (string) $row->va_number, 'virtualAccountName' => (string) $row->va_name,
                'inquiryRequestId' => $inquiryId, 'totalAmount' => ['value' => $amount, 'currency' => 'IDR'],
                'billDetails' => [['billCode' => '01', 'billNo' => (string) $row->trx_id, 'billName' => (string) $row->va_name, 'billAmount' => ['value' => $amount, 'currency' => 'IDR']]],
                'additionalInfo' => (object) [],
            ], 'additionalInfo' => (object) [],
        ]];
    }

    /** @param array<string,mixed> $payload
     *  @param array<string,string> $headers
     *  @return array<string,mixed> */
    public function handlePayment(array $payload, array $headers, string $path): array
    {
        if (! $this->verifyInboundSignature($payload, $headers, $path)) {
            return $this->snapError('4012501', 'Unauthorized Signature', 401);
        }
        $row = $this->findByVa((string) ($payload['virtualAccountNo'] ?? ''));
        if (! $row || trim((string) ($payload['trxId'] ?? '')) !== trim((string) $row->trx_id)) {
            return $this->snapError('4042512', 'Virtual Account Not Found', 404);
        }
        $paid = $this->amount($payload['paidAmount']['value'] ?? 0);
        if ($paid < (int) $row->tagihan) {
            return $this->snapError('4002513', 'Invalid Amount', 400);
        }
        $paidAtText = $this->toDatabaseDate((string) ($payload['trxDateTime'] ?? '')) ?? now()->toDateTimeString();
        DB::transaction(function () use ($row, $payload, $paidAtText): void {
            DB::table('tb_btnva_transaction')->where('id', $row->id)->update([
                'status_bank' => 'paid', 'status_message' => 'Pembayaran BTN diterima.',
                'payment_request_id' => trim((string) ($payload['paymentRequestId'] ?? '')),
                'paid_at' => $paidAtText,
                'is_active' => 0, 'next_check_at' => null, 'updated_at' => now(),
                'response_payload' => $this->json($this->mask($payload)),
            ]);
            $this->finalizePaymentByDomain((array) $row, $paidAtText);
        });
        return ['http_status' => 200, 'body' => [
            'responseCode' => '2002500', 'responseMessage' => 'Successful',
            'virtualAccountData' => ['paymentFlagStatus' => '00', 'paymentFlagReason' => ['english' => 'Success', 'indonesia' => 'Sukses'], 'partnerServiceId' => $this->partnerServiceId(), 'customerNo' => (string) $row->customer_no, 'virtualAccountNo' => (string) $row->va_number, 'virtualAccountName' => (string) $row->va_name, 'trxId' => (string) $row->trx_id, 'paymentRequestId' => trim((string) ($payload['paymentRequestId'] ?? ''))],
            'additionalInfo' => (object) [],
        ]];
    }

    /** @return array<string,mixed> */
    public function testConnection(): array
    {
        $res = $this->getToken(true);
        if (! (bool) ($res['ok'] ?? false)) {
            $msg = trim((string) ($res['message'] ?? 'Test koneksi BTNVA gagal.'));
            if (isset($res['response_payload'])) {
                $payloadStr = json_encode($res['response_payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (! str_contains($msg, 'Response Bank:')) {
                    $msg .= ' | Response Bank: ' . $payloadStr;
                }
            }
            return $res + ['ok' => false, 'message' => $msg];
        }
        return ['ok' => true, 'message' => 'Token BTNVA berhasil dibuat dan disimpan sementara secara aman.'];
    }

    /** @return array<string,mixed> */
    public function retryTransaction(int $transactionId): array
    {
        $row = Schema::hasTable('tb_btnva_transaction') ? DB::table('tb_btnva_transaction')->find($transactionId) : null;
        if (! $row) return $this->failure('Transaksi BTNVA tidak ditemukan.', 404);
        if (strtolower((string) $row->status_bank) === 'paid') return $this->failure('Transaksi BTNVA sudah lunas.', 422);

        if (trim((string) $row->va_number) !== '') {
            $inquiryId = trim((string) $row->inquiry_request_id);
            if ($inquiryId === '') {
                $inquiryId = 'INQ' . now('Asia/Jakarta')->format('ymdHis') . strtoupper(Str::random(4));
                DB::table('tb_btnva_transaction')->where('id', $row->id)->update([
                    'inquiry_request_id' => $inquiryId,
                    'updated_at' => now(),
                ]);
            }

            $result = $this->operation('status', [
                'customerNo' => (string) $row->customer_no,
                'virtualAccountNo' => (string) $row->va_number,
                'inquiryRequestId' => $inquiryId,
                'paymentRequestId' => $row->payment_request_id ?: null,
                'additionalInfo' => (object) []
            ]);

            if ($result['ok'] ?? false) {
                $vaData = $result['response_payload']['virtualAccountData'] ?? [];
                $flag = trim((string) ($vaData['paymentFlagStatus'] ?? ''));
                if ($flag === '00') {
                    $paidAtText = $this->toDatabaseDate((string) ($vaData['paidTime'] ?? $vaData['paymentTimestamp'] ?? '')) ?? now()->toDateTimeString();
                    DB::transaction(function () use ($row, $result, $paidAtText): void {
                        DB::table('tb_btnva_transaction')->where('id', $row->id)->update([
                            'status_bank' => 'paid',
                            'status_message' => 'Lunas via status check.',
                            'paid_at' => $paidAtText,
                            'is_active' => 0,
                            'updated_at' => now(),
                            'response_payload' => $this->json($result['response_payload'] ?? []),
                        ]);
                        $this->finalizePaymentByDomain((array) $row, $paidAtText);
                    });
                }
            }

            return $result;
        }

        return $this->createOrRefreshAdmisiTransaction([
            'kode_mhs' => $row->kode_mhs,
            'kode_payment' => $row->kode_payment,
            'tagihan' => $row->tagihan,
            'nama' => $row->va_name
        ]);
    }

    private function endpoint(string $operation): string { return (string) config('virtual_account.btn.endpoints.'.$operation, ''); }
    private function config(string $key): string { return trim((string) config('virtual_account.btn.'.$key, '')); }
    private function credential(string $key): string { return trim((string) config('virtual_account.btn.credentials.'.$key, '')); }
    private function partnerServiceId(): string { return trim($this->credential('partner_service_id')); }
    private function timestamp(): string { return Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP'); }
    private function privateKey(): string { $raw = base64_decode(preg_replace('/\s+/', '', str_replace(['\n', '\r'], '', $this->credential('private_rsa_key_base64'))), true); return is_string($raw) ? $raw : ''; }
    private function json(array $data): string { return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'; }
    private function money(int $value): string { return number_format(max(0, $value), 2, '.', ''); }
    private function limit(string $value, int $limit): string { return mb_substr(trim($value), 0, $limit); }
    private function amount(mixed $value): int { return (int) round((float) preg_replace('/[^0-9.\-]/', '', (string) $value)); }

    private function externalId(): string
    {
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', Str::random(16)) ?: 'BTN'.bin2hex(random_bytes(8)), 0, 16));
    }

    private function customerNo(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?: '';
        return str_pad(substr($digits, -13), 13, '0', STR_PAD_LEFT);
    }

    private function trxId(int $seed): string
    {
        // BTN permits an alphanumeric trxId up to 19 characters; random suffix
        // prevents collisions when an old payment ID is reused in a later year.
        return 'BTN'.now('Asia/Jakarta')->format('ymdHis').strtoupper(Str::random(4));
    }

    private function buildCreatePayload(array $row, array $input): array
    {
        $customer = (string) $row['customer_no'];
        $partner = preg_replace('/\D/', '', trim($this->credential('partner_service_id'))) ?: '';
        $currentAccountNo = trim((string) $this->credential('current_account_no'));
        return [
            'partnerServiceId' => $this->partnerServiceId(), 'customerNo' => $customer,
            'virtualAccountNo' => str_pad(substr($partner.$customer, -18), 18, '0', STR_PAD_LEFT),
            'virtualAccountName' => $this->limit((string) ($input['nama'] ?? $row['va_name'] ?? 'PMB UBG'), 30),
            'trxId' => (string) $row['trx_id'], 'totalAmount' => ['value' => $this->money((int) $row['tagihan']), 'currency' => 'IDR'],
            'virtualAccountTrxType' => 'C', 'expiredDate' => Carbon::now('Asia/Jakarta')->addDays(max(1, (int) config('virtual_account.btn.default_expired_days', 7)))->format('Y-m-d\TH:i:sP'),
            'additionalInfo' => [
                'description' => $this->limit((string) ($input['description'] ?? 'Pembayaran PMB UBG'), 60),
                'payment' => '',
                'currentAccountNo' => $currentAccountNo,
                'paymentCode' => '',
            ],
        ];
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function normalizeOperationPayload(string $operation, array $payload): array
    {
        $partner = $this->partnerServiceId();
        $payload['partnerServiceId'] = $partner;
        if ($operation === 'report') {
            return ['partnerServiceId' => $partner, 'startDate' => trim((string) ($payload['startDate'] ?? '')), 'endDate' => trim((string) ($payload['endDate'] ?? ''))];
        }
        if (in_array($operation, ['create', 'update'], true)) {
            foreach (['customerNo', 'virtualAccountNo', 'virtualAccountName', 'trxId', 'totalAmount', 'virtualAccountTrxType'] as $required) if (! isset($payload[$required])) return ['error' => 'Payload '.$operation.' BTN tidak lengkap: '.$required.'.'];
        }
        foreach (['inquiry', 'delete'] as $name) if ($operation === $name) foreach (['customerNo', 'virtualAccountNo', 'trxId'] as $required) if (empty($payload[$required])) return ['error' => 'Payload '.$operation.' BTN tidak lengkap: '.$required.'.'];
        if ($operation === 'status') foreach (['customerNo', 'virtualAccountNo', 'inquiryRequestId'] as $required) if (empty($payload[$required])) return ['error' => 'Payload status BTN tidak lengkap: '.$required.'.'];

        if (array_key_exists('expiredDate', $payload) && trim((string) $payload['expiredDate']) === '') {
            unset($payload['expiredDate']);
        }

        if (isset($payload['additionalInfo']) && is_array($payload['additionalInfo'])) {
            // BTN menolak field kosong/null (4002701 Invalid Field Format) → buang semua yang kosong
            foreach ($payload['additionalInfo'] as $key => $value) {
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    unset($payload['additionalInfo'][$key]);
                }
            }
            if ($payload['additionalInfo'] === []) {
                $payload['additionalInfo'] = (object) [];
            }
        }

        return $payload;
    }

    /** @param array<string,mixed> $payload @param array<string,string> $headers @return array<string,mixed> */
    private function send(string $operation, array $payload, array $headers): array
    {
        $path = $this->endpoint($operation);
        $url = $this->config('base_url').$path;
        $body = $this->json($payload);
        $started = microtime(true);
        try {
            $response = Http::timeout(max(5, (int) config('virtual_account.btn.timeout_seconds', 30)))
                ->acceptJson()->withHeaders($headers)->withBody($body, 'application/json')->send('POST', $url);
            $data = $response->json(); $data = is_array($data) ? $data : [];
            $this->log($operation, $path, $payload, $headers, $data, $response->status(), (microtime(true) - $started) * 1000);
            return ['http_status' => $response->status(), 'response_payload' => $data, 'response_code' => (string) ($data['responseCode'] ?? ''), 'message' => (string) ($data['responseMessage'] ?? '')];
        } catch (ConnectionException $e) {
            $this->log($operation, $path, $payload, $headers, ['error' => $e->getMessage()], 0, (microtime(true) - $started) * 1000);
            return $this->failure('Koneksi BTN gagal: '.$e->getMessage(), 0);
        } catch (Throwable $e) {
            Log::warning('BTNVA request gagal: '.$e->getMessage());
            return $this->failure('Request BTN gagal diproses.', 500);
        }
    }

    /** @param array<string,mixed> $result @param array<string,mixed> $request @param array<string,mixed> $extra */
    private function storeResult(int $id, array $result, array $request, array $extra = []): void
    {
        DB::table('tb_btnva_transaction')->where('id', $id)->update($extra + [
            'status_message' => (string) ($result['message'] ?? ''), 'bank_response_code' => (string) ($result['response_code'] ?? ''),
            'bank_http_status' => (int) ($result['http_status'] ?? 0), 'request_payload' => $this->json($this->mask($request)),
            'response_payload' => $this->json($this->mask((array) ($result['response_payload'] ?? []))), 'last_checked_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function toDatabaseDate(string $value): ?string { try { return $value !== '' ? Carbon::parse($value)->format('Y-m-d H:i:s') : null; } catch (Throwable) { return null; } }
    private function findByVa(string $value): ?object { $digits = preg_replace('/\D/', '', $value) ?: ''; return $digits === '' ? null : DB::table('tb_btnva_transaction')->whereRaw("REPLACE(REPLACE(va_number, ' ', ''), '-', '') = ?", [$digits])->first(); }

    /** @param array<string,mixed> $payload @param array<string,string> $headers */
    private function verifyInboundSignature(array $payload, array $headers, string $path): bool
    {
        $timestamp = trim((string) ($headers['x-timestamp'] ?? ''));
        $signature = trim((string) ($headers['x-signature'] ?? ''));
        $externalId = trim((string) ($headers['x-external-id'] ?? ''));
        $partnerId = trim((string) ($headers['x-partner-id'] ?? ''));
        $channelId = trim((string) ($headers['channel-id'] ?? ''));

        if ($timestamp === '' || $signature === '' || $partnerId === '' || $channelId === '') {
            Log::warning('BTNVA inbound signature failed: Missing required headers.', ['headers' => $headers]);
            return false;
        }

        $expectedPartnerId = $this->credential('apikey_id');
        $expectedClientId = $this->credential('oauth_id');
        if ($expectedPartnerId !== '' && $partnerId !== $expectedPartnerId && $partnerId !== $expectedClientId && $partnerId !== 'BTN-UBG-2026') {
            Log::warning("BTNVA inbound partner ID mismatch: received {$partnerId}, expected {$expectedPartnerId}");
            if ((bool) $this->config('production')) {
                return false;
            }
        }

        if ($externalId !== '' && preg_match('/^[A-Za-z0-9_-]{1,36}$/', $externalId) !== 1) {
            Log::warning("BTNVA inbound invalid external ID format: {$externalId}");
            if ((bool) $this->config('production')) {
                return false;
            }
        }

        $maxSkew = (bool) $this->config('production') ? 300 : 86400;
        try {
            if (abs(Carbon::parse($timestamp)->diffInSeconds(Carbon::now('Asia/Jakarta'), false)) > $maxSkew) {
                Log::warning("BTNVA inbound timestamp skew too large: {$timestamp}");
                if ((bool) $this->config('production')) {
                    return false;
                }
            }
        } catch (Throwable) {
            if ((bool) $this->config('production')) {
                return false;
            }
        }

        // 1. Try HMAC-SHA512 verification (Symmetric)
        $toSign = 'POST:'.$path.':'.hash('sha256', $this->json($payload)).':'.$timestamp;
        $calculatedHmac = base64_encode(hash_hmac('sha512', $toSign, $this->credential('apikey_secret'), true));
        if (hash_equals($calculatedHmac, $signature)) {
            return true;
        }

        $altPath = str_starts_with($path, '/btnva') ? substr($path, 6) : '/btnva'.$path;
        $toSignAlt = 'POST:'.$altPath.':'.hash('sha256', $this->json($payload)).':'.$timestamp;
        $calculatedHmacAlt = base64_encode(hash_hmac('sha512', $toSignAlt, $this->credential('apikey_secret'), true));
        if (hash_equals($calculatedHmacAlt, $signature)) {
            return true;
        }

        // 2. Try RSA-SHA256 verification (Asymmetric - 256 bytes binary / 344 base64 chars)
        $sigBin = base64_decode($signature, true);
        if (is_string($sigBin) && strlen($sigBin) === 256) {
            $privateKeyPem = $this->privateKey();
            if ($privateKeyPem !== '') {
                $pkey = @openssl_pkey_get_private($privateKeyPem);
                if ($pkey !== false) {
                    $details = openssl_pkey_get_details($pkey);
                    $pubKey = (string) ($details['key'] ?? '');
                    if ($pubKey !== '') {
                        if (openssl_verify($toSign, $sigBin, $pubKey, OPENSSL_ALGO_SHA256) === 1
                            || openssl_verify($toSignAlt, $sigBin, $pubKey, OPENSSL_ALGO_SHA256) === 1) {
                            return true;
                        }
                    }
                }
            }

            if (! (bool) $this->config('production')) {
                Log::info('BTNVA inbound RSA signature received in dev environment, accepting request.');
                return true;
            }
        }

        Log::warning("BTNVA inbound signature mismatch for path {$path}");
        return ! (bool) $this->config('production');
    }

    /** @return array<string,mixed> */
    private function snapError(string $code, string $message, int $status): array { return ['http_status' => $status, 'body' => ['responseCode' => $code, 'responseMessage' => $message]]; }
    private function markInternalPaymentPaid(int $kodePayment, string $va): void { if ($kodePayment > 0 && Schema::hasTable('tb_payment')) { $data = []; if (Schema::hasColumn('tb_payment', 'status_proses')) $data['status_proses'] = 1; if (Schema::hasColumn('tb_payment', 'ket')) $data['ket'] = 'Lunas melalui BTN VA. Nomor VA: '.$va; if (Schema::hasColumn('tb_payment', 'updated')) $data['updated'] = now(); if ($data !== []) DB::table('tb_payment')->where('kode_payment', $kodePayment)->update($data); } }
    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function mask(array $data): array { foreach ($data as $key => $value) { if (is_array($value)) $data[$key] = $this->mask($value); elseif (in_array(strtolower((string) $key), ['accesstoken', 'tokentype'], true)) continue; elseif (preg_match('/token|secret|signature|authorization|private/i', (string) $key)) $data[$key] = '***'; } return $data; }
    /** @param array<string,mixed> $payload @param array<string,string> $headers @param array<string,mixed> $response */
    private function log(string $operation, string $endpoint, array $payload, array $headers, array $response, int $httpStatus, float $duration): void { if (! Schema::hasTable('tb_payment_gateway_log')) return; try { DB::table('tb_payment_gateway_log')->insert(['provider' => 'btnva', 'operation' => $operation, 'endpoint' => $endpoint, 'request_payload' => $this->json($this->mask($payload)), 'request_headers' => $this->json($this->mask($headers)), 'response_payload' => $this->json($this->mask($response)), 'http_status' => $httpStatus, 'r_code' => (string) ($response['responseCode'] ?? ''), 'r_code_description' => (string) ($response['responseMessage'] ?? ''), 'is_success' => in_array((string) ($response['responseCode'] ?? ''), self::SUCCESS_CODES, true), 'status' => $httpStatus >= 200 && $httpStatus < 300 ? 'success' : 'failed', 'message' => (string) ($response['responseMessage'] ?? ''), 'duration_ms' => (int) $duration, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()]); } catch (Throwable) {} }
    /** @return array<string,mixed> */
    private function failure(string $message, int $httpStatus): array { return ['ok' => false, 'message' => $message, 'http_status' => $httpStatus, 'response_payload' => []]; }

    private function finalizePaymentByDomain(array $row, ?string $paidAt): void
    {
        $kodeMhs = trim((string) ($row['kode_mhs'] ?? ''));
        if ($kodeMhs === '') return;

        $domain = trim((string) ($row['domain'] ?? ''));
        $nowText = now()->toDateTimeString();

        if ($domain === 'admisi') {
            $kodePayment = (int) ($row['kode_payment'] ?? 0);
            if ($kodePayment > 0 && Schema::hasTable('tb_payment')) {
                $updatePayload = [];
                if (Schema::hasColumn('tb_payment', 'status_proses')) $updatePayload['status_proses'] = 1;
                if (Schema::hasColumn('tb_payment', 'user_verf')) $updatePayload['user_verf'] = 1;
                if (Schema::hasColumn('tb_payment', 'di_lihat')) $updatePayload['di_lihat'] = 1;
                if (Schema::hasColumn('tb_payment', 'ket')) $updatePayload['ket'] = 'Pembayaran tervalidasi otomatis dari BTN VA.';
                if (Schema::hasColumn('tb_payment', 'tanggal_verifikasi')) $updatePayload['tanggal_verifikasi'] = $paidAt ?? $nowText;
                if (Schema::hasColumn('tb_payment', 'updated')) $updatePayload['updated'] = $nowText;
                if ($updatePayload !== []) {
                    DB::table('tb_payment')->where('kode_payment', $kodePayment)->update($updatePayload);
                }
            }
            if (Schema::hasTable('tb_mhs_register')) {
                $registerPayload = [];
                if (Schema::hasColumn('tb_mhs_register', 'status_bayar')) $registerPayload['status_bayar'] = 1;
                if (Schema::hasColumn('tb_mhs_register', 'updated')) $registerPayload['updated'] = $nowText;
                if (Schema::hasColumn('tb_mhs_register', 'tanggal_verifikasi')) $registerPayload['tanggal_verifikasi'] = $paidAt ?? $nowText;
                if (Schema::hasColumn('tb_mhs_register', 'updated_at')) $registerPayload['updated_at'] = now();
                if ($registerPayload !== []) {
                    DB::table('tb_mhs_register')->whereRaw('TRIM(kode_mhs) = ?', [$kodeMhs])->update($registerPayload);
                }
            }
            return;
        }

        if ($domain === 'daftar_ulang' && Schema::hasTable('du_tagihan_mhs')) {
            $hasStatusVerifikasi = Schema::hasColumn('du_tagihan_mhs', 'status_verifikasi');
            $hasCatatanVerifikasi = Schema::hasColumn('du_tagihan_mhs', 'catatan_verifikasi');
            $hasVerifiedAt = Schema::hasColumn('du_tagihan_mhs', 'verified_at');
            $hasStatusBayar = Schema::hasColumn('du_tagihan_mhs', 'status_bayar');
            $hasNominalBayar = Schema::hasColumn('du_tagihan_mhs', 'nominal_bayar');
            $hasPaymentItemKey = Schema::hasColumn('du_tagihan_mhs', 'payment_item_key');
            $hasMetodePembayaran = Schema::hasColumn('du_tagihan_mhs', 'metode_pembayaran');

            $paymentItemKey = strtolower(trim((string) ($row['payment_item_key'] ?? '')));
            $targetQuery = DB::table('du_tagihan_mhs')->whereRaw('TRIM(kode_mhs) = ?', [$kodeMhs]);
            if ($hasPaymentItemKey && $paymentItemKey !== '' && $paymentItemKey !== 'all') {
                $targetQuery->whereRaw("LOWER(TRIM(COALESCE(payment_item_key, ''))) = ?", [$paymentItemKey]);
            } elseif ($hasMetodePembayaran) {
                $targetQuery->whereRaw("LOWER(TRIM(COALESCE(metode_pembayaran, ''))) = ?", ['va_btn']);
            }

            $updatePayload = ['updated_at' => $nowText];
            if ($hasStatusVerifikasi) $updatePayload['status_verifikasi'] = 1;
            if ($hasCatatanVerifikasi) $updatePayload['catatan_verifikasi'] = null;
            if ($hasVerifiedAt) $updatePayload['verified_at'] = $paidAt ?? $nowText;
            if ($hasStatusBayar) $updatePayload['status_bayar'] = 1;
            if ($hasNominalBayar) $updatePayload['nominal_bayar'] = DB::raw('total_tagihan');
            $targetQuery->update($updatePayload);

            if (Schema::hasTable('tb_mhs_register')) {
                $totalKomponen = (int) DB::table('du_tagihan_mhs')->whereRaw('TRIM(kode_mhs) = ?', [$kodeMhs])->count();
                $totalLunas = (int) DB::table('du_tagihan_mhs')->whereRaw('TRIM(kode_mhs) = ?', [$kodeMhs])->where('status_bayar', 1)->count();
                $isFullyPaid = $totalKomponen > 0 && $totalLunas >= $totalKomponen;

                $registerPayload = [];
                if (Schema::hasColumn('tb_mhs_register', 'st_daftar_ulang')) {
                    $registerPayload['st_daftar_ulang'] = $isFullyPaid || str_starts_with($paymentItemKey, 'disp-') ? 1 : 0;
                }
                if (Schema::hasColumn('tb_mhs_register', 'status_bayar')) {
                    $registerPayload['status_bayar'] = $isFullyPaid ? 1 : 0;
                }
                if (Schema::hasColumn('tb_mhs_register', 'updated')) {
                    $registerPayload['updated'] = $nowText;
                }
                if (Schema::hasColumn('tb_mhs_register', 'updated_at')) {
                    $registerPayload['updated_at'] = now();
                }
                if ($registerPayload !== []) {
                    DB::table('tb_mhs_register')->whereRaw('TRIM(kode_mhs) = ?', [$kodeMhs])->update($registerPayload);
                }
            }

        }
    }
}
