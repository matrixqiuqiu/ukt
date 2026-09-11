<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * BTN Virtual Account Host-to-Host (BI-SNAP).
 *
 * - Token  : POST {base}/snap/v1.0/access-token/b2b
 *            X-SIGNATURE = base64(RSA-SHA256(privateKey, clientId|X-TIMESTAMP))
 * - Service: X-SIGNATURE = base64(HMAC-SHA512(clientSecret, stringToSign))
 *            stringToSign = METHOD:endpointUrl:accessToken:lower(hex(sha256(minify(body)))):timestamp
 */
class BtnVaService
{
    public const TOKEN_PATH = '/snap/v1.0/access-token/b2b';

    public const PATH_MAP = [
        'create-va' => '/snap/v1.0/transfer-va/create-va',
        'update-va' => '/snap/v1.0/transfer-va/update-va',
        'delete-va' => '/snap/v1.0/transfer-va/delete-va',
        'inquiry-status' => '/snap/v1.0/transfer-va/inquiry-status',
    ];

    public function cfg(string $key, mixed $default = ''): mixed
    {
        return config('services.vabtn.' . $key, $default);
    }

    public function baseUrl(): string
    {
        return rtrim((string) $this->cfg('base_url'), '/');
    }

    public function timeout(): int
    {
        return (int) $this->cfg('timeout_seconds', 30);
    }

    private function log(): \Psr\Log\LoggerInterface
    {
        return Log::channel('bankbtn');
    }

    private function http(): Client
    {
        return new Client(['timeout' => $this->timeout(), 'verify' => false]);
    }

    /** SNAP timestamp: yyyy-MM-ddTHH:mm:ss.SSS+07:00 */
    public function timestamp(): string
    {
        return now()->format('Y-m-d\TH:i:s.vP');
    }

    private function privateKeyPem(): ?string
    {
        $b64 = (string) $this->cfg('private_key_base64');
        if ($b64 === '') {
            return null;
        }
        $pem = base64_decode($b64, true);
        return $pem !== false && str_contains($pem, 'PRIVATE KEY') ? $pem : null;
    }

    private function baseHeaders(string $timestamp, array $extra = []): array
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-TIMESTAMP' => $timestamp,
        ], $extra);

        $origin = (string) $this->cfg('origin');
        if ($origin !== '') {
            $headers['Origin'] = $origin;
        }

        return $headers;
    }

    /**
     * Ambil access token (client_credentials + asymmetric signature).
     *
     * @return array{success:bool,message:string,data:array,access_token:?string}
     */
    public function requestToken(): array
    {
        $clientId = (string) $this->cfg('client_id');
        $clientKey = (string) $this->cfg('client_key');
        $timestamp = $this->timestamp();
        $url = $this->baseUrl() . self::TOKEN_PATH;

        $pem = $this->privateKeyPem();
        if ($pem === null) {
            return $this->fail('Private key BTN belum diatur / tidak valid (BTN_VA_PRIVATE_RSA_KEY_BASE64).');
        }

        $pkey = openssl_pkey_get_private($pem);
        if ($pkey === false) {
            return $this->fail('Private key BTN tidak bisa dibaca openssl: ' . (openssl_error_string() ?: 'unknown'));
        }

        $stringToSign = $clientId . '|' . $timestamp;
        if (!openssl_sign($stringToSign, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
            return $this->fail('Gagal membuat signature RSA-SHA256 token.');
        }

        $body = ['grantType' => 'client_credentials'];
        $headers = $this->baseHeaders($timestamp, [
            'X-CLIENT-KEY' => $clientKey,
            'X-SIGNATURE' => base64_encode($signature),
        ]);

        $this->log()->info('BTN TOKEN REQUEST', ['url' => $url, 'client_key' => $clientKey]);

        try {
            $resp = $this->http()->post($url, ['headers' => $headers, 'body' => json_encode($body)]);
            $data = json_decode($resp->getBody()->getContents(), true) ?? [];
            $code = (string) ($data['responseCode'] ?? '');
            $ok = $resp->getStatusCode() === 200 && str_starts_with($code, '200') && !empty($data['accessToken']);

            $this->log()->info('BTN TOKEN RESPONSE', ['http' => $resp->getStatusCode(), 'responseCode' => $code]);

            return [
                'success' => $ok,
                'message' => $data['responseMessage'] ?? ($ok ? 'OK' : 'Gagal mengambil token'),
                'data' => $data,
                'access_token' => $ok ? $data['accessToken'] : null,
            ];
        } catch (\Throwable $e) {
            $this->log()->warning('BTN TOKEN ERROR: ' . $e->getMessage());
            return $this->fail('Connection failed: ' . $e->getMessage());
        }
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message, 'data' => [], 'access_token' => null];
    }

    /**
     * Panggil endpoint service SNAP (token diambil otomatis).
     *
     * @return array{success:bool,status:int,message:string,data:?array,request:array}
     */
    public function call(string $endpointKey, array $body): array
    {
        if (!isset(self::PATH_MAP[$endpointKey])) {
            return ['success' => false, 'status' => 0, 'message' => 'Unknown endpoint: ' . $endpointKey, 'data' => null, 'request' => $body];
        }

        $tokenRes = $this->requestToken();
        if (!$tokenRes['success'] || !$tokenRes['access_token']) {
            return ['success' => false, 'status' => 0, 'message' => 'Gagal mengambil token: ' . $tokenRes['message'], 'data' => null, 'request' => $body];
        }

        $accessToken = $tokenRes['access_token'];
        $path = self::PATH_MAP[$endpointKey];
        $url = $this->baseUrl() . $path;
        $timestamp = $this->timestamp();
        $minified = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $bodyHash = strtolower(hash('sha256', $minified));
        $stringToSign = 'POST:' . $path . ':' . $accessToken . ':' . $bodyHash . ':' . $timestamp;
        $signature = base64_encode(hash_hmac('sha512', $stringToSign, (string) $this->cfg('client_secret'), true));

        $headers = $this->baseHeaders($timestamp, [
            'Authorization' => 'Bearer ' . $accessToken,
            'X-PARTNER-ID' => (string) $this->cfg('partner_id'),
            'X-EXTERNAL-ID' => $this->externalId(),
            'CHANNEL-ID' => (string) $this->cfg('channel_id'),
            'X-SIGNATURE' => $signature,
        ]);

        $this->log()->info('BTN SERVICE REQUEST', ['url' => $url, 'endpoint' => $endpointKey, 'body' => $minified]);

        $start = microtime(true);
        try {
            $resp = $this->http()->post($url, ['headers' => $headers, 'body' => $minified]);
            $data = json_decode($resp->getBody()->getContents(), true);
            $code = (string) ($data['responseCode'] ?? '');
            $ok = $resp->getStatusCode() === 200 && str_starts_with($code, '200');
            $duration = round((microtime(true) - $start) * 1000);

            $this->log()->info('BTN SERVICE RESPONSE', ['http' => $resp->getStatusCode(), 'responseCode' => $code, 'duration_ms' => $duration]);

            return [
                'success' => $ok,
                'status' => $resp->getStatusCode(),
                'message' => $data['responseMessage'] ?? 'OK',
                'data' => $data,
                'request' => $body,
                'duration_ms' => $duration,
            ];
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $start) * 1000);
            $status = 0;
            $data = null;
            if ($e instanceof \GuzzleHttp\Exception\GuzzleException && method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $status = $e->getResponse()->getStatusCode();
                $data = json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            $this->log()->warning('BTN SERVICE ERROR: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => $status,
                'message' => $data['responseMessage'] ?? ('Connection failed: ' . $e->getMessage()),
                'data' => $data,
                'request' => $body,
                'duration_ms' => $duration,
            ];
        }
    }

    /** Numeric unique reference per hari (SNAP X-EXTERNAL-ID). */
    public function externalId(): string
    {
        return date('YmdHis') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Bangun nomor VA: partnerServiceId + customerNo.
     * customerNo default: NIM + semester (maks 20 digit).
     */
    public function buildVaNumber(string $customerNo): string
    {
        return (string) $this->cfg('partner_service_id') . $customerNo;
    }

    /** Body create-va SNAP dari data tagihan. */
    public function buildCreateVaBody(string $virtualAccountNo, string $customerNo, string $name, float $amount, string $email = '', string $phone = '', ?string $expiredDate = null): array
    {
        $body = [
            'partnerServiceId' => (string) $this->cfg('partner_service_id'),
            'customerNo' => $customerNo,
            'virtualAccountNo' => $virtualAccountNo,
            'virtualAccountName' => $name,
            'trxId' => $this->externalId(),
            'totalAmount' => ['value' => number_format($amount, 2, '.', ''), 'currency' => 'IDR'],
            'expiredDate' => $expiredDate ?? now()->addDays((int) $this->cfg('default_expired_days', 7))->toIso8601String(),
            'additionalInfo' => ['channel' => 'VIRTUAL_ACCOUNT_BTN'],
        ];
        if ($email !== '') {
            $body['virtualAccountEmail'] = $email;
        }
        if ($phone !== '') {
            $body['virtualAccountPhone'] = $phone;
        }
        return $body;
    }
}
