<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beasiswa extends Model
{
    use HasFactory;

    protected $table = 'beasiswas';

    protected $fillable = [
        'kode',
        'nama_beasiswa',
        'jenis',
        'jenis_beasiswa_id',
        'sumber_dana',
        'tahun_akademik_id',
        'semester',
        'tipe_diskon',
        'nilai_diskon',
        'komponen_biaya_id',
        'kuota',
        'terpakai',
        'tanggal_buka',
        'tanggal_tutup',
        'deskripsi',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'nilai_diskon' => 'decimal:2',
            'tanggal_buka' => 'date',
            'tanggal_tutup' => 'date',
            'status_aktif' => 'boolean',
            'semester' => 'integer',
            'kuota' => 'integer',
            'terpakai' => 'integer',
        ];
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function komponenBiaya(): BelongsTo
    {
        return $this->belongsTo(KomponenBiaya::class, 'komponen_biaya_id');
    }

    public function jenisBeasiswa(): BelongsTo
    {
        return $this->belongsTo(JenisBeasiswa::class, 'jenis_beasiswa_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BeasiswaMahasiswa::class, 'beasiswa_id');
    }

    public function pencairans(): HasMany
    {
        return $this->hasMany(BeasiswaPencairan::class, 'beasiswa_id');
    }

    public function getSisaKuotaAttribute(): int
    {
        return max(0, $this->kuota - $this->terpakai);
    }

    public function getIsFullAttribute(): bool
    {
        return $this->kuota > 0 && $this->terpakai >= $this->kuota;
    }

    /**
     * Hitung nominal diskon berdasarkan tipe.
     * $totalTagihan = nominal tagihan sebelum diskon.
     */
    public function hitungDiskon(float $totalTagihan): float
    {
        if ($this->tipe_diskon === 'full') {
            return $totalTagihan;
        }
        if ($this->tipe_diskon === 'persen') {
            $persen = min(100, max(0, (float) $this->nilai_diskon));
            return round($totalTagihan * $persen / 100, 2);
        }
        // nominal
        return min($totalTagihan, (float) $this->nilai_diskon);
    }
}
