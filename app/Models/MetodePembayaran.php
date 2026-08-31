<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodePembayaran extends Model
{
    use HasFactory;

    protected $table = 'metode_pembayarans';

    protected $fillable = [
        'nama_metode',
        'kode',
        'logo',
        'no_rekening',
        'instruksi',
        'kategori',
        'status_aktif',
    ];

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }
}
