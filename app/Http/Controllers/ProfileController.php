<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
 use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $nim = $user->getNimAttribute();
        $mahasiswa = $nim ? Mahasiswa::where('nim', $nim)->first() : null;

        // Fetch fresh data from Siakad API
        if ($nim && $mahasiswa) {
            try {
                $profileUrl = config('services.siakad.mahasiswa_nim', env('BASE_API_SIAKAD_MAHASISWA_NIM'));
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->get($profileUrl, ['nim' => $nim]);

                $body = $response->json();
                $data = $body['data'] ?? null;

                if ($data) {
                    $nama = $data['nama_mahasiswa'] ?? $data['nama'] ?? null;
                    $prodi = $data['nama_prodi'] ?? null;
                    $jurusan = is_array($prodi) ? ($prodi['nama_program_studi'] ?? null) : ($data['jurusan'] ?? null);

                    $updates = [];
                    if ($nama) $updates['nama_lengkap'] = $nama;
                    if ($jurusan) $updates['jurusan'] = $jurusan;

                    if (!empty($updates)) {
                        $mahasiswa->update($updates);
                    }

                    // Sync user name too
                    if ($nama && $user->name !== $nama) {
                        $user->update(['name' => $nama]);
                    }
                }
            } catch (\Exception $e) {
                // Non-critical, continue with local data
            }
        }

        return Inertia::render('Profile/Edit', [
            'mahasiswa' => $mahasiswa,
        ]);
    }
}
