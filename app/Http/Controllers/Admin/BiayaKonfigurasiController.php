<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\SemesterHelper;
use App\Models\BiayaKonfigurasi;
use App\Models\KomponenBiaya;
use App\Models\Jurusan;
use App\Models\Mahasiswa;
use App\Models\SemesterAktif;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BiayaKonfigurasiController extends Controller
{
    public function index(Request $request)
    {
        $query = BiayaKonfigurasi::with('komponenBiaya');

        if ($request->filled('angkatan')) {
            $query->where('angkatan', $request->angkatan);
        }
        if ($request->filled('jurusan')) {
            $query->where('jurusan', $request->jurusan);
        }

        $konfigurasis = $query->orderBy('angkatan', 'desc')
            ->orderBy('jurusan')
            ->orderBy('komponen_biaya_id')
            ->get();

        $angkatans = Mahasiswa::distinct()->pluck('angkatan')->sortDesc()->values()->toArray();
        $jurusans = Jurusan::where('status_aktif', true)->pluck('nama')->sort()->values()->toArray();
        $komponens = KomponenBiaya::where('status_aktif', true)->get();

        $summary = [];
        foreach ($konfigurasis as $k) {
            $key = $k->angkatan . '-' . $k->jurusan;
            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'angkatan' => $k->angkatan,
                    'jurusan' => $k->jurusan,
                    'total' => 0,
                    'komponen_count' => 0,
                ];
            }
            $summary[$key]['total'] += (float) $k->nominal;
            $summary[$key]['komponen_count']++;
        }

        return Inertia::render('Admin/Biaya/Index', [
            'konfigurasis' => $konfigurasis,
            'angkatans' => $angkatans,
            'jurusans' => $jurusans,
            'komponens' => $komponens,
            'summary' => array_values($summary),
            'filters' => $request->only('angkatan', 'jurusan'),
            'semesterAktif' => SemesterAktif::instance(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'komponen_biaya_id' => 'required|exists:komponen_biayas,id',
            'angkatan' => 'required|digits:4|integer|min:2000|max:' . (date('Y') + 1),
            'jurusan' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);

        $exists = BiayaKonfigurasi::where([
            'komponen_biaya_id' => $request->komponen_biaya_id,
            'angkatan' => $request->angkatan,
            'jurusan' => $request->jurusan,
        ])->exists();

        if ($exists) {
            return back()->with('error', 'Konfigurasi biaya untuk komponen, angkatan, dan jurusan ini sudah ada.');
        }

        BiayaKonfigurasi::create($request->only('komponen_biaya_id', 'angkatan', 'jurusan', 'nominal', 'status_aktif'));

        return back()->with('success', 'Konfigurasi biaya berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $konfigurasi = BiayaKonfigurasi::findOrFail($id);

        $request->validate([
            'komponen_biaya_id' => 'required|exists:komponen_biayas,id',
            'angkatan' => 'required|digits:4|integer|min:2000|max:' . (date('Y') + 1),
            'jurusan' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);

        $exists = BiayaKonfigurasi::where([
            'komponen_biaya_id' => $request->komponen_biaya_id,
            'angkatan' => $request->angkatan,
            'jurusan' => $request->jurusan,
        ])->where('id', '!=', $id)->exists();

        if ($exists) {
            return back()->with('error', 'Konfigurasi biaya untuk komponen, angkatan, dan jurusan ini sudah ada.');
        }

        $konfigurasi->update($request->only('komponen_biaya_id', 'angkatan', 'jurusan', 'nominal', 'status_aktif'));

        return back()->with('success', 'Konfigurasi biaya berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $konfigurasi = BiayaKonfigurasi::findOrFail($id);
        $konfigurasi->delete();

        return back()->with('success', 'Konfigurasi biaya berhasil dihapus.');
    }

    public function toggle($id)
    {
        $konfigurasi = BiayaKonfigurasi::findOrFail($id);
        $konfigurasi->status_aktif = !$konfigurasi->status_aktif;
        $konfigurasi->save();

        $msg = 'Status konfigurasi biaya berhasil diperbarui.';

        if ($konfigurasi->status_aktif) {
            $result = $this->autoGenerateTagihan($konfigurasi);
            if ($result['created'] > 0 || $result['skipped'] > 0) {
                $msg .= " ({$result['created']} tagihan dibuat, {$result['skipped']} dilewati)";
            }
        }

        return back()->with('success', $msg);
    }

    private function autoGenerateTagihan(BiayaKonfigurasi $konfigurasi): array
    {
        $semesterAktif = SemesterAktif::instance();

        // Get active TahunAkademik for semester formula
        $ta = TahunAkademik::where('is_aktif', true)->first();
        if (!$ta) {
            return ['created' => 0, 'skipped' => 0];
        }

        $totalBiaya = BiayaKonfigurasi::where('angkatan', $konfigurasi->angkatan)
            ->where('jurusan', $konfigurasi->jurusan)
            ->where('status_aktif', true)
            ->sum('nominal');

        $mahasiswas = Mahasiswa::where('angkatan', $konfigurasi->angkatan)
            ->where('jurusan', $konfigurasi->jurusan)
            ->where('status_aktif', true)
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($mahasiswas as $mhs) {
            // Calculate current semester from formula
            $semester = SemesterHelper::hitung($mhs->angkatan, $ta->nama, $ta->semester);

            $exists = Tagihan::where('mahasiswa_id', $mhs->id)
                ->where('semester', $semester)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Tagihan::create([
                'mahasiswa_id' => $mhs->id,
                'semester' => $semester,
                'tahun_akademik' => $semesterAktif->tahun_akademik,
                'nominal' => $totalBiaya,
                'status' => 'belum_dibayar',
                'jatuh_tempo' => $semesterAktif->jatuh_tempo,
                'keterangan' => 'Tagihan UKT ' . $semesterAktif->tahun_akademik . ' semester ' . $semester,
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
