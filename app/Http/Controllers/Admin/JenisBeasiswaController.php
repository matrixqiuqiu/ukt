<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisBeasiswa;
use App\Services\ExcelHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JenisBeasiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = JenisBeasiswa::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")->orWhere('kode', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status_aktif', $request->status === 'aktif');
        }

        $items = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/JenisBeasiswa/Index', [
            'items' => $items,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:20|unique:jenis_beasiswas,kode',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status_aktif' => 'boolean',
        ]);

        JenisBeasiswa::create($validated);
        return back()->with('success', 'Jenis beasiswa berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = JenisBeasiswa::findOrFail($id);
        $validated = $request->validate([
            'kode' => 'required|string|max:20|unique:jenis_beasiswas,kode,' . $item->id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status_aktif' => 'boolean',
        ]);
        $item->update($validated);
        return back()->with('success', 'Jenis beasiswa berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = JenisBeasiswa::findOrFail($id);
        if ($item->beasiswas()->exists()) {
            return back()->with('error', 'Jenis masih dipakai beasiswa, tidak bisa dihapus.');
        }
        $item->delete();
        return back()->with('success', 'Jenis beasiswa dihapus.');
    }

    public function toggle($id)
    {
        $item = JenisBeasiswa::findOrFail($id);
        $item->update(['status_aktif' => !$item->status_aktif]);
        return back()->with('success', 'Status diperbarui.');
    }

    public function export()
    {
        $headers = ['kode','nama','deskripsi','status_aktif'];
        $rows = JenisBeasiswa::orderBy('kode')->get()->map(fn($j)=>[$j->kode,$j->nama,$j->deskripsi ?? '-', $j->status_aktif?'Aktif':'Nonaktif'])->toArray();
        return ExcelHelper::download('jenis-beasiswa-'.date('Ymd-His').'.xlsx', $headers, $rows);
    }
}
