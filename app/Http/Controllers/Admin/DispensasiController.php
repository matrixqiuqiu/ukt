<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispensasi;
use App\Models\DispensasiSetting;
use App\Models\Tagihan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DispensasiController extends Controller
{
    public function index(): Response
    {
        $dispensasis = Dispensasi::with(['mahasiswa', 'tagihan', 'diprosesOleh'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Dispensasi/Index', [
            'dispensasis' => $dispensasis,
            'template' => DispensasiSetting::instance(),
        ]);
    }

    public function uploadTemplate(Request $request): RedirectResponse
    {
        $request->validate([
            'template' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $setting = DispensasiSetting::instance();

        if ($setting->template_path) {
            Storage::disk('public')->delete($setting->template_path);
        }

        $file = $request->file('template');
        $path = $file->store('dispensasi-templates', 'public');

        $setting->update([
            'template_path' => $path,
            'template_filename' => $file->getClientOriginalName(),
            'template_mime' => $file->getMimeType(),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Template surat dispensasi berhasil diunggah.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $setting = DispensasiSetting::instance();

        if (!$setting->template_path || !Storage::disk('public')->exists($setting->template_path)) {
            abort(404, 'Template surat dispensasi belum tersedia.');
        }

        return Storage::disk('public')->download(
            $setting->template_path,
            $setting->template_filename ?: 'template-surat-dispensasi.' . Str::afterLast($setting->template_path, '.')
        );
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        $dispensasi = Dispensasi::with('tagihan')->findOrFail($id);

        if ($dispensasi->status !== 'pending') {
            return back()->with('error', 'Pengajuan dispensasi sudah diproses sebelumnya.');
        }

        $dispensasi->tagihan->update([
            'jatuh_tempo' => $dispensasi->tempo_baru->format('Y-m-d'),
            'status' => 'dispen',
        ]);

        $dispensasi->update([
            'status' => 'disetujui',
            'catatan_admin' => $request->input('catatan_admin'),
            'diproses_oleh' => $request->user()->id,
            'diproses_pada' => now(),
        ]);

        return back()->with('success', 'Dispensasi disetujui. Jatuh tempo tagihan diperbarui dan status menjadi Dispen.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'catatan_admin' => 'required|string|max:500',
        ]);

        $dispensasi = Dispensasi::findOrFail($id);

        if ($dispensasi->status !== 'pending') {
            return back()->with('error', 'Pengajuan dispensasi sudah diproses sebelumnya.');
        }

        $dispensasi->update([
            'status' => 'ditolak',
            'catatan_admin' => $request->input('catatan_admin'),
            'diproses_oleh' => $request->user()->id,
            'diproses_pada' => now(),
        ]);

        return back()->with('success', 'Pengajuan dispensasi ditolak.');
    }
}
