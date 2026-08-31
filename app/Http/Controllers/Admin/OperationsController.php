<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VaApiLog;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OperationsController extends Controller
{
    public function index()
    {
        $config = [
            'nama_mitra' => config('services.vantb.nama_mitra', env('NAMA_MITRA', '')),
            'user_id' => config('services.vantb.user_id', env('USER_ID', '')),
            'id_mitra' => config('services.vantb.id_mitra', env('ID_MITRA', '')),
            'id_produk' => config('services.vantb.id_produk', env('ID_PRODUK', '')),
            'url_base' => config('services.vantb.url_base', env('URL_VANTB', '')),
            'user_secret' => config('services.vantb.user_secret', env('USER_SECRET', '')),
            'callback_url' => env('CALLBACK_VANTB', ''),
            'default_billing_type' => env('NTB_VA_DEFAULT_BILLING_TYPE', 'c'),
            'default_expired_minutes' => (int) env('NTB_VA_DEFAULT_EXPIRED_MINUTES', 5),
            'default_expired_days' => (int) env('NTB_VA_DEFAULT_EXPIRED_DAYS', 0),
            'default_expired_hours' => (int) env('NTB_VA_DEFAULT_EXPIRED_HOURS', 0),
            'timeout_seconds' => (int) env('NTB_VA_TIMEOUT_SECONDS', 30),
            'production' => env('PRODUCTION', false),
            'has_secret_key' => !empty(env('USER_SECRET', '')),
        ];

        $endpointMeta = [
            [
                'key' => 'token',
                'name' => 'token',
                'path' => '/token',
                'description' => 'Mengambil token autentikasi untuk dipakai di header endpoint NTBVA lainnya.',
            ],
            [
                'key' => 'va',
                'name' => 'va',
                'path' => '/va',
                'description' => 'Membuat virtual account baru untuk tagihan pendaftaran atau daftar ulang.',
            ],
            [
                'key' => 'inqva',
                'name' => 'inqva',
                'path' => '/inqva',
                'description' => 'Memeriksa detail virtual account yang sudah dibuat (status dan data tagihan).',
            ],
            [
                'key' => 'cekstatus',
                'name' => 'cekstatus',
                'path' => '/cekstatus',
                'description' => 'Mengecek histori/status pembayaran VA berdasarkan nomor VA dan tanggal pembayaran.',
            ],
            [
                'key' => 'flag',
                'name' => 'flag',
                'path' => '/flag',
                'description' => 'Flagging adalah proses setelah pembayaran dilakukan, maka secara otomatis akan melakukan callback/notifikasi ke kampus.',
            ],
            [
                'key' => 'updateva',
                'name' => 'updateva',
                'path' => '/updateva',
                'description' => 'Memperbarui data virtual account yang sudah ada, seperti tagihan dan masa berlaku.',
            ],
            [
                'key' => 'testbayar',
                'name' => 'testbayar',
                'path' => '/TestBayar',
                'description' => 'Simulator pembayaran host-to-host untuk trigger pembayaran VA pada mode development.',
            ],
        ];

        $apiLogs = VaApiLog::latest()->take(50)->get();

        $transactions = Pembayaran::whereNotNull('va_number')
            ->with(['tagihan' => function ($q) {
                $q->with('mahasiswa');
            }, 'metodePembayaran'])
            ->latest()
            ->get()
            ->map(function ($pembayaran) {
                $tagihan = $pembayaran->tagihan;
                $mahasiswa = $tagihan?->mahasiswa;

                return [
                    'id' => $pembayaran->id,
                    'waktu' => $pembayaran->created_at->format('d/m/Y, H.i'),
                    'waktu_expired' => $pembayaran->va_expired_at?->format('d/m/Y, H.i'),
                    'calon_mahasiswa' => [
                        'nama' => $mahasiswa->nama_lengkap ?? '-',
                        'nim' => $mahasiswa->nim ?? '-',
                        'jurusan' => $mahasiswa->jurusan ?? '-',
                    ],
                    'nomor' => $pembayaran->id,
                    'va' => [
                        'full' => $pembayaran->va_number,
                        'prefix' => substr($pembayaran->va_number, 0, 3),
                        'suffix' => substr($pembayaran->va_number, 3),
                    ],
                    'tagihan' => [
                        'nominal' => $pembayaran->jumlah_bayar,
                        'semester' => $tagihan->semester ?? null,
                        'tahun_akademik' => $tagihan->tahun_akademik ?? '-',
                        'keterangan' => $tagihan->keterangan ?? '-',
                    ],
                    'status' => $pembayaran->status,
                    'bayar' => $pembayaran->status === 'dikonfirmasi' ? $pembayaran->jumlah_bayar : null,
                    'metode' => $pembayaran->metodePembayaran?->nama_metode ?? '-',
                ];
            });

        $pendaftaran = $transactions->filter(fn ($t) => ($t['tagihan']['semester'] ?? 0) <= 1)->values();
        $daftarUlang = $transactions->filter(fn ($t) => ($t['tagihan']['semester'] ?? 0) > 1)->values();

        return Inertia::render('Admin/Operations/Index', [
            'config' => $config,
            'endpointMeta' => $endpointMeta,
            'apiLogs' => $apiLogs,
            'vaTransactions' => [
                'pendaftaran' => $pendaftaran,
                'daftar_ulang' => $daftarUlang,
                'all' => $transactions,
            ],
        ]);
    }

    public function testToken()
    {
        $urlBase = config('services.vantb.url_base', env('URL_VANTB', ''));
        $userId = config('services.vantb.user_id', env('USER_ID', ''));
        $userSecret = config('services.vantb.user_secret', env('USER_SECRET', ''));
        $idMitra = config('services.vantb.id_mitra', env('ID_MITRA', ''));
        $timeout = (int) env('NTB_VA_TIMEOUT_SECONDS', 30);

        $url = rtrim($urlBase, '/') . '/token';
        $requestBody = [
            'user_id' => $userId,
            'user_secret' => $userSecret,
            'id_mitra' => $idMitra,
        ];

        return $this->hitEndpoint($url, $requestBody, $timeout);
    }

    public function testEndpoint(Request $request)
    {
        $endpoint = $request->input('endpoint');
        $extraParams = $request->input('params', []);

        $urlBase = config('services.vantb.url_base', env('URL_VANTB', ''));
        $userId = config('services.vantb.user_id', env('USER_ID', ''));
        $userSecret = config('services.vantb.user_secret', env('USER_SECRET', ''));
        $idMitra = config('services.vantb.id_mitra', env('ID_MITRA', ''));
        $idProduk = config('services.vantb.id_produk', env('ID_PRODUK', ''));
        $timeout = (int) env('NTB_VA_TIMEOUT_SECONDS', 30);

        set_time_limit($timeout * 2 + 10);

        $pathMap = [
            'va' => '/va',
            'inqva' => '/inqva',
            'cekstatus' => '/cekstatus',
            'flag' => '/flag',
            'updateva' => '/updateva',
            // 'testbayar' => '/TestBayar', // REMOVED: Bank NTB closed this endpoint (405 Method Not Allowed)
        ];

        if (!isset($pathMap[$endpoint])) {
            // Handle 'testbayar' endpoint locally without HTTP request to Bank
            // Bank NTB Syariah has closed the /TestBayar API endpoint
            if ($endpoint === 'testbayar') {
                return $this->handleTestBayarLocally($extraParams);
            }
            return response()->json(['success' => false, 'message' => 'Unknown endpoint'], 400);
        }

        $url = rtrim($urlBase, '/') . $pathMap[$endpoint];

        // Always fetch fresh token
        try {
            $tokenResponse = \Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post(rtrim($urlBase, '/') . '/token', [
                    'user_id' => $userId,
                    'user_secret' => $userSecret,
                    'id_mitra' => $idMitra,
                ]);
            $tokenBody = $tokenResponse->json();
            $token = $tokenBody['data']['token'] ?? null;
        } catch (\Exception $e) {
            $token = null;
            $tokenBody = ['message' => 'Connection failed: ' . $e->getMessage()];
        }

        if (!$token) {
            return response()->json([
                'success' => false,
                'status' => 0,
                'message' => 'Gagal mengambil token: ' . ($tokenBody['message'] ?? 'Unknown'),
                'data' => null,
                'request' => ['user_id' => $userId, 'id_mitra' => $idMitra],
            ]);
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'token' => $token,
        ];

        // LANGKAH 1 & 2: Dilarang pakai $request->all() — mapping manual + type-casting paksa
        // Untuk endpoint 'va', gunakan strict payload dengan (string) casting
        if ($endpoint === 'va') {
            // Read from params (sent from frontend as 'params' key)
            $params = $extraParams;
            $strictPayload = [
                'va'               => (string) ($params['va'] ?? ''),
                'id_mitra'         => (string) $idMitra,
                'id_produk'        => (string) $idProduk,
                'name'             => (string) ($params['name'] ?? ''),
                'billing_type'     => (string) ($params['billing_type'] ?? 'c'),
                'email'            => (string) ($params['email'] ?? ''),
                'phone'            => (string) ($params['phone'] ?? ''),
                'datetime_expired' => (string) ($params['datetime_expired'] ?? ''),
                'description'      => (string) ($params['description'] ?? ''),
                'tagihan'          => (string) ($params['tagihan'] ?? ''),
            ];

            // LANGKAH 3: Handle field kosong — unset jika string kosong (harus sama dengan CLI)
            if ($strictPayload['email'] === '') unset($strictPayload['email']);
            if ($strictPayload['phone'] === '') unset($strictPayload['phone']);
            if ($strictPayload['description'] === '') unset($strictPayload['description']);

            $body = $strictPayload;
        } else {
            // Untuk endpoint lain, gunakan array_merge + sanitasi
            $body = array_merge([
                'id_mitra' => $idMitra,
                'id_produk' => $idProduk,
            ], $extraParams);

            // Sanitize: trim all string values
            $sanitize = function(array $arr): array {
                $result = [];
                foreach ($arr as $k => $v) {
                    $result[$k] = is_string($v) ? trim($v) : (is_array($v) ? $sanitize($v) : $v);
                }
                return $result;
            };
            $body = $sanitize($body);
        }

        $rawJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $secretKey = env('SECRET_KEY_SIGNATURE', '');
        if ($secretKey && $endpoint !== 'token') {
            $headers['signature'] = hash_hmac('sha256', $rawJson, $secretKey);
        }

        // Transport layer logging — verify signature string === body string
        \Illuminate\Support\Facades\Log::channel('bankntb')->info('TEST ENDPOINT REQUEST [RAW TRANSPORT]', [
            'url' => $url,
            'endpoint' => $endpoint,
            'signature_input_string' => $rawJson,
            'data_to_sign' => $secretKey ? ($secretKey . $rawJson) : 'N/A',
            'body_sent_to_client' => $rawJson,
            'strings_identical' => true,
            'raw_json_bytes' => strlen($rawJson),
            'raw_json_hex' => bin2hex($rawJson),
            'signature' => $headers['signature'] ?? 'N/A',
            'sig_key_len' => strlen($secretKey),
        ]);

        $startTime = microtime(true);

        try {
            $guzzle = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'verify' => false,
            ]);

            $response = $guzzle->post($url, [
                'headers' => $headers,
                'body' => $rawJson,
            ]);

            $respBody = json_decode($response->getBody()->getContents(), true);

            \Illuminate\Support\Facades\Log::channel('bankntb')->info('TEST ENDPOINT RESPONSE', [
                'status' => $response->getStatusCode(),
                'rcode' => $respBody['rCode'] ?? null,
                'message' => $respBody['message'] ?? null,
                'resp' => $respBody,
            ]);

            $duration = round((microtime(true) - $startTime) * 1000);
            $success = ($respBody['rCode'] ?? '') === '000';
            
            // If flag endpoint succeeds, automatically update database
            // This ensures admin VA status reflects "Lunas" immediately
            if ($success && $endpoint === 'flag') {
                $vaNumber = $body['va'] ?? null;
                if ($vaNumber) {
                    try {
                        $pembayaran = Pembayaran::where('va_number', $vaNumber)->first();
                        if ($pembayaran && $pembayaran->status !== 'dikonfirmasi') {
                            $pembayaran->update([
                                'status' => 'dikonfirmasi',
                                'verified_at' => now(),
                            ]);
                            if ($pembayaran->tagihan) {
                                $pembayaran->tagihan->update(['status' => 'sudah_dibayar']);
                            }
                        }
                    } catch (\Exception $dbEx) {
                        \Illuminate\Support\Facades\Log::warning('Auto-update pembayaran failed after flag: ' . $dbEx->getMessage());
                    }
                }
            }

            try {
                VaApiLog::create([
                    'endpoint' => 'test-' . $endpoint,
                    'success' => $success,
                    'status_code' => $response->getStatusCode(),
                    'rcode' => $respBody['rCode'] ?? null,
                    'message' => $respBody['message'] ?? 'OK',
                    'request_data' => $body,
                    'response_data' => $respBody,
                    'duration_ms' => $duration,
                ]);
            } catch (\Exception $logEx) {
                file_put_contents(storage_path('logs/va-log-error.txt'), json_encode([
                    'error' => $logEx->getMessage(),
                    'endpoint' => 'test-' . $endpoint,
                    'rcode' => $respBody['rCode'] ?? null,
                    'request_data' => $body,
                    'response_data' => $respBody,
                ], JSON_PRETTY_PRINT));
            }

            return response()->json([
                'success' => $success,
                'status' => $response->getStatusCode(),
                'message' => $respBody['message'] ?? 'OK',
                'data' => $respBody,
                'request' => $body,
            ]);
        } catch (\Exception $e) {
            $statusCode = 0;
            $respBody = null;
            if ($e instanceof \GuzzleHttp\Exception\GuzzleException && $e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $respBody = json_decode($e->getResponse()->getBody()->getContents(), true);
            }

            $duration = round((microtime(true) - $startTime) * 1000);

            VaApiLog::create([
                'endpoint' => 'test-' . $endpoint,
                'success' => false,
                'status_code' => $statusCode,
                'rcode' => $respBody['rCode'] ?? null,
                'message' => $respBody['message'] ?? 'Connection failed: ' . $e->getMessage(),
                'request_data' => $body,
                'response_data' => $respBody,
                'duration_ms' => $duration,
            ]);

            return response()->json([
                'success' => false,
                'status' => $statusCode,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'data' => $respBody,
                'request' => $body,
            ]);
        }
    }

    private function hitEndpoint($url, $requestBody, $timeout)
    {
        $startTime = microtime(true);

        try {
            $response = \Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $requestBody);

            $duration = round((microtime(true) - $startTime) * 1000);
            $body = $response->json();

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'rcode' => $body['rCode'] ?? null,
                'message' => $body['message'] ?? ($response->successful() ? 'OK' : 'Failed'),
                'data' => $body,
                'request' => $requestBody,
                'duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success' => false,
                'status' => 0,
                'rcode' => null,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'data' => null,
                'request' => $requestBody,
                'duration_ms' => $duration,
            ]);
        }
    }

    /**
     * Simulate payment for testing purposes (bypasses Bank API).
     * Directly updates tagihan/pembayaran status to 'Lunas'/'dikonfirmasi' in database.
     * Used because Bank NTB dev API has closed /TestBayar endpoint.
     */
    public function simulatePayment(Request $request)
    {
        $vaNumber = $request->input('va');
        $amount = $request->input('amount');

        if (!$vaNumber) {
            return response()->json([
                'success' => false,
                'message' => 'VA number is required',
            ], 400);
        }

        try {
            // Find pembayaran by VA number
            $pembayaran = Pembayaran::where('va_number', $vaNumber)->first();

            if (!$pembayaran) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran with VA number ' . $vaNumber . ' not found',
                ], 404);
            }

            // Update pembayaran status to 'dikonfirmasi' (confirmed/paid)
            $pembayaran->update([
                'status' => 'dikonfirmasi',
                'verified_at' => now(),
            ]);

            // Update tagihan status to 'sudah_dibayar' (paid)
            if ($pembayaran->tagihan) {
                $pembayaran->tagihan->update(['status' => 'sudah_dibayar']);
            }

            // Log the simulated payment
            VaApiLog::create([
                'endpoint' => 'simulate-payment',
                'success' => true,
                'status_code' => 200,
                'rcode' => '000',
                'message' => 'Payment simulated successfully (bypass Bank API)',
                'request_data' => ['va' => $vaNumber, 'amount' => $amount],
                'response_data' => [
                    'va' => $pembayaran->va_number,
                    'status' => 'paid',
                    'amount' => $pembayaran->jumlah_bayar,
                ],
                'duration_ms' => 0,
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'rcode' => '000',
                'message' => 'Payment simulated successfully',
                'data' => [
                    'va' => $pembayaran->va_number,
                    'status' => 'paid',
                    'amount' => $pembayaran->jumlah_bayar,
                    'pembayaran_id' => $pembayaran->id,
                ],
                'request' => ['va' => $vaNumber, 'amount' => $amount],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to simulate payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch VA transaction history for the Transaksi History tab.
     */
    public function transaksiHistory()
    {
        $transactions = Pembayaran::whereNotNull('va_number')
            ->with([
                'tagihan' => function ($q) {
                    $q->with('mahasiswa');
                },
                'metodePembayaran',
            ])
            ->latest()
            ->get()
            ->map(function ($pembayaran) {
                $tagihan = $pembayaran->tagihan;
                $mahasiswa = $tagihan?->mahasiswa;

                return [
                    'id' => $pembayaran->id,
                    'waktu' => $pembayaran->created_at->format('d/m/Y, H.i'),
                    'waktu_expired' => $pembayaran->va_expired_at?->format('d/m/Y, H.i'),
                    'calon_mahasiswa' => [
                        'nama' => $mahasiswa->nama_lengkap ?? '-',
                        'nim' => $mahasiswa->nim ?? '-',
                        'jurusan' => $mahasiswa->jurusan ?? '-',
                    ],
                    'nomor' => $pembayaran->id,
                    'va' => [
                        'full' => $pembayaran->va_number,
                        'prefix' => substr($pembayaran->va_number, 0, 3),
                        'suffix' => substr($pembayaran->va_number, 3),
                    ],
                    'tagihan' => [
                        'nominal' => $pembayaran->jumlah_bayar,
                        'semester' => $tagihan->semester ?? null,
                        'tahun_akademik' => $tagihan->tahun_akademik ?? '-',
                        'keterangan' => $tagihan->keterangan ?? '-',
                    ],
                    'status' => $pembayaran->status,
                    'bayar' => $pembayaran->status === 'dikonfirmasi' ? $pembayaran->jumlah_bayar : null,
                    'metode' => $pembayaran->metodePembayaran?->nama_metode ?? '-',
                ];
            });

        $pendaftaran = $transactions->filter(fn ($t) => ($t['tagihan']['semester'] ?? 0) <= 1)->values();
        $daftarUlang = $transactions->filter(fn ($t) => ($t['tagihan']['semester'] ?? 0) > 1)->values();

        return response()->json([
            'pendaftaran' => $pendaftaran,
            'daftar_ulang' => $daftarUlang,
            'all' => $transactions,
        ]);
    }

    /**
     * Clear API logs.
     */
    public function clearApiLogs()
    {
        VaApiLog::truncate();
        return response()->json(['success' => true, 'message' => 'API logs cleared']);
    }

    /**
     * Monitoring transaksi data for the Monitoring tab.
     */
    public function monitoring()
    {
        $since = now()->subHours(24);

        $logs = VaApiLog::where('created_at', '>=', $since)->get();

        $total = $logs->count();
        $success = $logs->where('success', true)->count();
        $failed = $logs->where('success', false)->where('status_code', '>=', 400)->count();
        $error = $logs->where('success', false)->where('status_code', '<', 400)->count();

        $rCodeBreakdown = $logs->groupBy('rcode')
            ->map(fn ($group) => [
                'rcode' => $group->first()->rcode ?? '-',
                'description' => match ($group->first()->rcode) {
                    '000' => 'Success',
                    default => $group->first()->message ?? 'Unknown',
                },
                'total' => $group->count(),
            ])
            ->values();

        $recentLogs = VaApiLog::latest()->take(20)->get()->map(fn ($log) => [
            'id' => $log->id,
            'waktu' => $log->created_at->format('d/m/Y, H.i'),
            'endpoint' => $log->endpoint,
            'success' => $log->success,
            'rcode' => $log->rcode,
            'status_code' => $log->status_code,
            'duration_ms' => $log->duration_ms,
            'message' => $log->message,
        ]);

        return response()->json([
            'stats' => [
                'total_24' => $total,
                'success_24' => $success,
                'failed_24' => $failed,
                'error_24' => $error,
            ],
            'r_code_breakdown' => $rCodeBreakdown,
            'recent_logs' => $recentLogs,
        ]);
    }

    /**
     * Get transaction detail for modal.
     */
    public function transactionDetail($id)
    {
        $log = VaApiLog::findOrFail($id);

        return response()->json([
            'id' => $log->id,
            'endpoint' => $log->endpoint,
            'success' => $log->success,
            'status_code' => $log->status_code,
            'rcode' => $log->rcode,
            'message' => $log->message,
            'duration_ms' => $log->duration_ms,
            'request_data' => $log->request_data,
            'response_data' => $log->response_data,
            'created_at' => $log->created_at->format('d/m/Y, H.i.s'),
        ]);
    }
}
