<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\SemesterAktif;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TahunAkademikController extends Controller
{
    public function index(): Response
    {
        $activeTa = TahunAkademik::where('is_aktif', true)->first();
        $semesterAktif = SemesterAktif::instance();

        // Metrik Semester Berjalan
        $metrics = [
            'total_mahasiswa_aktif' => Mahasiswa::where('status_aktif', true)->count(),
            'total_tagihan' => 0,
            'total_nominal' => 0,
            'lunas_count' => 0,
            'lunas_nominal' => 0,
            'belum_lunas_count' => 0,
            'belum_lunas_nominal' => 0,
        ];

        $countdown = [
            'diff_days' => null,
            'is_expired' => false,
            'formatted_date' => $semesterAktif->jatuh_tempo ? $semesterAktif->jatuh_tempo->translatedFormat('d F Y') : '-',
        ];

        if ($activeTa) {
            $tagihanQuery = Tagihan::where('tahun_akademik', $activeTa->nama);

            $metrics['total_tagihan'] = (clone $tagihanQuery)->count();
            $metrics['total_nominal'] = (float) (clone $tagihanQuery)->sum('nominal');
            $metrics['lunas_count'] = (clone $tagihanQuery)->where('status', 'sudah_dibayar')->count();
            $metrics['lunas_nominal'] = (float) (clone $tagihanQuery)->where('status', 'sudah_dibayar')->sum('nominal');
            $metrics['belum_lunas_count'] = (clone $tagihanQuery)->where('status', 'belum_dibayar')->count();
            $metrics['belum_lunas_nominal'] = (float) (clone $tagihanQuery)->where('status', 'belum_dibayar')->sum('nominal');

            if ($semesterAktif->jatuh_tempo) {
                $today = now()->startOfDay();
                $target = $semesterAktif->jatuh_tempo->copy()->startOfDay();
                $diff = (int) $today->diffInDays($target, false);
                $countdown['diff_days'] = $diff;
                $countdown['is_expired'] = $diff < 0;
            }
        }

        // Daftar Master Tahun Akademik
        $items = TahunAkademik::orderBy('nama', 'desc')->orderBy('semester', 'asc')->get()->map(function ($item) {
            $tagihanCount = Tagihan::where('tahun_akademik', $item->nama)->count();
            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'semester' => $item->semester,
                'is_aktif' => (bool) $item->is_aktif,
                'tagihan_count' => $tagihanCount,
                'created_at' => $item->created_at ? $item->created_at->format('d/m/Y') : '-',
            ];
        });

        return Inertia::render('Admin/TahunAkademik/Index', [
            'items' => $items,
            'activeTa' => $activeTa,
            'semesterAktif' => [
                'tahun_akademik' => $semesterAktif->tahun_akademik,
                'jatuh_tempo' => $semesterAktif->jatuh_tempo ? $semesterAktif->jatuh_tempo->format('Y-m-d') : '',
            ],
            'metrics' => $metrics,
            'countdown' => $countdown,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);

        $exists = TahunAkademik::where('nama', $validated['nama'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($exists) {
            return back()->with('error', "Tahun Akademik {$validated['nama']} ({$validated['semester']}) sudah ada di database.");
        }

        TahunAkademik::create([
            'nama' => $validated['nama'],
            'semester' => $validated['semester'],
            'is_aktif' => false,
        ]);

        return back()->with('success', 'Tahun akademik baru berhasil ditambahkan ke master data.');
    }

    public function update(Request $request, $id)
    {
        $item = TahunAkademik::findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);

        $exists = TahunAkademik::where('nama', $validated['nama'])
            ->where('semester', $validated['semester'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->with('error', "Tahun Akademik {$validated['nama']} ({$validated['semester']}) sudah ada.");
        }

        $oldName = $item->nama;
        $item->update($validated);

        // Jika yang diedit adalah tahun akademik aktif, update juga semester_aktifs
        if ($item->is_aktif) {
            $semester = SemesterAktif::instance();
            $semester->update(['tahun_akademik' => $item->nama]);
        }

        return back()->with('success', 'Tahun akademik berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = TahunAkademik::findOrFail($id);

        if ($item->is_aktif) {
            return back()->with('error', 'Tahun akademik yang sedang berstatus AKTIF tidak dapat dihapus.');
        }

        $tagihanCount = Tagihan::where('tahun_akademik', $item->nama)->count();
        if ($tagihanCount > 0) {
            return back()->with('error', "Tidak dapat menghapus {$item->nama} ({$item->semester}) karena memiliki {$tagihanCount} data tagihan mahasiswa terkait.");
        }

        $item->delete();

        return back()->with('success', 'Tahun akademik berhasil dihapus dari master data.');
    }

    public function activate(Request $request, $id)
    {
        $item = TahunAkademik::findOrFail($id);

        $validated = $request->validate([
            'jatuh_tempo' => ['required', 'date'],
            'generate_tagihan' => ['nullable', 'boolean'],
        ]);

        // Nonaktifkan semua tahun akademik lain
        TahunAkademik::where('id', '!=', $id)->update(['is_aktif' => false]);
        $item->update(['is_aktif' => true]);

        // Perbarui singleton SemesterAktif
        $semester = SemesterAktif::instance();
        $semester->update([
            'tahun_akademik' => $item->nama,
            'jatuh_tempo' => $validated['jatuh_tempo'],
        ]);

        // Generate tagihan jika dicentang
        $msg = "Tahun Akademik {$item->nama} ({$item->semester}) berhasil diaktifkan.";
        if (!empty($validated['generate_tagihan'])) {
            $result = Tagihan::generateForAll($item->nama, $validated['jatuh_tempo']);
            $msg .= " ({$result['created']} tagihan mahasiswa berhasil dibuat, {$result['skipped']} data dilewati).";
        }

        return back()->with('success', $msg);
    }

    public function updateJatuhTempo(Request $request)
    {
        $validated = $request->validate([
            'jatuh_tempo' => ['required', 'date'],
            'update_tagihan_unpaid' => ['nullable', 'boolean'],
        ]);

        $semester = SemesterAktif::instance();
        $semester->update(['jatuh_tempo' => $validated['jatuh_tempo']]);

        // Update jatuh tempo pada tagihan belum lunas di semester aktif jika diminta
        if (!empty($validated['update_tagihan_unpaid'])) {
            Tagihan::where('tahun_akademik', $semester->tahun_akademik)
                ->where('status', 'belum_dibayar')
                ->update(['jatuh_tempo' => $validated['jatuh_tempo']]);
        }

        return back()->with('success', 'Tanggal jatuh tempo semester aktif berhasil diperbarui.');
    }

    public function generateTagihan(Request $request)
    {
        $activeTa = TahunAkademik::where('is_aktif', true)->first();
        if (!$activeTa) {
            return back()->with('error', 'Belum ada tahun akademik yang aktif.');
        }

        $semester = SemesterAktif::instance();
        $result = Tagihan::generateForAll($activeTa->nama, $semester->jatuh_tempo ? $semester->jatuh_tempo->format('Y-m-d') : null);

        return back()->with('success', "Generate tagihan selesai: {$result['created']} tagihan baru diterbitkan, {$result['skipped']} data mahasiswa dilewati (sudah ada tagihan).");
    }

    public function toggle($id)
    {
        // Backward-compatible toggle method
        $item = TahunAkademik::findOrFail($id);
        $semester = SemesterAktif::instance();

        TahunAkademik::where('id', '!=', $id)->update(['is_aktif' => false]);
        $item->update(['is_aktif' => true]);

        $semester->update(['tahun_akademik' => $item->nama]);

        return back()->with('success', "Tahun akademik {$item->nama} ({$item->semester}) telah diaktifkan.");
    }
}
