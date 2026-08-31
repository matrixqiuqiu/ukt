<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SemesterAktif;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SemesterAktifController extends Controller
{
    public function index()
    {
        $semester = SemesterAktif::instance();
        $tahunAkademiks = TahunAkademik::orderBy('nama', 'desc')->get();

        return Inertia::render('Admin/Semester/Index', [
            'semester' => $semester,
            'tahunAkademiks' => $tahunAkademiks,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'tahun_akademik_id' => 'required|exists:tahun_akademiks,id',
            'jatuh_tempo' => 'required|date',
        ]);

        $ta = TahunAkademik::findOrFail($request->tahun_akademik_id);

        // Sync: turn off all others, turn on selected
        TahunAkademik::where('is_aktif', true)->update(['is_aktif' => false]);
        $ta->update(['is_aktif' => true]);

        // Update semester_aktifs
        $semester = SemesterAktif::instance();
        $semester->update([
            'tahun_akademik' => $ta->nama,
            'jatuh_tempo' => $request->jatuh_tempo,
        ]);

        // Auto-generate tagihan for all active mahasiswa
        $result = Tagihan::generateForAll($ta->nama, $request->jatuh_tempo);

        $msg = 'Pengaturan semester aktif berhasil disimpan.';
        if ($result['created'] > 0 || $result['skipped'] > 0) {
            $msg .= " ({$result['created']} tagihan dibuat, {$result['skipped']} dilewati)";
        }

        return back()->with('success', $msg);
    }
}
