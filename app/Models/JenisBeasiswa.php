<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisBeasiswa extends Model
{
    use HasFactory;

    protected $table = 'jenis_beasiswas';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function beasiswas(): HasMany
    {
        return $this->hasMany(Beasiswa::class, 'jenis_beasiswa_id');
    }
}
