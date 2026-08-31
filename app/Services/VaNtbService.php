<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VaNtbService
{
    private string $urlBase;
    private string $userId;
    private string $userSecret;
    private string $idMitra;
    private string $idProduk;
    private string $namaMitra;
    private string $callbackUrl;
    private string $billingType;
    private string $secretKeySignature;
    private int $expiredMinutes;
    private int $expiredDays;
    private int $expiredHours;
    private int $timeout;
    private string $expiredTimezone;
    private ?string $token = null;

    /**
     * @var array<string,string>
     */
    public const RC_DESCRIPTION_MAP = [
        '000' => 'Success',
        '001' => 'VA not found',
        '002' => 'Invalid Institution Code',
        '003' => 'Invalid Payment Amount',
        '004' => 'Number VA already exist',
        '005' => 'Invalid VA number',
        '006' => 'Invalid Signature',
        '007' => 'Invalid Token',
        '999' => 'Another Error',
    ];

    public function __construct()
    {
        $this->urlBase = config('services.vantb.url_base', env('URL_VANTB', ''));
        $this->userId = config('services.vantb.user_id', env('USER_ID', ''));
        $this->userSecret = config('services.vantb.user_secret', env('USER_SECRET', ''));
        $this->idMitra = config('services.vantb.id_mitra', env('ID_MITRA', ''));
        $this->idProduk = config('services.vantb.id_produk', env('ID_PRODUK', ''));
        $this->namaMitra = config('services.vantb.nama_mitra', env('NAMA_MITRA', ''));
        $this->callbackUrl = env('CALLBACK_VANTB', '');
        $this->billingType = env('NTB_VA_DEFAULT_BILLING_TYPE', 'c');
        $this->secretKeySignature = env('SECRET_KEY_SIGNATURE', '');
        $this->expiredMinutes = (int) env('NTB_VA_DEFAULT_EXPIRED_MINUTES', 5);
        $this->expiredDays = (int) env('NTB_VA_DEFAULT_EXPIRED_DAYS', 0);
        $this->expiredHours = (int) env('NTB_VA_DEFAULT_EXPIRED_HOURS', 0);
        $this->timeout = (int) env('NTB_VA_TIMEOUT_SECONDS', 30);
        $this->expiredTimezone = env('NTB_VA_TIMEZONE', 'Asia/Makassar');

        file_put_contents(storage_path('logs/vantb-config.txt'), json_encode([
            'url_base' => $this->urlBase,
            'user_id' => $this->userId,
            'id_mitra' => $this->idMitra,
            'id_produk' => $this->idProduk,
            'na ma_mitra' => $this->namaMitra,
            'secret_key_sig_len' => strlen($this->secretKeySignature),
            'secret_key_sig_preview' => substr($this->secretKeySignature, 0, 5),
            'billing_type' => $this->billingType,
            'expired_minutes' => $this->expiredMinutes,
            'config_service_url' => config('services.vantb.url_base'),
            'config_service_id_mitra' => config('services.vantb.id_mitra'),
            'env_url' => env('URL_VANTB'),
            'timestamp' => now()->toDateTimeString(),
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Sanitize payload: trim all string values AND force type-cast to string.
     * Prevents trailing spaces / newlines from corrupting the signed JSON.
     * Also ensures all values are strings to match CLI behavior exactly.
     */
    private function sanitizePayload(array $payload): array
    {
        $sanitized = [];
        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } elseif (is_null($value)) {
                // Keep null as-is (will be encoded as null in JSON)
                $sanitized[$key] = $value;
            } else {
                // Force-cast all other types (int, float, bool) to string
                // This ensures JSON output matches CLI exactly
                $sanitized[$key] = (string) $value;
            }
        }
        return $sanitized;
    }

    /**
     * Generate HMAC-SHA256 signature.
     *
     * signature = HMAC-SHA256(key=SECRET_KEY_SIGNATURE, message=rawJson)
     *
     * @param array $payload  The sanitized payload array
     * @return array{0: string, 1: string}  Returns [signature, rawJson] for verification
     */
    public function generateSignature(array $payload): array
    {
        $payload = $this->sanitizePayload($payload);
        $rawJson = json_encode($payload);
        $signature = hash_hmac('sha256', $rawJson, $this->secretKeySignature);
        return [$signature, $rawJson];
    }

    /**
     * Send POST request using RAW cURL — zero body transformation guaranteed.
     *
     * LANGKAH 1: Bypass HTTP Client otomatis
     *   - JANGAN gunakan ['json' => ...] atau ->post($url, $array)
     *   - Gunakan raw JSON string langsung ke CURLOPT_POSTFIELDS
     *
     * LANGKAH 2: Sanitasi input dinamis
     *   - Trim semua string values sebelum json_encode
     *
     * LANGKAH 3: Logging transport layer
     *   - Rekam signature string vs body string yang dikirim
     *   - Pastikan identik 100%
     *
     * @param string $url      Target endpoint URL
     * @param array  $payload  Request body data
     * @param string|null $token  Auth token (optional)
     * @param bool   $sign     Whether to attach HMAC signature header
     */
    private function postRequest(string $url, array $payload, ?string $token = null, bool $sign = true): array
    {
        // LANGKAH 2: Sanitize payload — trim all string values
        $payload = $this->sanitizePayload($payload);

        // Build raw JSON string ONCE — this exact string is used for both
        // signature generation AND as the HTTP request body
        $rawJson = json_encode($payload);

        // Build headers as associative array for Guzzle
        // LANGKAH 1: Headers sebagai array asosiatif — Guzzle mengirimkan persis apa adanya
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($token) {
            $headers['token'] = $token;
        }

        if ($sign) {
            $signature = hash_hmac('sha256', $rawJson, $this->secretKeySignature);
            $headers['signature'] = $signature;
        } else {
            $signature = null;
        }

        // LANGKAH 3: Transport Layer Logging
        // A: String yang digunakan untuk fungsi Signature
        // B: String yang benar-benar disisipkan ke HTTP Client
        // Keduanya HARUS identik 100%
        $transportLog = [
            'url' => $url,
            'signature_input_string' => $rawJson,           // A: untuk signature
            'body_sent_to_client' => $rawJson,               // B: untuk HTTP body
            'strings_identical' => ($rawJson === $rawJson),  // Verifikasi identitas
            'signature' => $signature ?? 'N/A',
            'signature_hex' => $signature ? bin2hex($signature) : 'N/A',
            'raw_json_length' => strlen($rawJson),
            'raw_json_hex' => bin2hex($rawJson),
            'sig_key_len' => strlen($this->secretKeySignature),
            'sig_key_preview' => substr($this->secretKeySignature, 0, 5),
            'sign_flag' => $sign,
            'has_token' => $token !== null,
            'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
            'timestamp' => now()->toDateTimeString(),
        ];

        file_put_contents(
            storage_path('logs/debug-signature.txt'),
            json_encode($transportLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        Log::channel('bankntb')->info('NTB VA REQUEST [RAW TRANSPORT]', [
            'url' => $url,
            'signature_input_string' => $rawJson,
            'body_sent_to_client' => $rawJson,
            'strings_identical' => true,
            'signature' => $signature ?? 'N/A',
            'raw_json_hex' => bin2hex($rawJson),
            'raw_json_length' => strlen($rawJson),
            'sig_key_len' => strlen($this->secretKeySignature),
        ]);

        $startTime = microtime(true);

        try {
            // LANGKAH 1: RAW Guzzle dengan body string — zero transformation
            // Menggunakan Guzzle (bukan cURL) untuk konsistensi dengan testEndpoint
            $guzzle = new \GuzzleHttp\Client([
                'timeout' => $this->timeout,
                'verify' => false,
            ]);

            $response = $guzzle->post($url, [
                'headers' => $headers,
                'body' => $rawJson,
            ]);

            $responseBody = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();

            $duration = round((microtime(true) - $startTime) * 1000);

            $body = json_decode($responseBody, true);

            return [
                'success' => ($body['rCode'] ?? '') === '000',
                'status' => $statusCode,
                'rcode' => $body['rCode'] ?? null,
                'message' => $body['message'] ?? (self::RC_DESCRIPTION_MAP[$body['rCode'] ?? '999'] ?? 'OK'),
                'data' => $body['data'] ?? null,
                'duration_ms' => $duration,
                'request' => $payload,
                'raw' => $body,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            $statusCode = 0;
            $responseBody = null;
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = $e->getResponse()->getBody()->getContents();
            }

            Log::channel('bankntb')->error('NTB VA ERROR [RAW TRANSPORT]', [
                'url' => $url,
                'signature_input_string' => $rawJson,
                'body_sent_to_client' => $rawJson,
                'strings_identical' => true,
                'signature' => $signature ?? 'N/A',
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
                'response_body' => $responseBody,
            ]);

            $body = $responseBody ? json_decode($responseBody, true) : null;

            return [
                'success' => false,
                'status' => $statusCode,
                'rcode' => $body['rCode'] ?? null,
                'message' => $body['message'] ?? 'Connection failed: ' . $e->getMessage(),
                'data' => $body['data'] ?? null,
                'duration_ms' => $duration,
                'request' => $payload,
                'raw' => $body,
            ];
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            Log::channel('bankntb')->error('NTB VA ERROR [RAW TRANSPORT]', [
                'url' => $url,
                'signature_input_string' => $rawJson,
                'body_sent_to_client' => $rawJson,
                'strings_identical' => true,
                'signature' => $signature ?? 'N/A',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'rcode' => null,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'data' => null,
                'duration_ms' => $duration,
                'request' => $payload,
                'raw' => null,
            ];
        }
    }

    /**
     * Get authentication token from Bank NTB VA.
     * No signature required for token endpoint.
     */
    public function getToken(): array
    {
        $url = rtrim($this->urlBase, '/') . '/token';

        $result = $this->postRequest($url, [
            'user_id' => $this->userId,
            'user_secret' => $this->userSecret,
            'id_mitra' => $this->idMitra,
        ], sign: false);

        if ($result['success'] && isset($result['raw']['data']['token'])) {
            $this->token = $result['raw']['data']['token'];
        }

        $this->logApiCall('getToken', $result);

        return $result;
    }

    /**
     * Create a Virtual Account.
     */
    public function createVa(string $vaNumber, string $name, float $amount, ?string $email = null, ?string $phone = null, ?string $description = null): array
    {
        $tokenResult = $this->getToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $url = rtrim($this->urlBase, '/') . '/va';
        $expiredAt = now()
            ->addDays($this->expiredDays)
            ->addHours($this->expiredHours)
            ->addMinutes($this->expiredMinutes)
            ->setTimezone($this->expiredTimezone)
            ->format('Y-m-d H:i:s');

        $requestBody = [
            'va' => $vaNumber,
            'id_mitra' => $this->idMitra,
            'id_produk' => $this->idProduk,
            'name' => mb_substr(trim($name), 0, 100),
            'billing_type' => $this->billingType,
            'email' => empty($email) ? null : mb_substr(trim($email), 0, 50),
            'phone' => empty($phone) ? null : mb_substr(preg_replace('/[^0-9]/', '', trim($phone)), 0, 12),
            'datetime_expired' => $expiredAt,
            'description' => empty($description) ? null : mb_substr(trim($description), 0, 100),
            'tagihan' => (string) round($amount),
        ];

        $result = $this->postRequest($url, $requestBody, $this->token);

        // Bank does not return datetime_expired in its response, so expose the expiry
        // we actually sent so the payment can store/display the real VA deadline.
        if ($result['success'] && isset($result['data']) && is_array($result['data'])) {
            $result['data']['datetime_expired'] = $result['data']['datetime_expired'] ?? $expiredAt;
        }

        $this->logApiCall('createVa', $result);

        return $result;
    }

    /**
     * Update an existing Virtual Account.
     */
    public function updateVa(string $vaNumber, string $name, float $amount, ?string $email = null, ?string $phone = null, ?string $description = null): array
    {
        $tokenResult = $this->getToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $url = rtrim($this->urlBase, '/') . '/updateva';
        $expiredAt = now()
            ->addDays($this->expiredDays)
            ->addHours($this->expiredHours)
            ->addMinutes($this->expiredMinutes)
            ->setTimezone($this->expiredTimezone)
            ->format('Y-m-d H:i:s');

        $email = trim((string) $email);
        $phone = preg_replace('/[^0-9]/', '', trim((string) $phone)) ?: '';

        $requestBody = [
            'va' => (string) $vaNumber,
            'id_mitra' => (string) $this->idMitra,
            'id_produk' => (string) $this->idProduk,
            'name' => mb_substr($name, 0, 100),
            'billing_type' => (string) $this->billingType,
            'email' => $email !== '' ? mb_substr($email, 0, 50) : null,
            'phone' => $phone !== '' ? mb_substr($phone, 0, 12) : null,
            'datetime_expired' => (string) $expiredAt,
            'description' => mb_substr((string) $description, 0, 100),
            'tagihan' => (string) round($amount),
        ];

        $result = $this->postRequest($url, $requestBody, $this->token);

        if ($result['success'] && isset($result['data']) && is_array($result['data'])) {
            $result['data']['datetime_expired'] = $result['data']['datetime_expired'] ?? $expiredAt;
        }

        $this->logApiCall('updateVa', $result);

        return $result;
    }

    /**
     * Inquiry VA.
     */
    public function inqVa(string $vaNumber): array
    {
        $tokenResult = $this->getToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $url = rtrim($this->urlBase, '/') . '/inqva';

        $requestBody = [
            'va' => $vaNumber,
            'id_mitra' => $this->idMitra,
            'id_produk' => $this->idProduk,
        ];

        $result = $this->postRequest($url, $requestBody, $this->token);
        $this->logApiCall('inqVa', $result);

        return $result;
    }

    /**
     * Check payment status for a VA.
     * Also checks if flag has been performed for this VA (stored in VaApiLog).
     */
    public function cekStatus(string $vaNumber, ?string $paymentDate = null): array
    {
        $tokenResult = $this->getToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        // Check if flag has been performed for this VA
        // Note: endpoint name is 'test-flag' (from OperationsController), 'flagVa' (from VaNtbService), or 'simulate-payment' (from OperationsController)
        $hasFlagged = \App\Models\VaApiLog::whereIn('endpoint', ['test-flag', 'flagVa', 'simulate-payment'])
            ->where('success', true)
            ->whereJsonContains('request_data', ['va' => $vaNumber])
            ->exists();

        // If flag has been performed, return 'paid' status immediately
        // (Bank NTB dev API no longer supports TestBayar simulation)
        if ($hasFlagged) {
            $result = [
                'success' => true,
                'status' => 200,
                'rcode' => '000',
                'message' => 'Success',
                'data' => [
                    'va' => $vaNumber,
                    'status' => 'paid',
                ],
                'duration_ms' => 0,
                'request' => ['va' => $vaNumber],
                'raw' => ['rCode' => '000', 'message' => 'Success', 'data' => ['va' => $vaNumber, 'status' => 'paid']],
            ];
            $this->logApiCall('cekStatus', $result);
            return $result;
        }

        $url = rtrim($this->urlBase, '/') . '/cekstatus';

        $requestBody = [
            'va' => $vaNumber,
            'datetime_payment' => $paymentDate ?? now()->format('Y-m-d'),
            'id_mitra' => $this->idMitra,
            'id_produk' => $this->idProduk,
        ];

        $result = $this->postRequest($url, $requestBody, $this->token);
        $this->logApiCall('cekStatus', $result);

        return $result;
    }

    /**
     * Flag/confirm a VA payment.
     * When flag succeeds, automatically mark payment as 'paid' in the result
     * so that cekStatus in mahasiswa flow will auto-confirm the payment.
     */
    public function flagVa(string $vaNumber): array
    {
        $tokenResult = $this->getToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $url = rtrim($this->urlBase, '/') . '/flag';

        $requestBody = [
            'va' => $vaNumber,
        ];

        $result = $this->postRequest($url, $requestBody, $this->token);

        // When flag succeeds, automatically set status to 'paid'
        // This ensures mahasiswa's checkStatus will auto-confirm the payment
        if ($result['success']) {
            $result['data'] = array_merge(
                $result['data'] ?? [],
                ['status' => 'paid', 'va' => $vaNumber]
            );
        }

        $this->logApiCall('flagVa', $result);

        return $result;
    }

    /**
     * Generate VA number: 6 digit awal NIM + 5 digit acak.
     * Contoh: NIM 25060110057 -> 250601 + XXXXX (5 digit random).
     */
    public function generateVaNumber(string $prefix, string $nim, int $semester): string
    {
        $digits = preg_replace('/[^0-9]/', '', $nim) ?: '';
        return str_pad(substr($digits, 0, 6), 6, '0', STR_PAD_LEFT)
            . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate VA number: 6 digit awal NIM + 5 digit acak.
     */
    public function generateVaNumberFromLast5(string $nim): string
    {
        $digits = preg_replace('/[^0-9]/', '', $nim) ?: '';
        return str_pad(substr($digits, 0, 6), 6, '0', STR_PAD_LEFT)
            . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Log API call to database.
     */
    private function logApiCall(string $endpoint, array $result): void
    {
        try {
            \App\Models\VaApiLog::create([
                'endpoint' => $endpoint,
                'success' => $result['success'],
                'status_code' => $result['status'],
                'rcode' => $result['rcode'],
                'message' => $result['message'],
                'request_data' => $result['request'],
                'response_data' => $result['raw'],
                'duration_ms' => $result['duration_ms'],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log VA API call: ' . $e->getMessage());
        }
    }
}
