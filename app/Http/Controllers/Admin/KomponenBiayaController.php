<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KomponenBiaya;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KomponenBiayaController extends Controller
{
    public function index()
    {
        $komponens = KomponenBiaya::withCount('konfigurasis')->latest()->get();

        return Inertia::render('Admin/KomponenBiaya/Index', [
            'komponens' => $komponens,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:20|unique:komponen_biayas,kode',
            'deskripsi' => 'nullable|string|max:500',
            'status_aktif' => 'boolean',
        ]);

        KomponenBiaya::create($request->only('nama', 'kode', 'deskripsi', 'status_aktif'));

        return back()->with('success', 'Komponen biaya "' . $request->nama . '" berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $komponen = KomponenBiaya::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:20|unique:komponen_biayas,kode,' . $komponen->id,
            'deskripsi' => 'nullable|string|max:500',
            'status_aktif' => 'boolean',
        ]);

        $komponen->update($request->only('nama', 'kode', 'deskripsi', 'status_aktif'));

        return back()->with('success', 'Komponen biaya "' . $komponen->nama . '" berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $komponen = KomponenBiaya::findOrFail($id);

        if ($komponen->konfigurasis()->count() > 0) {
            return back()->with('error', 'Komponen biaya "' . $komponen->nama . '" tidak bisa dihapus karena masih memiliki data konfigurasi biaya.');
        }

        $komponen->delete();

        return back()->with('success', 'Komponen biaya berhasil dihapus.');
    }

    public function toggle($id)
    {
        $komponen = KomponenBiaya::findOrFail($id);
        $komponen->status_aktif = !$komponen->status_aktif;
        $komponen->save();

        return back()->with('success', 'Status "' . $komponen->nama . '" berhasil diperbarui.');
    }
}
