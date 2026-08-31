<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ThemeSettingController extends Controller
{
    public function index()
    {
        $theme = ThemeSetting::instance();

        return Inertia::render('Admin/Pengaturan/Index', [
            'theme' => $theme,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'sidebar_bg' => 'required|string|max:7',
            'sidebar_text' => 'required|string|max:7',
            'sidebar_icon' => 'required|string|max:7',
            'sidebar_active_text' => 'required|string|max:7',
            'sidebar_active_bg' => 'required|string|max:7',
            'sidebar_hover_bg' => 'required|string|max:7',
            'navbar_bg' => 'required|string|max:7',
            'navbar_text' => 'required|string|max:7',
            'navbar_border' => 'required|string|max:7',
            'primary_color' => 'required|string|max:7',
            'logo_text' => 'required|string|max:7',
            'content_bg' => 'nullable|string|max:7',
            'content_text' => 'nullable|string|max:7',
            'card_bg' => 'nullable|string|max:7',
            'card_border' => 'nullable|string|max:7',
            'invoice_institution_name' => 'nullable|string|max:255',
            'invoice_institution_address' => 'nullable|string|max:500',
            'invoice_institution_phone' => 'nullable|string|max:50',
            'invoice_institution_email' => 'nullable|email|max:100',
            'invoice_institution_website' => 'nullable|url|max:255',
        ]);

        $theme = ThemeSetting::instance();
        $theme->update($request->only([
            'sidebar_bg', 'sidebar_text', 'sidebar_icon', 'sidebar_active_text',
            'sidebar_active_bg', 'sidebar_hover_bg',
            'navbar_bg', 'navbar_text', 'navbar_border',
            'primary_color', 'logo_text',
            'content_bg', 'content_text', 'card_bg', 'card_border',
            'invoice_institution_name',
            'invoice_institution_address',
            'invoice_institution_phone',
            'invoice_institution_email',
            'invoice_institution_website',
        ]));

        return back()->with('success', 'Pengaturan tema berhasil disimpan.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $path = $this->storeResizedLogo($request->file('logo'));
        $url = Storage::url($path);

        $theme = ThemeSetting::instance();
        $theme->update(['invoice_logo' => $url]);

        return response()->json(['url' => $url, 'success' => true]);
    }

    /**
     * Store the logo downscaled to a web-friendly size.
     * Oversized logos (e.g. 1MB+ PNG) are embedded as base64 in the PDF invoice,
     * which makes dompdf extremely slow, so they are resized here.
     */
    private function storeResizedLogo(UploadedFile $file): string
    {
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($image === false) {
            return $file->store('invoice-logos', 'public');
        }

        $maxDim = 256;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxDim || $height > $maxDim) {
            $ratio = min($maxDim / $width, $maxDim / $height);
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $dir = storage_path('app/public/invoice-logos');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = 'logo-' . Str::random(20) . '.png';
        imagepng($image, $dir . DIRECTORY_SEPARATOR . $name, 9);
        imagedestroy($image);

        return 'invoice-logos/' . $name;
    }

    public function uploadInvoiceHeader(Request $request)
    {
        $request->validate([
            'header_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $path = $request->file('header_image')->store('invoice-headers', 'public');
        $url = Storage::url($path);

        $theme = ThemeSetting::instance();
        $theme->update(['invoice_header_image' => $url]);

        return response()->json(['url' => $url, 'success' => true]);
    }

    public function reset()
    {
        $theme = ThemeSetting::instance();
        $theme->update([
            'sidebar_bg' => '#1e293b',
            'sidebar_text' => '#94a3b8',
            'sidebar_icon' => '#94a3b8',
            'sidebar_active_text' => '#ffffff',
            'sidebar_active_bg' => '#4f46e5',
            'sidebar_hover_bg' => '#334155',
            'navbar_bg' => '#ffffff',
            'navbar_text' => '#1e293b',
            'navbar_border' => '#e2e8f0',
            'primary_color' => '#4f46e5',
            'logo_text' => '#ffffff',
            'content_bg' => '#f8fafc',
            'content_text' => '#1e293b',
            'card_bg' => '#ffffff',
            'card_border' => '#e2e8f0',
        ]);

        return back()->with('success', 'Tema berhasil direset ke default.');
    }
}
