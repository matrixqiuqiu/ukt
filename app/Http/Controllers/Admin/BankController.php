<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BankController extends Controller
{
    public function index()
    {
        $banks = MetodePembayaran::withCount('pembayarans')->latest()->get();

        return Inertia::render('Admin/Bank/Index', [
            'banks' => $banks,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_metode' => 'required|string|max:255',
            'kode' => 'required|string|max:10|unique:metode_pembayarans,kode',
            'logo' => 'nullable',
            'no_rekening' => 'nullable|string|max:50',
            'instruksi' => 'nullable|string',
            'kategori' => 'required|in:rekening_universitas,virtual_account',
            'status_aktif' => 'boolean',
        ]);

        MetodePembayaran::create($request->only('nama_metode', 'kode', 'no_rekening', 'instruksi', 'kategori', 'status_aktif') + [
            'logo' => $this->resolveLogo($request),
        ]);

        return back()->with('success', 'Bank "' . $request->nama_metode . '" berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $bank = MetodePembayaran::findOrFail($id);

        $request->validate([
            'nama_metode' => 'required|string|max:255',
            'kode' => 'required|string|max:10|unique:metode_pembayarans,kode,' . $bank->id,
            'logo' => 'nullable',
            'no_rekening' => 'nullable|string|max:50',
            'instruksi' => 'nullable|string',
            'kategori' => 'required|in:rekening_universitas,virtual_account',
            'status_aktif' => 'boolean',
        ]);

        $bank->update($request->only('nama_metode', 'kode', 'no_rekening', 'instruksi', 'kategori', 'status_aktif') + [
            'logo' => $this->resolveLogo($request),
        ]);

        return back()->with('success', 'Bank "' . $bank->nama_metode . '" berhasil diperbarui.');
    }

    /**
     * Accept logo either as an uploaded image file or an existing URL string.
     * Uploaded files are stored under /storage/bank-logos and returned as a public URL.
     */
    private function resolveLogo(Request $request): ?string
    {
        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048']);

            return Storage::url($request->file('logo')->store('bank-logos', 'public'));
        }

        return $request->input('logo');
    }

    public function destroy($id)
    {
        $bank = MetodePembayaran::findOrFail($id);

        if ($bank->pembayarans()->count() > 0) {
            return back()->with('error', 'Bank "' . $bank->nama_metode . '" tidak bisa dihapus karena masih memiliki data transaksi.');
        }

        $bank->delete();

        return back()->with('success', 'Bank berhasil dihapus.');
    }

    public function uploadLogo(Request $request, $id)
    {
        $bank = MetodePembayaran::findOrFail($id);

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:1024',
        ]);

        $path = $request->file('logo')->store('bank-logos', 'public');
        $url = Storage::url($path);

        $bank->update(['logo' => $url]);

        return response()->json(['url' => $url, 'success' => true]);
    }

    public function toggle($id)
    {
        $bank = MetodePembayaran::findOrFail($id);
        $bank->status_aktif = !$bank->status_aktif;
        $bank->save();

        return back()->with('success', 'Status "' . $bank->nama_metode . '" berhasil diperbarui.');
    }
}
