<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the mahasiswa login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Display the admin login view.
     */
    public function createAdmin(): Response
    {
        if (auth()->check() && auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Auth/AdminLogin', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle admin login (email + password).
     */
    public function storeAdmin(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $user = Auth::user();
        if ($user->role !== 'admin') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Anda tidak memiliki akses admin.'],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Handle Siakad login with NIM + password.
     */
    public function storeSiakad(Request $request): RedirectResponse
    {
        $request->validate([
            'nim' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Fetch CSRF cookie first, then login (same session)
        $csrfUrl = config('services.siakad.srfcookie', env('BASE_API_SIAKAD_SRFCOOKIE'));
        $loginUrl = config('services.siakad.login_mahasiswa', env('BASE_API_SIAKAD_LOGIN_MAHASISWA'));
        $baseUrl = config('services.siakad.base_url', env('BASE_API_SIAKAD'));

        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 30,
        ]);

        // Step 1: Fetch CSRF cookie (sets session + XSRF-TOKEN)
        $csrfResponse = $client->get($csrfUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'Referer' => $baseUrl . '/',
                'Origin' => $baseUrl,
            ],
        ]);

        // Extract cookies from Set-Cookie headers
        $setCookies = $csrfResponse->getHeader('Set-Cookie');
        $xsrfToken = '';
        $laravelSession = '';
        foreach ($setCookies as $cookie) {
            if (preg_match('/^XSRF-TOKEN=([^;]+)/', $cookie, $m)) {
                $xsrfToken = urldecode($m[1]);
            }
            if (preg_match('/^laravel-session=([^;]+)/', $cookie, $m)) {
                $laravelSession = $m[1];
            }
        }

        // Step 2: Login with raw Cookie header
        try {
            $response = $client->post($loginUrl, [
                'json' => [
                    'nim' => $request->nim,
                    'password' => $request->password,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-XSRF-TOKEN' => $xsrfToken,
                    'Cookie' => "XSRF-TOKEN=$xsrfToken; laravel-session=$laravelSession",
                    'Referer' => $baseUrl . '/',
                    'Origin' => $baseUrl,
                ],
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw ValidationException::withMessages([
                'nim' => ['NIM atau password salah.'],
            ]);
        }

        $body = json_decode($response->getBody()->getContents(), true);

        // API returns {"status":true,"message":"Login successful"} on success
        if (empty($body['status']) || $body['status'] !== true) {
            throw ValidationException::withMessages([
                'nim' => [$body['message'] ?? 'NIM atau password salah.'],
            ]);
        }

        // Find or create local user + mahasiswa record
        $nim = $request->nim;
        $email = strtolower($nim) . '@ubg.ac.id';

        // Check if mahasiswa already linked to a user (from sync)
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();
        $user = $mahasiswa && $mahasiswa->user ? $mahasiswa->user : User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Mahasiswa ' . $nim,
                'email' => $email,
                'password' => Hash::make($request->password),
                'role' => 'mahasiswa',
            ]);
        }

        if (!$mahasiswa) {
            // Derive angkatan from NIM (first 2 digits)
            $angkatan = null;
            if (strlen($nim) >= 2) {
                $prefix = (int) substr($nim, 0, 2);
                $angkatan = $prefix > 50 ? 1900 + $prefix : 2000 + $prefix;
            }

            $mahasiswa = Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => $nim,
                'nama_lengkap' => $user->name,
                'jurusan' => '-',
                'angkatan' => $angkatan,
                'semester' => 1,
                'status_aktif' => true,
            ]);
        } else {
            // Always link to current user
            $mahasiswa->update(['user_id' => $user->id]);
        }

        // Sync profile from Siakad API
        $this->syncProfile($client, $nim, $user, $mahasiswa);

        // Refresh user to pick up name changes from sync
        $user->refresh();

        // Login with Laravel Auth
        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('mahasiswa.dashboard', absolute: false));
    }

    /**
     * Fetch mahasiswa profile from Siakad and sync locally.
     */
    private function syncProfile(\GuzzleHttp\Client $client, string $nim, User $user, Mahasiswa $mahasiswa): void
    {
        try {
            $profileUrl = config('services.siakad.mahasiswa_nim', env('BASE_API_SIAKAD_MAHASISWA_NIM'));
            $profileResponse = $client->get($profileUrl, [
                'query' => ['nim' => $nim],
                'headers' => ['Accept' => 'application/json'],
            ]);

            $profileBody = json_decode($profileResponse->getBody()->getContents(), true);
            $data = $profileBody['data'] ?? null;

            if (!$data) {
                return;
            }

            // Update user name
            $nama = $data['nama_mahasiswa'] ?? $data['nama'] ?? null;
            if ($nama && $user->name !== $nama) {
                $user->update(['name' => $nama]);
            }

            // Update mahasiswa profile
            $prodi = $data['nama_prodi'] ?? null;
            $jurusan = is_array($prodi) ? ($prodi['nama_program_studi'] ?? '-') : ($data['jurusan'] ?? '-');
            $statusApi = $data['status'] ?? 'A';
            $statusAktif = strtoupper($statusApi) === 'A';

            $mahasiswa->update([
                'nama_lengkap' => $nama ?? $mahasiswa->nama_lengkap,
                'jurusan' => $jurusan,
                'status_aktif' => $statusAktif,
            ]);
        } catch (\Exception $e) {
            // Profile sync is non-critical, log and continue
            \Log::warning('Gagal sync profil mahasiswa: ' . $e->getMessage());
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
