<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Services\ExcelHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JurusanController extends Controller
{
    public function index()
    {
        $query = Jurusan::with('fakultasRel')->withCount('mahasiswas');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%")
                  ->orWhere('kodeps', 'like', "%{$search}%");
            });
        }

        $jurusans = $query->orderBy('kode')->paginate(10)->withQueryString();

        $fakultas = Fakultas::where('status_aktif', true)->get();

        return Inertia::render('Admin/Jurusan/Index', [
            'jurusans' => $jurusans,
            'fakultas' => $fakultas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:20|unique:jurusans,kode',
            'kodeps' => 'nullable|string|max:10',
            'fakultas_id' => 'nullable|exists:fakultas,id',
            'status_aktif' => 'boolean',
        ]);

        $fakultas = Fakultas::find($request->fakultas_id);

        Jurusan::create([
            'nama' => $request->nama,
            'kode' => $request->kode,
            'kodeps' => $request->kodeps,
            'fakultas_id' => $request->fakultas_id,
            'fakultas' => $fakultas?->nama,
            'status_aktif' => $request->boolean('status_aktif'),
        ]);

        return back()->with('success', 'Program studi "' . $request->nama . '" berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $jurusan = Jurusan::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:20|unique:jurusans,kode,' . $jurusan->id,
            'kodeps' => 'nullable|string|max:10',
            'fakultas_id' => 'nullable|exists:fakultas,id',
            'status_aktif' => 'boolean',
        ]);

        $fakultas = Fakultas::find($request->fakultas_id);

        $jurusan->update([
            'nama' => $request->nama,
            'kode' => $request->kode,
            'kodeps' => $request->kodeps,
            'fakultas_id' => $request->fakultas_id,
            'fakultas' => $fakultas?->nama,
            'status_aktif' => $request->boolean('status_aktif'),
        ]);

        return back()->with('success', 'Program studi "' . $jurusan->nama . '" berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $jurusan = Jurusan::findOrFail($id);

        if ($jurusan->mahasiswas()->count() > 0) {
            return back()->with('error', 'Program studi "' . $jurusan->nama . '" tidak bisa dihapus karena masih memiliki data mahasiswa.');
        }

        $jurusan->delete();

        return back()->with('success', 'Program studi berhasil dihapus.');
    }

    public function toggle($id)
    {
        $jurusan = Jurusan::findOrFail($id);
        $jurusan->status_aktif = !$jurusan->status_aktif;
        $jurusan->save();

        return back()->with('success', 'Status "' . $jurusan->nama . '" berhasil diperbarui.');
    }

    public function export(): StreamedResponse
    {
        $headers = ['kode', 'kodeps', 'nama', 'fakultas', 'status_aktif'];

        $rows = Jurusan::orderBy('kode')->get()->map(function (Jurusan $j) {
            return [
                $j->kode,
                $j->kodeps ?? '',
                $j->nama,
                $j->fakultas ?? $j->fakultasRel?->nama ?? '',
                $j->status_aktif ? 'Aktif' : 'Nonaktif',
            ];
        })->toArray();

        return ExcelHelper::download('data-jurusan-' . date('Ymd-His') . '.xlsx', $headers, $rows);
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

            $fakultasByNama = Fakultas::all()->keyBy(fn ($f) => strtolower(trim($f->nama)));
            $fakultasByKode = Fakultas::all()->keyBy(fn ($f) => strtolower(trim($f->kode)));

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

                    // Resolve fakultas dari nama / kode / fakultas_id
                    $fakultasId = null;
                    $fakultasNama = trim((string) ($row['fakultas'] ?? ''));
                    if ($fakultasNama !== '') {
                        $fakultas = $fakultasByNama->get(strtolower($fakultasNama))
                            ?? $fakultasByKode->get(strtolower($fakultasNama));
                        if ($fakultas) {
                            $fakultasId = $fakultas->id;
                        }
                    } elseif (!empty($row['fakultas_id'])) {
                        $fakultasId = (int) $row['fakultas_id'];
                    }

                    $status = ExcelHelper::parseBoolean($row['status_aktif'] ?? 'Aktif');

                    $jurusan = Jurusan::where('kode', $kode)->first();

                    if ($jurusan) {
                        $jurusan->update([
                            'nama' => $nama,
                            'kodeps' => $row['kodeps'] ?? null,
                            'fakultas_id' => $fakultasId,
                            'fakultas' => $fakultasByNama->get(strtolower((string) $fakultasNama))?->nama
                                ?? $fakultasByKode->get(strtolower((string) $fakultasNama))?->nama
                                ?? $jurusan->fakultas,
                            'status_aktif' => $status,
                        ]);
                        $updated++;
                    } else {
                        $jurusan = Jurusan::create([
                            'nama' => $nama,
                            'kode' => $kode,
                            'kodeps' => $row['kodeps'] ?? null,
                            'fakultas_id' => $fakultasId,
                            'fakultas' => $fakultasByNama->get(strtolower((string) $fakultasNama))?->nama
                                ?? $fakultasByKode->get(strtolower((string) $fakultasNama))?->nama
                                ?? $fakultasNama ?: null,
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
