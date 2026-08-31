<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nim',
        'nama_lengkap',
        'email',
        'telepon',
        'jurusan',
        'program_studi_kode',
        'angkatan',
        'semester',
        'status_aktif',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tagihans()
    {
        return $this->hasMany(Tagihan::class);
    }
}
