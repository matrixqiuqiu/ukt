<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiayaKonfigurasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'komponen_biaya_id',
        'angkatan',
        'jurusan',
        'nominal',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'status_aktif' => 'boolean',
        ];
    }

    public function komponenBiaya()
    {
        return $this->belongsTo(KomponenBiaya::class);
    }
}
