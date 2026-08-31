<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'kode',
        'kodeps',
        'fakultas',
        'fakultas_id',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class, 'jurusan', 'nama');
    }

    public function fakultasRel()
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id');
    }
}
