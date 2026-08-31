<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeasiswaMahasiswa extends Model
{
    use HasFactory;

    protected $table = 'beasiswa_mahasiswa';

    protected $fillable = [
        'beasiswa_id',
        'mahasiswa_id',
        'tagihan_id',
        'pencairan_id',
        'diskon_diterapkan',
        'status',
        'catatan_admin',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'diskon_diterapkan' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Beasiswa::class, 'beasiswa_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function pencairan(): BelongsTo
    {
        return $this->belongsTo(BeasiswaPencairan::class, 'pencairan_id');
    }
}
