<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\MahasiswaSemesterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class MahasiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Mahasiswa::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_aktif', $request->status === 'aktif');
        }

        if ($request->filled('jurusan')) {
            $query->where('jurusan', $request->jurusan);
        }

        if ($request->filled('semester')) {
            $filterSemester = (int) $request->semester;
            $taForFilter = TahunAkademik::aktif();
            if ($taForFilter) {
                $flag = strtolower($taForFilter->semester) === 'genap' ? 2 : 1;
                // Validasi parity: Ganjil hanya semester ganjil, Genap hanya genap
                if (($filterSemester - $flag) % 2 !== 0) {
                    $query->whereRaw('1=0'); // tidak ada angkatan yang cocok
                } else {
                    $startYear = (int) substr($taForFilter->nama, 0, 4);
                    $diff = ($filterSemester - $flag) / 2;
                    $angkatanTarget = $startYear - $diff;
                    $query->where('angkatan', $angkatanTarget);
                }
            } else {
                $query->where('semester', $filterSemester);
            }
        }

        if ($request->filled('angkatan')) {
            $query->where('angkatan', $request->angkatan);
        }

        // Sorting
        $allowedSorts = ['nim','nama_lengkap','jurusan','angkatan','semester'];
        $sort = $request->input('sort');
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        if ($sort && in_array($sort, $allowedSorts, true)) {
            if ($sort === 'semester') {
                // Semester tampil adalah semester_hitung (via service), jadi urutkan berdasarkan angkatan terbalik
                $taForSort = TahunAkademik::aktif();
                if ($taForSort) {
                    // semester asc -> angkatan desc, dan sebaliknya
                    $query->orderBy('angkatan', $direction === 'asc' ? 'desc' : 'asc');
                } else {
                    $query->orderBy('semester', $direction);
                }
            } else {
                $query->orderBy($sort, $direction);
            }
        } else {
            $query->latest();
        }

        $mahasiswas = $query->paginate(15)->withQueryString();

        // Hitung semester otomatis via service (SIAKAD flag 1=ganjil, 2=genap)
        $semesterService = app(MahasiswaSemesterService::class);
        $taAktif = TahunAkademik::aktif();
        $mahasiswas->getCollection()->transform(function ($mhs) use ($semesterService, $taAktif) {
            $mhs->semester_hitung = $semesterService->hitung($mhs, $taAktif);
            // semester_label untuk badge
            $mhs->semester_label = $mhs->semester_hitung % 2 === 1 ? 'Ganjil' : 'Genap';
            return $mhs;
        });

        // Filter options untuk dropdown semester -> dari hitungan dinamis (bukan kolom statis)
        if ($mahasiswas->getCollection()->isNotEmpty()) {
            $semesterList = $mahasiswas->getCollection()->pluck('semester_hitung')->unique()->sort()->values()->toArray();
            // Tambah semester dari semua data agar dropdown lengkap
            if (empty($semesterList)) {
                $semesterList = Mahasiswa::distinct()->pluck('semester')->sort()->values()->toArray();
            }
        } else {
            $semesterList = Mahasiswa::distinct()->pluck('semester')->sort()->values()->toArray();
        }
        $jurusanList = Mahasiswa::distinct()->pluck('jurusan')->sort()->values()->toArray();

        // Angkatan selalu tersedia (rentang tahun) walau tabel mahasiswa kosong,
        // karena NIM diawali 2 digit kode angkatan (mis. 25 -> angkatan 2025).
        $existingAngkatan = Mahasiswa::distinct()->pluck('angkatan')->sortDesc()->values()->toArray();
        $tahunIni = (int) date('Y');
        $generatedAngkatan = range($tahunIni, $tahunIni - 15);
        $angkatanList = array_values(array_unique(array_merge($generatedAngkatan, $existingAngkatan)));
        rsort($angkatanList);

        return Inertia::render('Admin/Mahasiswa/Index', [
            'mahasiswas' => $mahasiswas,
            'filters' => $request->only(['search', 'status', 'jurusan', 'semester', 'angkatan', 'sort', 'direction']),
            'filterOptions' => [
                'jurusan' => $jurusanList,
                'angkatan' => $angkatanList,
                'semester' => $semesterList,
            ],
        ]);
    }

    public function show($id): Response
    {
        $mahasiswa = Mahasiswa::with(['user', 'tagihans'])->findOrFail($id);
        $semesterService = app(MahasiswaSemesterService::class);
        $hitung = $semesterService->hitung($mahasiswa);

        return Inertia::render('Admin/Mahasiswa/Show', [
            'mahasiswa' => [
                'id' => $mahasiswa->id,
                'nim' => $mahasiswa->nim,
                'nama_lengkap' => $mahasiswa->nama_lengkap,
                'jurusan' => $mahasiswa->jurusan,
                'angkatan' => $mahasiswa->angkatan,
                'semester' => $hitung,
                'semester_tersimpan' => $mahasiswa->semester,
                'semester_label' => $hitung % 2 === 1 ? 'Ganjil' : 'Genap',
                'status_aktif' => $mahasiswa->status_aktif,
            ],
            'tagihans' => $mahasiswa->tagihans,
        ]);
    }

    public function impersonate(Request $request, $id)
    {
        $mahasiswa = Mahasiswa::with('user')->findOrFail($id);

        // Cari atau buat user mahasiswa
        $user = $mahasiswa->user;
        if (!$user) {
            $email = strtolower($mahasiswa->nim) . '@ubg.ac.id';
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $mahasiswa->nama_lengkap,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'mahasiswa',
                ]);
                $mahasiswa->update(['user_id' => $user->id]);
            } else {
                $mahasiswa->update(['user_id' => $user->id]);
            }
        }

        // Pastikan role mahasiswa
        if ($user->role !== 'mahasiswa') {
            $user->update(['role' => 'mahasiswa']);
        }

        // Simpan admin asli untuk kembali
        $request->session()->put('impersonator_id', $request->user()->id);
        $request->session()->put('impersonator_role', $request->user()->role);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('mahasiswa.dashboard')->with('success', 'Login sebagai ' . $mahasiswa->nama_lengkap . ' (' . $mahasiswa->nim . ')');
    }

    public function leaveImpersonate(Request $request)
    {
        if (!$request->session()->has('impersonator_id')) {
            return redirect()->route('login');
        }

        $adminId = $request->session()->pull('impersonator_id');
        $request->session()->forget('impersonator_role');

        $admin = User::find($adminId);
        if (!$admin) {
            Auth::logout();
            return redirect()->route('admin.login')->with('error', 'Sesi admin tidak ditemukan, silakan login kembali.');
        }

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.mahasiswa.index')->with('success', 'Kembali ke akun admin.');
    }
}
