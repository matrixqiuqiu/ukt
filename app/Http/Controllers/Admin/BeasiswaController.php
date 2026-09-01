<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beasiswa;
use App\Models\BeasiswaMahasiswa;
use App\Models\JenisBeasiswa;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\KomponenBiaya;
use App\Services\BeasiswaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\ExcelHelper;

class BeasiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Beasiswa::with(['tahunAkademik', 'komponenBiaya', 'jenisBeasiswa']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_beasiswa', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }
        if ($request->filled('jenis')) {
            // Support both legacy string jenis and new FK
            if (is_numeric($request->jenis)) {
                $query->where('jenis_beasiswa_id', $request->jenis);
            } else {
                $query->where('jenis', $request->jenis);
            }
        }
        if ($request->filled('status')) {
            $query->where('status_aktif', $request->status === 'aktif');
        }

        $beasiswas = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Beasiswa/Index', [
            'beasiswas' => $beasiswas,
            'filters' => $request->only(['search', 'jenis', 'status']),
            'tahunAkademiks' => TahunAkademik::orderBy('nama', 'desc')->get(['id', 'nama', 'semester']),
            'komponens' => KomponenBiaya::where('status_aktif', true)->get(['id', 'nama', 'kode']),
            'jenisBeasiswas' => JenisBeasiswa::where('status_aktif', true)->orderBy('kode')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:20|unique:beasiswas,kode',
            'nama_beasiswa' => 'required|string|max:255',
            'jenis' => 'nullable|string|max:50',
            'jenis_beasiswa_id' => 'nullable|exists:jenis_beasiswas,id',
            'sumber_dana' => 'required|in:internal,eksternal,pemerintah,kerjasama',
            'tahun_akademik_id' => 'nullable|exists:tahun_akademiks,id',
            'semester' => 'nullable|integer|in:1,2',
            'tipe_diskon' => 'required|in:persen,nominal,full',
            'nilai_diskon' => 'required|numeric|min:0',
            'komponen_biaya_id' => 'nullable|exists:komponen_biayas,id',
            'kuota' => 'required|integer|min:0',
            'tanggal_buka' => 'nullable|date',
            'tanggal_tutup' => 'nullable|date|after_or_equal:tanggal_buka',
            'deskripsi' => 'nullable|string',
            'status_aktif' => 'boolean',
        ]);

        // Sync jenis string dari master jika dipilih
        if (!empty($validated['jenis_beasiswa_id'])) {
            $jenisMaster = JenisBeasiswa::find($validated['jenis_beasiswa_id']);
            if ($jenisMaster) {
                $kodeMap = ['JB001'=>'prestasi','JB002'=>'tidak_mampu','JB003'=>'tahfidz','JB004'=>'kerjasama','JB005'=>'lain'];
                $validated['jenis'] = $kodeMap[$jenisMaster->kode] ?? strtolower(str_replace(' ', '_', $jenisMaster->nama));
            }
        }
        if (empty($validated['jenis'])) $validated['jenis'] = 'lain';

        // Sinkron semester dengan Tahun Akademik master (hindari bentrok: Tahun 2025/2026 Ganjil => semester 1)
        if (!empty($validated['tahun_akademik_id']) && !empty($validated['semester'])) {
            $ta = TahunAkademik::find($validated['tahun_akademik_id']);
            if ($ta) {
                $flag = strtolower($ta->semester) === 'genap' ? 2 : 1;
                if ((int)$validated['semester'] !== $flag) {
                    $validated['semester'] = $flag;
                }
            }
        } elseif (!empty($validated['tahun_akademik_id']) && empty($validated['semester'])) {
            $ta = TahunAkademik::find($validated['tahun_akademik_id']);
            if ($ta) {
                $validated['semester'] = strtolower($ta->semester) === 'genap' ? 2 : 1;
            }
        }

        if ($validated['tipe_diskon'] === 'persen' && $validated['nilai_diskon'] > 100) {
            return back()->withErrors(['nilai_diskon' => 'Diskon persen maksimal 100.']);
        }
        if ($validated['tipe_diskon'] === 'full') {
            $validated['nilai_diskon'] = 0;
        }

        Beasiswa::create($validated);

        return back()->with('success', 'Beasiswa berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $beasiswa = Beasiswa::findOrFail($id);

        $validated = $request->validate([
            'kode' => 'required|string|max:20|unique:beasiswas,kode,' . $beasiswa->id,
            'nama_beasiswa' => 'required|string|max:255',
            'jenis' => 'nullable|string|max:50',
            'jenis_beasiswa_id' => 'nullable|exists:jenis_beasiswas,id',
            'sumber_dana' => 'required|in:internal,eksternal,pemerintah,kerjasama',
            'tahun_akademik_id' => 'nullable|exists:tahun_akademiks,id',
            'semester' => 'nullable|integer|in:1,2',
            'tipe_diskon' => 'required|in:persen,nominal,full',
            'nilai_diskon' => 'required|numeric|min:0',
            'komponen_biaya_id' => 'nullable|exists:komponen_biayas,id',
            'kuota' => 'required|integer|min:0',
            'tanggal_buka' => 'nullable|date',
            'tanggal_tutup' => 'nullable|date|after_or_equal:tanggal_buka',
            'deskripsi' => 'nullable|string',
            'status_aktif' => 'boolean',
        ]);

        if (!empty($validated['jenis_beasiswa_id'])) {
            $jenisMaster = JenisBeasiswa::find($validated['jenis_beasiswa_id']);
            if ($jenisMaster) {
                $kodeMap = ['JB001'=>'prestasi','JB002'=>'tidak_mampu','JB003'=>'tahfidz','JB004'=>'kerjasama','JB005'=>'lain'];
                $validated['jenis'] = $kodeMap[$jenisMaster->kode] ?? strtolower(str_replace(' ', '_', $jenisMaster->nama));
            }
        }
        if (empty($validated['jenis'])) $validated['jenis'] = $beasiswa->jenis ?? 'lain';

        if (!empty($validated['tahun_akademik_id']) && !empty($validated['semester'])) {
            $ta = TahunAkademik::find($validated['tahun_akademik_id']);
            if ($ta) {
                $flag = strtolower($ta->semester) === 'genap' ? 2 : 1;
                if ((int)$validated['semester'] !== $flag) {
                    $validated['semester'] = $flag;
                }
            }
        } elseif (!empty($validated['tahun_akademik_id']) && empty($validated['semester'])) {
            $ta = TahunAkademik::find($validated['tahun_akademik_id']);
            if ($ta) {
                $validated['semester'] = strtolower($ta->semester) === 'genap' ? 2 : 1;
            }
        }

        if ($validated['tipe_diskon'] === 'persen' && $validated['nilai_diskon'] > 100) {
            return back()->withErrors(['nilai_diskon' => 'Diskon persen maksimal 100.']);
        }
        if ($validated['tipe_diskon'] === 'full') {
            $validated['nilai_diskon'] = 0;
        }

        $oldKode = $beasiswa->kode;
        $oldNama = $beasiswa->nama_beasiswa;
        $beasiswa->update($validated);

        // Jika kode/nama berubah, sinkronkan catatan pembayaran & keterangan tagihan terkait (agar tidak tampil old kode di admin/pembayaran/{id})
        if ($oldKode !== $beasiswa->kode || $oldNama !== $beasiswa->nama_beasiswa) {
            $tagihanIds = \App\Models\BeasiswaMahasiswa::where('beasiswa_id', $beasiswa->id)->pluck('tagihan_id')->filter();
            if ($tagihanIds->isNotEmpty()) {
                \App\Models\Pembayaran::whereIn('tagihan_id', $tagihanIds)
                    ->where('catatan_admin', 'like', '%'.$oldKode.'%')
                    ->update(['catatan_admin' => \Illuminate\Support\Facades\DB::raw("REPLACE(catatan_admin, '".$oldKode."', '".$beasiswa->kode."')")]);
                // Juga update nama di catatan jika ada
                \App\Models\Pembayaran::whereIn('tagihan_id', $tagihanIds)
                    ->where('nama_pengirim', 'like', '%'.$oldKode.'%')
                    ->update(['nama_pengirim' => \Illuminate\Support\Facades\DB::raw("REPLACE(nama_pengirim, '".$oldKode."', '".$beasiswa->kode."')")]);
                \App\Models\Tagihan::whereIn('id', $tagihanIds)
                    ->where('keterangan', 'like', '%'.$oldKode.'%')
                    ->update(['keterangan' => \Illuminate\Support\Facades\DB::raw("REPLACE(keterangan, '".$oldKode."', '".$beasiswa->kode."')")]);
            }
        }

        return back()->with('success', 'Beasiswa berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $beasiswa = Beasiswa::findOrFail($id);
        if ($beasiswa->assignments()->exists()) {
            return back()->with('error', 'Beasiswa masih memiliki penerima, tidak bisa dihapus.');
        }
        $beasiswa->delete();
        return back()->with('success', 'Beasiswa berhasil dihapus.');
    }

    public function toggle($id)
    {
        $b = Beasiswa::findOrFail($id);
        $b->update(['status_aktif' => !$b->status_aktif]);
        return back()->with('success', 'Status beasiswa diperbarui.');
    }

    // Assignment
    public function assignments($id)
    {
        $beasiswa = Beasiswa::with(['tahunAkademik', 'jenisBeasiswa'])->findOrFail($id);
        $assignments = BeasiswaMahasiswa::where('beasiswa_id', $id)->with(['mahasiswa', 'tagihan'])->latest()->paginate(15);
        // Enrich tagihan nominal info for sync status
        $assignments->getCollection()->transform(function ($a) {
            if ($a->tagihan) {
                $a->tagihan->setAttribute('nominal_fmt', 'Rp ' . number_format($a->tagihan->nominal, 0, ',', '.'));
            }
            return $a;
        });
        return Inertia::render('Admin/Beasiswa/Assignments', [
            'beasiswa' => $beasiswa,
            'assignments' => $assignments,
        ]);
    }

    public function syncTagihan($id)
    {
        $beasiswa = Beasiswa::findOrFail($id);
        $result = app(BeasiswaService::class)->syncTagihan($beasiswa);
        return back()->with('success', "Sinkron selesai: {$result['synced']} tagihan diperbarui, {$result['skipped']} dilewati.");
    }

    public function assign(Request $request, $id)
    {
        $beasiswa = Beasiswa::findOrFail($id);
        $validated = $request->validate([
            'nim' => 'required|string|exists:mahasiswas,nim',
            'tagihan_id' => 'nullable|exists:tagihans,id',
        ]);

        $mahasiswa = Mahasiswa::where('nim', $validated['nim'])->firstOrFail();

        $tagihan = null;
        if (!empty($validated['tagihan_id'])) {
            $tagihan = \App\Models\Tagihan::findOrFail($validated['tagihan_id']);
        }

        try {
            app(BeasiswaService::class)->assign($mahasiswa, $beasiswa, $tagihan, 'disetujui', auth()->id());
            return back()->with('success', 'Mahasiswa berhasil ditambahkan ke beasiswa.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function revoke($beasiswaId, $assignmentId)
    {
        $assignment = BeasiswaMahasiswa::where('beasiswa_id', $beasiswaId)->findOrFail($assignmentId);
        app(BeasiswaService::class)->revoke($assignment);
        return back()->with('success', 'Penerima beasiswa dicabut.');
    }

    public function searchMahasiswa(Request $request, $id)
    {
        $beasiswa = Beasiswa::findOrFail($id);
        $assignedIds = BeasiswaMahasiswa::where('beasiswa_id', $id)->pluck('mahasiswa_id')->toArray();

        $query = Mahasiswa::whereNotIn('id', $assignedIds);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('jurusan', 'like', "%{$search}%");
            });
        }
        if ($request->filled('jurusan')) {
            $query->where('jurusan', $request->jurusan);
        }
        if ($request->filled('angkatan')) {
            $query->where('angkatan', $request->angkatan);
        }

        $result = $query->orderBy('nim')->paginate(10)->withQueryString();

        // Attach semester_hitung for display
        $taAktif = \App\Models\TahunAkademik::aktif();
        $service = app(\App\Services\MahasiswaSemesterService::class);
        $result->getCollection()->transform(function ($mhs) use ($service, $taAktif) {
            $mhs->semester_hitung = $service->hitung($mhs, $taAktif);
            return $mhs;
        });

        return response()->json($result);
    }

    public function assignBulk(Request $request, $id)
    {
        $beasiswa = Beasiswa::findOrFail($id);
        $validated = $request->validate([
            'nims' => 'required|array|min:1',
            'nims.*' => 'required|string|exists:mahasiswas,nim',
        ]);

        $count = 0;
        $errors = [];
        foreach ($validated['nims'] as $nim) {
            $mahasiswa = Mahasiswa::where('nim', $nim)->first();
            if (!$mahasiswa) { $errors[] = "NIM $nim tidak ditemukan"; continue; }
            // Skip jika sudah terassign
            if (BeasiswaMahasiswa::where('beasiswa_id', $beasiswa->id)->where('mahasiswa_id', $mahasiswa->id)->exists()) {
                $errors[] = "NIM $nim sudah penerima";
                continue;
            }
            if ($beasiswa->kuota > 0 && $beasiswa->terpakai >= $beasiswa->kuota) {
                $errors[] = "Kuota penuh saat memproses $nim";
                break;
            }
            try {
                app(BeasiswaService::class)->assign($mahasiswa, $beasiswa, null, 'disetujui', auth()->id());
                $count++;
            } catch (\Throwable $e) {
                $errors[] = "NIM $nim: " . $e->getMessage();
            }
        }

        $msg = "$count mahasiswa berhasil ditambahkan.";
        if ($errors) $msg .= " " . count($errors) . " gagal: " . implode(', ', array_slice($errors, 0, 3));

        return back()->with($errors && $count === 0 ? 'error' : 'success', $msg);
    }

    public function export()
    {
        $headers = ['kode','nama_beasiswa','jenis','sumber_dana','tahun_akademik','semester','tipe_diskon','nilai_diskon','kuota','terpakai','status_aktif'];
        $rows = Beasiswa::with('tahunAkademik')->get()->map(fn($b) => [
            $b->kode, $b->nama_beasiswa, $b->jenis, $b->sumber_dana ?? '-', $b->tahunAkademik?->nama ?? '-', $b->semester ?? '-', $b->tipe_diskon, $b->nilai_diskon, $b->kuota, $b->terpakai, $b->status_aktif ? 'Aktif' : 'Nonaktif',
        ])->toArray();
        return ExcelHelper::download('data-beasiswa-' . date('Ymd-His') . '.xlsx', $headers, $rows);
    }

    public function exportPenerima($id)
    {
        $beasiswa = Beasiswa::with(['tahunAkademik','jenisBeasiswa'])->findOrFail($id);
        $data = BeasiswaMahasiswa::where('beasiswa_id', $id)->with(['mahasiswa','tagihan'])->get();

        $headers = ['No','NIM','Nama Lengkap','Jurusan','Angkatan','Tahun Akademik','Semester','Nominal Tagihan','Diskon Diterapkan','Status'];
        $rows = [];
        foreach ($data as $i => $a) {
            $rows[] = [
                $i+1,
                $a->mahasiswa?->nim ?? '-',
                $a->mahasiswa?->nama_lengkap ?? '-',
                $a->mahasiswa?->jurusan ?? '-',
                $a->mahasiswa?->angkatan ?? '-',
                $a->tagihan?->tahun_akademik ?? $beasiswa->tahunAkademik?->nama ?? '-',
                $a->tagihan?->semester ?? $beasiswa->semester ?? '-',
                $a->tagihan ? number_format($a->tagihan->nominal,0,',','.') : '-',
                number_format($a->diskon_diterapkan,0,',','.'),
                $a->status,
            ];
        }
        $safe = preg_replace('/[^A-Za-z0-9_-]/','-', $beasiswa->kode);
        return ExcelHelper::download("penerima-{$safe}-" . date('Ymd-His') . '.xlsx', $headers, $rows);
    }

    public function exportPenerimaPdf($id)
    {
        $beasiswa = Beasiswa::with(['tahunAkademik','jenisBeasiswa'])->findOrFail($id);
        $penerimas = BeasiswaMahasiswa::where('beasiswa_id', $id)->with(['mahasiswa','tagihan'])->orderBy('id')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.penerima-beasiswa', [
            'beasiswa' => $beasiswa,
            'penerimas' => $penerimas,
        ])->setPaper('A4','landscape');

        $safe = preg_replace('/[^A-Za-z0-9_-]/','-', $beasiswa->kode);
        return $pdf->stream("penerima-{$safe}-" . date('Ymd-His') . '.pdf');
    }
}
