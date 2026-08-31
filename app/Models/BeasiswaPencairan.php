<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeasiswaPencairan extends Model
{
    use HasFactory;

    protected $table = 'beasiswa_pencairans';

    protected $fillable = [
        'beasiswa_id',
        'termin_ke',
        'nominal_dijanjikan',
        'nominal_cair',
        'tanggal_janji_cair',
        'jatuh_tempo_external',
        'tanggal_cair',
        'bukti_tagihan',
        'bukti_cair',
        'status',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'nominal_dijanjikan' => 'decimal:2',
            'nominal_cair' => 'decimal:2',
            'tanggal_janji_cair' => 'date',
            'jatuh_tempo_external' => 'date',
            'tanggal_cair' => 'date',
        ];
    }

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Beasiswa::class, 'beasiswa_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BeasiswaMahasiswa::class, 'pencairan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSisaAttribute(): float
    {
        return max(0, (float)$this->nominal_dijanjikan - (float)$this->nominal_cair);
    }

    public function getIsLunasAttribute(): bool
    {
        return (float)$this->nominal_cair >= (float)$this->nominal_dijanjikan;
    }
}
