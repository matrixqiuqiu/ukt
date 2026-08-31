<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $items = TahunAkademik::latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'semester' => $item->semester,
                'is_aktif' => $item->is_aktif,
            ];
        });

        return Inertia::render('Admin/TahunAkademik/Index', [
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:20',
            'semester' => 'required|in:Ganjil,Genap',
        ]);

        TahunAkademik::create($validated);

        return back()->with('success', 'Tahun akademik berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = TahunAkademik::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:20',
            'semester' => 'required|in:Ganjil,Genap',
        ]);

        $item->update($validated);

        return back()->with('success', 'Tahun akademik berhasil diperbarui.');
    }

    public function destroy($id)
    {
        TahunAkademik::findOrFail($id)->delete();

        return back()->with('success', 'Tahun akademik berhasil dihapus.');
    }

    public function toggle($id)
    {
        $item = TahunAkademik::findOrFail($id);

        // Deactivate all others
        TahunAkademik::where('id', '!=', $id)->update(['is_aktif' => false]);

        // Toggle this one
        $item->update(['is_aktif' => true]);

        // Sync semester_aktifs with this tahun akademik
        $semester = SemesterAktif::instance();
        $semester->update([
            'tahun_akademik' => $item->nama,
        ]);

        // Auto-generate tagihan for all active mahasiswa
        $result = Tagihan::generateForAll($item->nama, $semester->jatuh_tempo->format('Y-m-d'));

        $msg = "Tahun akademik {$item->nama} {$item->semester} diaktifkan.";
        if ($result['created'] > 0 || $result['skipped'] > 0) {
            $msg .= " ({$result['created']} tagihan dibuat, {$result['skipped']} dilewati)";
        }

        return back()->with('success', $msg);
    }
}
