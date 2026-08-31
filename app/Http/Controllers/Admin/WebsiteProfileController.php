<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteProfileController extends Controller
{
    public function index(): Response
    {
        $theme = ThemeSetting::instance();

        return Inertia::render('Admin/ProfilWebsite/Index', [
            'profile' => [
                'website_name' => $theme->website_name,
                'website_short_name' => $theme->website_short_name,
                'website_tagline' => $theme->website_tagline,
                'website_footer_text' => $theme->website_footer_text,
                'invoice_institution_name' => $theme->invoice_institution_name,
                'invoice_institution_address' => $theme->invoice_institution_address,
                'invoice_institution_phone' => $theme->invoice_institution_phone,
                'invoice_institution_email' => $theme->invoice_institution_email,
                'invoice_institution_website' => $theme->invoice_institution_website,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'website_name' => ['nullable', 'string', 'max:255'],
            'website_short_name' => ['nullable', 'string', 'max:100'],
            'website_tagline' => ['nullable', 'string', 'max:255'],
            'website_footer_text' => ['nullable', 'string', 'max:255'],
            'invoice_institution_name' => ['nullable', 'string', 'max:255'],
            'invoice_institution_address' => ['nullable', 'string', 'max:500'],
            'invoice_institution_phone' => ['nullable', 'string', 'max:50'],
            'invoice_institution_email' => ['nullable', 'email', 'max:100'],
            'invoice_institution_website' => ['nullable', 'string', 'max:255'],
        ]);

        // Normalisasi website: auto prepend https:// jika tanpa scheme agar tidak gagal validasi url sebelumnya
        if (!empty($validated['invoice_institution_website']) && !preg_match('#^https?://#i', $validated['invoice_institution_website'])) {
            $validated['invoice_institution_website'] = 'https://' . ltrim($validated['invoice_institution_website'], '/');
        }

        ThemeSetting::instance()->update($validated);

        return back()->with('success', 'Profil website berhasil disimpan.');
    }
}
