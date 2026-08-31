<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'tagihan_id',
        'metode_pembayaran_id',
        'jumlah_bayar',
        'bukti_pembayaran',
        'nama_pengirim',
        'va_number',
        'va_expired_at',
        'status',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_bayar' => 'decimal:2',
            'va_expired_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Auto-update tagihan status when pembayaran status changes to 'dikonfirmasi'.
     * This ensures tagihan.status is always in sync with pembayaran.status.
     */
    protected static function booted()
    {
        static::updated(function ($pembayaran) {
            if ($pembayaran->status === 'dikonfirmasi' && $pembayaran->tagihan) {
                if ($pembayaran->tagihan->status !== 'sudah_dibayar') {
                    $pembayaran->tagihan->update(['status' => 'sudah_dibayar']);
                }
            }
        });
    }

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function metodePembayaran()
    {
        return $this->belongsTo(MetodePembayaran::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function riwayatTransaksi()
    {
        return $this->hasMany(RiwayatTransaksi::class);
    }
}
