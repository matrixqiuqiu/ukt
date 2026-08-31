<?php

namespace App\Http\Middleware;

use App\Models\Pembayaran;
use App\Models\SemesterAktif;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'mahasiswa' => fn () => $request->user() && $request->user()->role === 'mahasiswa'
                    ? \App\Models\Mahasiswa::where('nim', $request->user()->getNimAttribute())->first()
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->pull('success'),
                'error' => fn () => $request->session()->pull('error'),
                'sync_result' => fn () => $request->session()->pull('sync_result'),
            ],
            'impersonating' => fn () => $request->session()->has('impersonator_id'),
            'impersonator' => fn () => $request->session()->has('impersonator_id')
                ? \App\Models\User::find($request->session()->get('impersonator_id'))
                : null,
            'pendingVerification' => fn () => $request->user()?->role === 'admin'
                ? Pembayaran::where('status', 'pending')->count()
                : 0,
            'belumBayarCount' => fn () => $request->user()?->role === 'mahasiswa'
                ? ($m = $request->user()->getMahasiswaByNim())
                    ? $m->tagihans()->where('status', '!=', 'sudah_dibayar')->count()
                    : 0
                : 0,
            'appTimezone' => fn () => config('app.timezone'),
            'appTimezoneAbbr' => fn () => match(config('app.timezone')) {
                'Asia/Jakarta' => 'WIB',
                'Asia/Makassar' => 'WITA',
                'Asia/Jayapura' => 'WIT',
                default => config('app.timezone'),
            },
            'theme' => fn () => ThemeSetting::instance(),
            'semesterAktif' => fn () => SemesterAktif::instance(),
        ];
    }
}
