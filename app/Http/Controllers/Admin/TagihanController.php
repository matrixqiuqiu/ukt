<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Services\ExcelHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagihanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Tagihan::with('mahasiswa');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('mahasiswa', function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%");
            });
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // Sorting
        $allowedSorts = ['nim','nama_lengkap','semester','tahun_akademik','nominal','status'];
        $sort = $request->input('sort');
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        if ($sort && in_array($sort, $allowedSorts, true)) {
            if (in_array($sort, ['nim','nama_lengkap'], true)) {
                $col = $sort === 'nim' ? 'mahasiswas.nim' : 'mahasiswas.nama_lengkap';
                $query->join('mahasiswas','mahasiswas.id','=','tagihans.mahasiswa_id')->orderBy($col, $direction)->select('tagihans.*');
            } else {
                $query->orderBy($sort === 'nominal' ? 'nominal' : $sort, $direction);
            }
        } else {
            $query->latest();
        }

        $tagihans = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Tagihan/Index', [
            'tagihans' => $tagihans,
            'filters' => $request->only(['search', 'status', 'semester', 'sort', 'direction']),
        ]);
    }

    public function export(Request $request)
    {
        $q = Tagihan::with('mahasiswa');
        if ($request->filled('search')) {
            $s=$request->search; $q->whereHas('mahasiswa', fn($qq)=>$qq->where('nim','like',"%{$s}%")->orWhere('nama_lengkap','like',"%{$s}%"));
        }
        if ($request->filled('status')) $q->where('status',$request->status);
        if ($request->filled('semester')) $q->where('semester',$request->semester);
        $data=$q->latest()->get();
        $headers=['No','NIM','Nama','Jurusan','Angkatan','Tahun Akademik','Semester','Nominal','Status','Jatuh Tempo'];
        $rows=$data->map(fn($t,$i)=>[$i+1, ($t->mahasiswa?->nim ?? '-'), ($t->mahasiswa?->nama_lengkap ?? '-'), ($t->mahasiswa?->jurusan ?? '-'), ($t->mahasiswa?->angkatan ?? '-'), $t->tahun_akademik, $t->semester, (int)$t->nominal, $t->status, ($t->jatuh_tempo?->format('d/m/Y') ?? '-')])->toArray();
        return ExcelHelper::download('tagihan-'.date('Ymd-His').'.xlsx',$headers,$rows);
    }

    public function exportPdf(Request $request)
    {
        $q = Tagihan::with('mahasiswa');
        if ($request->filled('search')) { $s=$request->search; $q->whereHas('mahasiswa', fn($qq)=>$qq->where('nim','like',"%{$s}%")->orWhere('nama_lengkap','like',"%{$s}%")); }
        if ($request->filled('status')) $q->where('status',$request->status);
        if ($request->filled('semester')) $q->where('semester',$request->semester);
        $data=$q->latest()->get();
        $pdf=\Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.tagihan', ['data'=>$data])->setPaper('A4','landscape');
        return $pdf->stream('tagihan-'.date('Ymd-His').'.pdf');
    }

    public function show($id): Response
    {
        $tagihan = Tagihan::with(['mahasiswa', 'pembayarans.metodePembayaran'])->findOrFail($id);

        return Inertia::render('Admin/Tagihan/Show', [
            'tagihan' => $tagihan,
            'pembayarans' => $tagihan->pembayarans,
        ]);
    }
}
