<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Services\ExcelHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FakultasController extends Controller
{
    public function index()
    {
        $fakultas = Fakultas::withCount('jurusans')->latest()->get();

        return Inertia::render('Admin/Fakultas/Index', [
            'fakultas' => $fakultas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:20|unique:fakultas,kode',
            'kodef' => 'nullable|string|max:10',
            'nama' => 'required|string|max:255',
            'status_aktif' => 'boolean',
        ]);

        Fakultas::create($request->only('kode', 'kodef', 'nama', 'status_aktif'));

        return back()->with('success', 'Fakultas "' . $request->nama . '" berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $fakultas = Fakultas::findOrFail($id);

        $request->validate([
            'kode' => 'required|string|max:20|unique:fakultas,kode,' . $fakultas->id,
            'kodef' => 'nullable|string|max:10',
            'nama' => 'required|string|max:255',
            'status_aktif' => 'boolean',
        ]);

        $fakultas->update($request->only('kode', 'kodef', 'nama', 'status_aktif'));

        return back()->with('success', 'Fakultas "' . $fakultas->nama . '" berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $fakultas = Fakultas::findOrFail($id);

        if ($fakultas->jurusans()->count() > 0) {
            return back()->with('error', 'Fakultas "' . $fakultas->nama . '" tidak bisa dihapus karena masih memiliki data jurusan.');
        }

        $fakultas->delete();

        return back()->with('success', 'Fakultas berhasil dihapus.');
    }

    public function toggle($id)
    {
        $fakultas = Fakultas::findOrFail($id);
        $fakultas->status_aktif = !$fakultas->status_aktif;
        $fakultas->save();

        return back()->with('success', 'Status "' . $fakultas->nama . '" berhasil diperbarui.');
    }

    public function export(): StreamedResponse
    {
        $headers = ['kode', 'kodef', 'nama', 'status_aktif'];

        $rows = Fakultas::orderBy('kode')->get()->map(function (Fakultas $f) {
            return [
                $f->kode,
                $f->kodef ?? '',
                $f->nama,
                $f->status_aktif ? 'Aktif' : 'Nonaktif',
            ];
        })->toArray();

        return ExcelHelper::download('data-fakultas-' . date('Ymd-His') . '.xlsx', $headers, $rows);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:4096'],
        ]);

        try {
            $rows = ExcelHelper::parse($request->file('file'));

            if (empty($rows)) {
                return back()->with('error', 'File Excel kosong atau format tidak sesuai.');
            }

            $created = 0;
            $updated = 0;
            $errors = [];

            foreach ($rows as $row) {
                try {
                    $kode = trim((string) ($row['kode'] ?? ''));

                    if ($kode === '') {
                        $errors[] = 'Baris tanpa kode dilewati.';
                        continue;
                    }

                    $nama = trim((string) ($row['nama'] ?? ''));
                    if ($nama === '') {
                        $errors[] = 'Kode ' . $kode . ': nama kosong, dilewati.';
                        continue;
                    }

                    $status = ExcelHelper::parseBoolean($row['status_aktif'] ?? 'Aktif');

                    $fakultas = Fakultas::where('kode', $kode)->first();

                    if ($fakultas) {
                        $fakultas->update([
                            'kodef' => $row['kodef'] ?? null,
                            'nama' => $nama,
                            'status_aktif' => $status,
                        ]);
                        $updated++;
                    } else {
                        Fakultas::create([
                            'kode' => $kode,
                            'kodef' => $row['kodef'] ?? null,
                            'nama' => $nama,
                            'status_aktif' => $status,
                        ]);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Kode ' . ($row['kode'] ?? '?') . ': ' . $e->getMessage();
                }
            }

            $msg = "Import selesai: {$created} baru, {$updated} diperbarui.";
            if ($errors) {
                $msg .= ' (' . count($errors) . ' baris bermasalah)';
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }
}
