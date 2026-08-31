<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SiakadController extends Controller
{
    private function getAuthToken()
    {
        $baseUrl = config('services.siakad.base_url', '');
        $loginUrl = config('services.siakad.login', '');
        $email = config('services.siakad.username', '');
        $password = config('services.siakad.password', '');

        if (!$baseUrl || !$loginUrl) {
            throw new \Exception('URL API Siakad belum dikonfigurasi di .env (BASE_API_SIAKAD / BASE_API_SIAKAD_LOGIN).');
        }

        if (!$email || !$password) {
            throw new \Exception('Username/Password Siakad belum dikonfigurasi di .env (USERNAME_SIAKAD / PASSWORD_SIAKAD).');
        }

        $client = Http::withOptions([
            'verify' => false,
        ])->timeout(15);

        $csrfResponse = $client->get($baseUrl . '/sanctum/csrf-cookie');

        if ($csrfResponse->failed()) {
            throw new \Exception('Gagal mengambil CSRF cookie: HTTP ' . $csrfResponse->status());
        }

        $loginResponse = $client->post($loginUrl, [
            'email' => $email,
            'password' => $password,
        ]);

        if ($loginResponse->failed()) {
            $body = $loginResponse->json();
            throw new \Exception('Login gagal: ' . ($body['message'] ?? 'HTTP ' . $loginResponse->status()));
        }

        $loginData = $loginResponse->json();

        return $loginData['token'] ?? null;
    }

    /**
     * Resolve URL API mahasiswa per angkatan, dengan pesan error yang jelas
     * bila variabel belum dikonfigurasi di .env.
     */
    private function resolveMahasiswaAngkatanUrl(): string
    {
        $url = config('services.siakad.get_mahasiswa_angkatan', env('BASE_API_SIAKAD_GET_MAHASISWA_ANGKATAN', ''));

        if (trim($url) === '') {
            throw new \Exception('BASE_API_SIAKAD_GET_MAHASISWA_ANGKATAN belum dikonfigurasi di .env server.');
        }

        return trim($url);
    }

    /**
     * Fetch active mahasiswa for one angkatan: GET /api/v1/mahasiswa-get?status=A&angkatan=YYYY.
     * Endpoint ini jauh lebih ringan daripada /api/v1/mahasiswa (yang menarik seluruh data).
     */
    private function fetchMahasiswaByAngkatan(string $token, string $angkatan): array
    {
        $url = $this->resolveMahasiswaAngkatanUrl();

        $client = Http::withOptions([
            'verify' => false,
        ])->timeout(120)->withToken($token)->withHeaders([
            'Accept' => 'application/json',
        ]);

        $response = $client->get($url, [
            'status' => 'A',
            'angkatan' => $angkatan,
        ]);

        if ($response->failed()) {
            throw new \Exception('Gagal mengambil data mahasiswa angkatan ' . $angkatan . ': HTTP ' . $response->status());
        }

        $body = $response->json();

        if (!is_array($body)) {
            return [];
        }

        // Menangani berbagai bentuk respons:
        // 1. ["data": [...]] (bentuk umum API Siakad)
        // 2. ["data_preview": [...]] (bentuk saat test connection)
        // 3. ["data": {"data": [...], "total": n}] (wrapper paginasi)
        // 4. array langsung
        foreach (['data', 'data_preview', 'mahasiswa'] as $key) {
            if (isset($body[$key]) && is_array($body[$key])) {
                $inner = $body[$key];
                if (isset($inner['data']) && is_array($inner['data'])) {
                    return $inner['data'];
                }
                return $inner;
            }
        }

        return $body;
    }

    public function syncMahasiswa(Request $request)
    {
        $angkatan = trim((string) $request->input('angkatan'));

        if ($angkatan === '') {
            return back()->with('sync_result', [
                'success' => false,
                'message' => 'Pilih angkatan terlebih dahulu untuk sinkronisasi.',
            ]);
        }

        // Ukuran batch: 100 / 200 / 500 / semua
        $batchRaw = strtolower(trim((string) $request->input('batch', '100')));
        if ($batchRaw === 'semua') {
            $batchSize = PHP_INT_MAX;
        } else {
            $batchSize = (int) $batchRaw;
            if (!in_array($batchSize, [100, 200, 500], true)) {
                $batchSize = 100;
            }
        }

        // Proses 1000+ data butuh waktu; perpanjang batas agar tidak di-kill PHP-FPM (502).
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        try {
            $token = $this->getAuthToken();

            if (!$token) {
                return back()->with('sync_result', [
                    'success' => false,
                    'message' => 'Gagal mendapatkan token autentikasi.',
                ]);
            }

            $mahasiswaList = $this->fetchMahasiswaByAngkatan($token, $angkatan);

            if (empty($mahasiswaList)) {
                return back()->with('sync_result', [
                    'success' => false,
                    'message' => 'Tidak ada data diterima dari API untuk angkatan ' . $angkatan . '.',
                ]);
            }

            $total = count($mahasiswaList);

            // Offset lanjutan = jumlah yang sudah tersinkron untuk angkatan ini
            // (data di-sinkron berurutan, sehingga count = progres pembuatan).
            $alreadyCount = Mahasiswa::where('angkatan', $angkatan)->count();
            $offset = $batchRaw === 'semua' ? 0 : min($alreadyCount, $total);

            $chunk = array_slice($mahasiswaList, $offset, $batchSize);

            // Jika semua sudah DIBUAT, jangan skip — lakukan update pass agar data yang
            // berubah di Siakad tetap tersinkron (batch update, bukan cuma "selesai").
            $isUpdatePass = false;
            if (empty($chunk) && $batchRaw !== 'semua') {
                $chunk = array_slice($mahasiswaList, 0, $batchSize);
                $offset = 0;
                $isUpdatePass = true;
            }

            if (empty($chunk)) {
                return back()->with('sync_result', [
                    'success' => true,
                    'message' => 'Semua mahasiswa angkatan ' . $angkatan . ' sudah tersinkron (' . $total . ' data).',
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => [],
                    'total' => $total,
                    'done' => $total,
                    'remaining' => 0,
                ]);
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            // Preload NIM yang sudah ada dalam batch ini (1 query, bukan 1 query per mahasiswa)
            $nims = array_values(array_filter(array_column($chunk, 'nim')));
            $existingByNim = Mahasiswa::whereIn('nim', $nims)->get()->keyBy('nim');

            DB::beginTransaction();

            foreach ($chunk as $item) {
                try {
                    $nim = $item['nim'] ?? null;

                    if (!$nim) {
                        $skipped++;
                        continue;
                    }

                    $nama = $item['nama_mahasiswa'] ?? $item['nama_lengkap'] ?? $item['nama'] ?? '';
                    $prodiObj = $item['nama_prodi'] ?? null;
                    $jurusan = $prodiObj['nama_program_studi'] ?? $item['jurusan'] ?? $item['prodi'] ?? '';
                    $programStudiKode = $prodiObj['kode_program_studi'] ?? $item['program_studi_kode'] ?? null;
                    $emailMhs = $item['email'] ?? null;
                    $telepon = $item['telepon'] ?? null;
                    $statusApi = $item['status'] ?? 'A';
                    $status_aktif = strtoupper($statusApi) === 'A';

                    $angkatanMhs = null;
                    if (strlen($nim) >= 2) {
                        $prefix = (int) substr($nim, 0, 2);
                        $angkatanMhs = $prefix > 50 ? 1900 + $prefix : 2000 + $prefix;
                    }

                    $semester = 1;

                    $existing = $existingByNim->get($nim);

                    if ($existing) {
                        $existing->update([
                            'nama_lengkap' => $nama,
                            'email' => $emailMhs,
                            'telepon' => $telepon,
                            'jurusan' => $jurusan,
                            'program_studi_kode' => $programStudiKode,
                            'angkatan' => $angkatanMhs,
                            'semester' => $semester,
                            'status_aktif' => $status_aktif,
                        ]);
                        $updated++;
                    } else {
                        // Email akun login tetap nim@ubt.ac.id agar login berbasis NIM tetap berfungsi;
                        // email asli dari Siakad disimpan di kolom mahasiswas.email.
                        $emailLogin = strtolower($nim) . '@ubt.ac.id';

                        $user = User::create([
                            'name' => $nama,
                            'email' => $emailLogin,
                            'password' => 'password',
                            'role' => 'mahasiswa',
                        ]);

                        Mahasiswa::create([
                            'user_id' => $user->id,
                            'nim' => $nim,
                            'nama_lengkap' => $nama,
                            'email' => $emailMhs,
                            'telepon' => $telepon,
                            'jurusan' => $jurusan,
                            'program_studi_kode' => $programStudiKode,
                            'angkatan' => $angkatanMhs,
                            'semester' => $semester,
                            'status_aktif' => $status_aktif,
                        ]);

                        // Tandai sudah dibuat agar tidak duplikat jika NIM muncul 2x di list
                        $existingByNim->put($nim, new Mahasiswa(['id' => 0]));
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'NIM ' . ($nim ?? '?') . ': ' . $e->getMessage();
                }
            }

            DB::commit();

            $processedEnd = min($offset + count($chunk), $total);
            $remaining = max(0, $total - $processedEnd);

            if ($isUpdatePass) {
                $msg = "Update data angkatan {$angkatan}: {$updated} diperbarui";
                $msg .= " (batch update {$processedEnd} dari {$total}).";
                $msg .= " Klik sinkron lagi untuk update batch berikutnya.";
            } else {
                $msg = "Sinkron angkatan {$angkatan}: {$created} baru, {$updated} diperbarui";
                $msg .= " ({$offset}-{$processedEnd} dari {$total})";
                if ($remaining > 0) {
                    $msg .= ". Klik sinkron lagi untuk melanjutkan " . min($batchSize, $remaining) . " berikutnya.";
                } else {
                    $msg .= ". Semua data selesai.";
                }
            }

            return back()->with('sync_result', [
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total' => $total,
                'done' => $processedEnd,
                'remaining' => $remaining,
                'message' => $msg,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->with('sync_result', [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke API Siakad. Pastikan server aktif.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('sync_result', [
                'success' => false,
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage(),
            ]);
        }
    }

    public function testConnection(Request $request)
    {
        try {
            $token = $this->getAuthToken();

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mendapatkan token',
                ], 500);
            }

            $angkatan = trim((string) $request->query('angkatan', (string) (date('Y') - 1)));

            $url = $this->resolveMahasiswaAngkatanUrl();
            $response = Http::withOptions(['verify' => false])
                ->timeout(15)
                ->withToken($token)
                ->get($url, ['status' => 'A', 'angkatan' => $angkatan]);

            $body = $response->json();
            $inner = is_array($body) ? ($body['data'] ?? $body) : [];
            if (is_array($inner) && isset($inner['data']) && is_array($inner['data'])) {
                $inner = $inner['data'];
            }
            $pageData = is_array($inner) ? $inner : [];

            return response()->json([
                'status' => $response->status(),
                'success' => $response->successful(),
                'angkatan' => $angkatan,
                'total_records' => count($pageData),
                'total_pages' => 1,
                'data_preview' => array_slice($pageData, 0, 3),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
