<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemesterAktif extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_akademik',
        'jatuh_tempo',
    ];

    protected function casts(): array
    {
        return [
            'jatuh_tempo' => 'date',
        ];
    }

    public static function instance(): static
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create([
                'tahun_akademik' => date('Y') . '/' . (date('Y') + 1),
                'jatuh_tempo' => now()->addDays(30),
            ]);
        }
        return $setting;
    }
}
