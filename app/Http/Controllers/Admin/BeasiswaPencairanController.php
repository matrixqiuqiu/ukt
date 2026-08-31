<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beasiswa;
use App\Models\BeasiswaPencairan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BeasiswaPencairanController extends Controller
{
    public function index($beasiswaId)
    {
        $beasiswa = Beasiswa::with('tahunAkademik')->findOrFail($beasiswaId);
        $pencairans = BeasiswaPencairan::where('beasiswa_id', $beasiswaId)->orderBy('termin_ke')->get();
        return Inertia::render('Admin/Beasiswa/Pencairan', [
            'beasiswa' => $beasiswa,
            'pencairans' => $pencairans,
        ]);
    }

    public function store(Request $request, $beasiswaId)
    {
        $beasiswa = Beasiswa::findOrFail($beasiswaId);
        $validated = $request->validate([
            'termin_ke' => 'required|integer|min:1',
            'nominal_dijanjikan' => 'required|numeric|min:0',
            'tanggal_janji_cair' => 'nullable|date',
            'jatuh_tempo_external' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ]);
        $validated['beasiswa_id'] = $beasiswa->id;
        $validated['status'] = 'ditagih';
        $validated['created_by'] = auth()->id();
        BeasiswaPencairan::create($validated);
        return back()->with('success', 'Termin pencairan ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $pencairan = BeasiswaPencairan::findOrFail($id);
        $validated = $request->validate([
            'termin_ke' => 'required|integer|min:1',
            'nominal_dijanjikan' => 'required|numeric|min:0',
            'tanggal_janji_cair' => 'nullable|date',
            'jatuh_tempo_external' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:ditagih,cair_sebagian,cair_penuh,gagal',
        ]);
        $pencairan->update($validated);
        return back()->with('success', 'Termin diperbarui.');
    }

    public function konfirmasi(Request $request, $id)
    {
        $pencairan = BeasiswaPencairan::findOrFail($id);
        $request->validate([
            'nominal_cair' => 'required|numeric|min:0',
            'tanggal_cair' => 'nullable|date',
            'bukti_cair' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        $data = [
            'nominal_cair' => $request->nominal_cair,
            'tanggal_cair' => $request->tanggal_cair ?? now(),
            'status' => $request->nominal_cair >= $pencairan->nominal_dijanjikan ? 'cair_penuh' : 'cair_sebagian',
        ];
        if ($request->hasFile('bukti_cair')) {
            $data['bukti_cair'] = $request->file('bukti_cair')->store('bukti-pencairan', 'public');
        }
        $pencairan->update($data);
        return back()->with('success', 'Pencairan dikonfirmasi.');
    }

    public function destroy($id)
    {
        BeasiswaPencairan::findOrFail($id)->delete();
        return back()->with('success', 'Termin dihapus.');
    }
}
