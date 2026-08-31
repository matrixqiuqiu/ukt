<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAkademik extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'semester',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public static function aktif(): ?static
    {
        return static::where('is_aktif', true)->first();
    }
}
