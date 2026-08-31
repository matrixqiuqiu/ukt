<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
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

    public function show($id): Response
    {
        $tagihan = Tagihan::with(['mahasiswa', 'pembayarans.metodePembayaran'])->findOrFail($id);

        return Inertia::render('Admin/Tagihan/Show', [
            'tagihan' => $tagihan,
            'pembayarans' => $tagihan->pembayarans,
        ]);
    }
}
