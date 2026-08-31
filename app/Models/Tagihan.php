<?php

namespace App\Models;

use App\Helpers\SemesterHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    use HasFactory;

    protected $fillable = [
        'mahasiswa_id',
        'semester',
        'tahun_akademik',
        'nominal',
        'status',
        'jatuh_tempo',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'jatuh_tempo' => 'date',
        ];
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function dispensasis()
    {
        return $this->hasMany(Dispensasi::class);
    }

    /**
     * Auto-generate tagihan for all active mahasiswa based on active BiayaKonfigurasi.
     * Returns ['created' => int, 'skipped' => int].
     */
    public static function generateForAll(?string $tahunAkademik = null, ?string $jatuhTempo = null): array
    {
        $semesterAktif = \App\Models\SemesterAktif::instance();
        $tahunAkademik = $tahunAkademik ?? $semesterAktif->tahun_akademik;
        $jatuhTempo = $jatuhTempo ?? $semesterAktif->jatuh_tempo;

        // Get active TahunAkademik for semester formula
        $ta = \App\Models\TahunAkademik::where('is_aktif', true)->first();
        if (!$ta) {
            return ['created' => 0, 'skipped' => 0];
        }

        // Group active biaya config by angkatan+jurusan to get total per group
        $groups = \App\Models\BiayaKonfigurasi::where('status_aktif', true)
            ->selectRaw('angkatan, jurusan, SUM(nominal) as total_biaya')
            ->groupBy('angkatan', 'jurusan')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $mahasiswas = \App\Models\Mahasiswa::where('angkatan', $group->angkatan)
                ->where('jurusan', $group->jurusan)
                ->where('status_aktif', true)
                ->get();

            /** @var \App\Services\BeasiswaService $beasiswaService */
            $beasiswaService = app(\App\Services\BeasiswaService::class);

            foreach ($mahasiswas as $mhs) {
                // Calculate current semester for this mahasiswa based on active tahun akademik
                $semester = SemesterHelper::hitung($mhs->angkatan, $ta->nama, $ta->semester);

                $exists = static::where('mahasiswa_id', $mhs->id)
                    ->where('semester', $semester)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $totalBiaya = (float) $group->total_biaya;
                $hitung = $beasiswaService->hitungTagihan($mhs, $tahunAkademik, $semester, $totalBiaya);
                $nominal = $hitung['nominal_akhir'];
                $keterangan = 'Tagihan UKT ' . $tahunAkademik . ' semester ' . $semester;
                if ($hitung['beasiswa']) {
                    $keterangan .= ' (Beasiswa ' . $hitung['beasiswa']->kode . ' - potongan Rp ' . number_format($hitung['diskon'], 0, ',', '.') . ')';
                }

                $isFullGratis = $hitung['beasiswa'] && $nominal == 0;
                $tagihan = static::create([
                    'mahasiswa_id' => $mhs->id,
                    'semester' => $semester,
                    'tahun_akademik' => $tahunAkademik,
                    'nominal' => $nominal,
                    'status' => $isFullGratis ? 'sudah_dibayar' : 'belum_dibayar',
                    'jatuh_tempo' => $jatuhTempo,
                    'keterangan' => $keterangan . ($isFullGratis ? ' — Full Gratis' : ''),
                ]);

                // Jika full gratis, buat pembayaran 0 untuk audit (masuk laporan lunas H2 Talangan)
                if ($isFullGratis) {
                    $metodeId = \App\Models\MetodePembayaran::where('kode', 'BEASISWA')->value('id')
                        ?? \App\Models\MetodePembayaran::first()?->id ?? 1;
                    \App\Models\Pembayaran::create([
                        'tagihan_id' => $tagihan->id,
                        'metode_pembayaran_id' => $metodeId,
                        'jumlah_bayar' => 0,
                        'nama_pengirim' => 'Beasiswa ' . $hitung['beasiswa']->kode,
                        'status' => 'dikonfirmasi',
                        'catatan_admin' => 'Otomatis lunas — Beasiswa Full Gratis ' . $hitung['beasiswa']->kode . ($hitung['beasiswa']->sumber_dana ? ' ('.$hitung['beasiswa']->sumber_dana.')' : '') . ' — Talangan H2',
                        'verified_by' => null,
                        'verified_at' => now(),
                    ]);
                }

                // Jika ada beasiswa, link tagihan ke assignment untuk audit
                if ($hitung['beasiswa']) {
                    $assignment = \App\Models\BeasiswaMahasiswa::where('beasiswa_id', $hitung['beasiswa']->id)
                        ->where('mahasiswa_id', $mhs->id)
                        ->whereIn('status', ['disetujui','aktif'])
                        ->first();
                    if ($assignment && !$assignment->tagihan_id) {
                        $assignment->update([
                            'tagihan_id' => $tagihan->id,
                            'diskon_diterapkan' => $hitung['diskon'],
                        ]);
                    }
                }

                $created++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
