<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Services\ExcelHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $query = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->where('pembayarans.status', '!=', 'expired');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('tagihan.mahasiswa', function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status !== 'expired') {
                $query->where('pembayarans.status', $request->status);
            }
        }

        // Sorting
        $allowedSorts = ['tanggal','mahasiswa','nim','semester','jumlah_bayar','status'];
        $sort = $request->input('sort');
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        if ($sort && in_array($sort, $allowedSorts, true)) {
            if ($sort === 'tanggal') {
                $query->orderBy('pembayarans.created_at', $direction);
            } elseif (in_array($sort, ['mahasiswa','nim'], true)) {
                $col = $sort === 'nim' ? 'mahasiswas.nim' : 'mahasiswas.nama_lengkap';
                $query->join('tagihans','tagihans.id','=','pembayarans.tagihan_id')
                      ->join('mahasiswas','mahasiswas.id','=','tagihans.mahasiswa_id')
                      ->orderBy($col, $direction)->select('pembayarans.*');
            } elseif ($sort === 'semester') {
                $query->join('tagihans as t2','t2.id','=','pembayarans.tagihan_id')->orderBy('t2.semester', $direction)->select('pembayarans.*');
            } elseif ($sort === 'jumlah_bayar') {
                $query->orderBy('pembayarans.jumlah_bayar', $direction);
            } elseif ($sort === 'status') {
                $query->orderBy('pembayarans.status', $direction);
            }
        } else {
            $query->latest('pembayarans.created_at');
        }

        $pembayarans = $query->paginate(10)->withQueryString();

        // Inject beasiswa status per pembayaran (H2 Talangan)
        $tagihanIds = $pembayarans->getCollection()->pluck('tagihan_id')->filter()->unique();
        $beasiswaMap = \App\Models\BeasiswaMahasiswa::whereIn('tagihan_id', $tagihanIds)
            ->with(['beasiswa.jenisBeasiswa'])
            ->get()->keyBy('tagihan_id');
        $pembayarans->getCollection()->transform(function ($p) use ($beasiswaMap) {
            $bm = $beasiswaMap->get($p->tagihan_id);
            if ($bm) {
                $p->setAttribute('beasiswa', [
                    'kode' => $bm->beasiswa->kode,
                    'nama' => $bm->beasiswa->nama_beasiswa,
                    'jenis' => $bm->beasiswa->jenisBeasiswa->nama ?? $bm->beasiswa->jenis,
                    'diskon' => $bm->diskon_diterapkan,
                    'status' => $bm->status,
                    'tipe' => $bm->beasiswa->tipe_diskon,
                    'sumber' => $bm->beasiswa->sumber_dana,
                ]);
            } elseif ($p->tagihan && $p->jumlah_bayar == 0 && $p->status === 'dikonfirmasi' && str_contains($p->catatan_admin ?? '', 'Beasiswa')) {
                // Fallback untuk pembayaran 0 full gratis tanpa pivot tagihan_id (legacy)
                $mhsId = $p->tagihan->mahasiswa_id;
                $bm2 = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $mhsId)->with(['beasiswa.jenisBeasiswa'])->latest()->first();
                if ($bm2) {
                    $p->setAttribute('beasiswa', [
                        'kode' => $bm2->beasiswa->kode,
                        'nama' => $bm2->beasiswa->nama_beasiswa,
                        'jenis' => $bm2->beasiswa->jenisBeasiswa->nama ?? $bm2->beasiswa->jenis,
                        'diskon' => $bm2->diskon_diterapkan,
                        'status' => $bm2->status,
                        'tipe' => $bm2->beasiswa->tipe_diskon,
                        'sumber' => $bm2->beasiswa->sumber_dana,
                    ]);
                }
            }
            return $p;
        });

        return Inertia::render('Admin/Pembayaran/Index', [
            'pembayarans' => $pembayarans,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    public function show($id)
    {
        $pembayaran = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran', 'riwayatTransaksi'])->findOrFail($id);
        $beasiswa = \App\Models\BeasiswaMahasiswa::where('tagihan_id', $pembayaran->tagihan_id)->with(['beasiswa.jenisBeasiswa'])->first();
        if (!$beasiswa && $pembayaran->tagihan) {
            $beasiswa = \App\Models\BeasiswaMahasiswa::where('mahasiswa_id', $pembayaran->tagihan->mahasiswa_id)->with(['beasiswa.jenisBeasiswa'])->latest()->first();
        }
        return Inertia::render('Admin/Pembayaran/Show', [
            'pembayaran' => $pembayaran,
            'beasiswa' => $beasiswa ? [
                'kode' => $beasiswa->beasiswa->kode,
                'nama' => $beasiswa->beasiswa->nama_beasiswa,
                'jenis' => $beasiswa->beasiswa->jenisBeasiswa->nama ?? $beasiswa->beasiswa->jenis,
                'diskon' => $beasiswa->diskon_diterapkan,
                'status' => $beasiswa->status,
                'tipe' => $beasiswa->beasiswa->tipe_diskon,
                'sumber' => $beasiswa->beasiswa->sumber_dana,
            ] : null,
        ]);
    }

    public function verifikasi($id)
    {
        $pembayaran = Pembayaran::with('tagihan.mahasiswa')->findOrFail($id);
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $pembayaran->update([
                'status' => 'dikonfirmasi',
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            $pembayaran->tagihan->update(['status' => 'sudah_dibayar']);

            DB::commit();

            return redirect()->back()->with('success', 'Pembayaran berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memverifikasi: ' . $e->getMessage());
        }
    }

    public function tolak(Request $request, $id)
    {
        $pembayaran = Pembayaran::findOrFail($id);
        $user = auth()->user();

        $request->validate([
            'catatan_admin' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $pembayaran->update([
                'status' => 'ditolak',
                'catatan_admin' => $request->catatan_admin,
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Pembayaran berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menolak: ' . $e->getMessage());
        }
    }

    public function exportLunas(Request $request): StreamedResponse
    {
        $query = Pembayaran::with(['tagihan.mahasiswa', 'metodePembayaran'])
            ->where('status', 'dikonfirmasi')
            ->whereHas('tagihan');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('tagihan.mahasiswa', function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%");
            });
        }
        if ($request->filled('tahun_akademik')) {
            $query->whereHas('tagihan', fn ($q) => $q->where('tahun_akademik', $request->tahun_akademik));
        }
        if ($request->filled('angkatan')) {
            $query->whereHas('tagihan.mahasiswa', fn ($q) => $q->where('angkatan', $request->angkatan));
        }
        if ($request->filled('jurusan')) {
            $query->whereHas('tagihan.mahasiswa', fn ($q) => $q->where('jurusan', $request->jurusan));
        }

        $data = $query->latest('verified_at')->latest()->get();

        $headers = ['No','NIM','Nama Mahasiswa','Jurusan','Angkatan','Fakultas','Tahun Akademik','Semester','Nominal','Tanggal Bayar','Metode','VA Number','Status'];

        $rows = $data->map(function ($p, $i) {
            $mhs = $p->tagihan?->mahasiswa;
            $tagihan = $p->tagihan;
            // Resolve fakultas via jurusan relasi jika ada
            $fakultas = '-';
            if ($mhs) {
                $jurusan = \App\Models\Jurusan::where('nama', $mhs->jurusan)->first();
                $fakultas = $jurusan?->fakultasRel?->nama ?? $jurusan?->fakultas ?? '-';
            }
            return [
                $i + 1,
                $mhs?->nim ?? '-',
                $mhs?->nama_lengkap ?? '-',
                $mhs?->jurusan ?? '-',
                $mhs?->angkatan ?? '-',
                $fakultas,
                $tagihan?->tahun_akademik ?? '-',
                $tagihan?->semester ?? '-',
                (int) $p->jumlah_bayar,
                $p->verified_at ? $p->verified_at->format('d/m/Y H:i') : ($p->updated_at ? $p->updated_at->format('d/m/Y H:i') : '-'),
                $p->metodePembayaran?->nama_metode ?? '-',
                $p->va_number ?? '-',
                'Lunas',
            ];
        })->toArray();

        $filename = 'laporan-lunas-' . date('Ymd-His') . '.xlsx';

        return ExcelHelper::download($filename, $headers, $rows);
    }
}
