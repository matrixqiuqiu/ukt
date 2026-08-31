<?php

namespace App\Services;

use App\Models\Beasiswa;
use App\Models\BeasiswaMahasiswa;
use App\Models\Mahasiswa;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

class BeasiswaService
{
    /**
     * Hitung nominal akhir tagihan setelah beasiswa (jika ada yang aktif untuk mahasiswa+periode).
     * Kembalikan ['nominal_awal' => float, 'diskon' => float, 'nominal_akhir' => float, 'beasiswa' => ?Beasiswa]
     */
    public function hitungTagihan(Mahasiswa $mahasiswa, string $tahunAkademik, int $semester, float $totalTagihan): array
    {
        $beasiswa = $this->cariBeasiswaAktif($mahasiswa, $tahunAkademik, $semester);

        if (!$beasiswa) {
            return [
                'nominal_awal' => $totalTagihan,
                'diskon' => 0,
                'nominal_akhir' => $totalTagihan,
                'beasiswa' => null,
            ];
        }

        $diskon = $beasiswa->hitungDiskon($totalTagihan);

        return [
            'nominal_awal' => $totalTagihan,
            'diskon' => $diskon,
            'nominal_akhir' => max(0, $totalTagihan - $diskon),
            'beasiswa' => $beasiswa,
        ];
    }

    /**
     * Cari beasiswa aktif yang berlaku untuk mahasiswa pada periode tersebut.
     * Prioritas: beasiswa dengan tahun_akademik+semester spesifik, lalu yang umum (null).
     */
    public function cariBeasiswaAktif(Mahasiswa $mahasiswa, string $tahunAkademik, int $semester): ?Beasiswa
    {
        // Cek assignment langsung yang sudah disetujui/aktif untuk mahasiswa ini
        $assignment = BeasiswaMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->whereIn('status', ['disetujui', 'aktif'])
            ->whereHas('beasiswa', function ($q) use ($tahunAkademik, $semester) {
                $q->where('status_aktif', true)
                  ->where(function ($qq) use ($tahunAkademik, $semester) {
                      $qq->where(function ($sub) use ($tahunAkademik, $semester) {
                          $sub->whereHas('tahunAkademik', fn ($t) => $t->where('nama', $tahunAkademik))
                              ->where('semester', $semester);
                      })->orWhere(function ($sub) {
                          $sub->whereNull('tahun_akademik_id')->whereNull('semester');
                      });
                  });
            })
            ->with('beasiswa')
            ->first();

        if ($assignment) {
            return $assignment->beasiswa;
        }

        return null;
    }

    /**
     * Assign beasiswa ke mahasiswa untuk tagihan tertentu.
     */
    public function assign(Mahasiswa $mahasiswa, Beasiswa $beasiswa, ?Tagihan $tagihan = null, string $status = 'disetujui', ?int $approvedBy = null): BeasiswaMahasiswa
    {
        if ($beasiswa->kuota > 0 && $beasiswa->terpakai >= $beasiswa->kuota) {
            throw new \RuntimeException('Kuota beasiswa sudah penuh.');
        }

        // Jika tagihan tidak diberikan tapi beasiswa punya periode spesifik, cari tagihan yang cocok
        if (!$tagihan && $beasiswa->tahun_akademik_id && $beasiswa->semester) {
            $ta = $beasiswa->tahunAkademik;
            if ($ta) {
                $semNum = \App\Helpers\SemesterHelper::hitung((string)$mahasiswa->angkatan, $ta->nama, $beasiswa->semester);
                $tagihan = Tagihan::where('mahasiswa_id', $mahasiswa->id)->where('tahun_akademik', $ta->nama)->where('semester', $semNum)->first();
            }
        }
        if (!$tagihan) {
            // Fallback: tagihan terbaru belum lunas jika beasiswa Umum
            $tagihan = Tagihan::where('mahasiswa_id', $mahasiswa->id)->where('status', 'belum_dibayar')->latest()->first();
        }

        $diskon = $tagihan ? $beasiswa->hitungDiskon((float) $tagihan->nominal) : 0;

        return DB::transaction(function () use ($mahasiswa, $beasiswa, $tagihan, $status, $approvedBy, $diskon) {
            $record = BeasiswaMahasiswa::create([
                'beasiswa_id' => $beasiswa->id,
                'mahasiswa_id' => $mahasiswa->id,
                'tagihan_id' => $tagihan?->id,
                'diskon_diterapkan' => $diskon,
                'status' => $status,
                'approved_by' => $approvedBy,
                'approved_at' => $status !== 'diajukan' ? now() : null,
            ]);

            // Update terpakai jika disetujui/aktif
            if (in_array($status, ['disetujui', 'aktif'], true)) {
                $beasiswa->increment('terpakai');
            }

            // Jika ada tagihan, potong nominalnya
            if ($tagihan && in_array($status, ['disetujui', 'aktif'], true)) {
                $nominalAkhir = max(0, (float) $tagihan->nominal - $diskon);
                $tagihan->update(['nominal' => $nominalAkhir]);

                // H2 Talangan: jika full gratis (nominal 0) langsung anggap lunas dan buat pembayaran 0 untuk audit
                if ($nominalAkhir == 0 && $tagihan->status !== 'sudah_dibayar') {
                    $tagihan->update(['status' => 'sudah_dibayar']);
                    $metodeBeasiswaId = \App\Models\MetodePembayaran::where('kode', 'BEASISWA')->value('id') ?? \App\Models\MetodePembayaran::first()?->id ?? 1;
                    \App\Models\Pembayaran::create([
                        'tagihan_id' => $tagihan->id,
                        'metode_pembayaran_id' => $metodeBeasiswaId,
                        'jumlah_bayar' => 0,
                        'nama_pengirim' => 'Beasiswa ' . $beasiswa->kode,
                        'status' => 'dikonfirmasi',
                        'catatan_admin' => 'Full gratis via beasiswa ' . $beasiswa->kode . ($beasiswa->sumber_dana ? ' ('.$beasiswa->sumber_dana.')' : ''),
                        'verified_by' => $approvedBy,
                        'verified_at' => now(),
                    ]);
                }
            }

            return $record;
        });
    }

    /**
     * Cabut beasiswa dari mahasiswa/tagihan.
     * Untuk full gratis, kembalikan status tagihan dan hapus pembayaran 0.
     */
    public function revoke(BeasiswaMahasiswa $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            $beasiswa = $assignment->beasiswa;
            $tagihan = $assignment->tagihan;

            $wasFullGratis = $tagihan && $tagihan->nominal == 0 && $tagihan->status === 'sudah_dibayar';

            if ($tagihan && $assignment->diskon_diterapkan > 0) {
                $tagihan->increment('nominal', $assignment->diskon_diterapkan);
                if ($wasFullGratis) {
                    $autoPay = \App\Models\Pembayaran::where('tagihan_id', $tagihan->id)
                        ->where('jumlah_bayar', 0)
                        ->where('status', 'dikonfirmasi')
                        ->where('catatan_admin', 'like', '%Beasiswa%')
                        ->first();
                    if ($autoPay) {
                        $autoPay->delete();
                    }
                    $tagihan->update(['status' => 'belum_dibayar']);
                }
            }

            if (in_array($assignment->status, ['disetujui', 'aktif'], true) && $beasiswa) {
                $beasiswa->decrement('terpakai');
            }

            $assignment->delete();
        });
    }

    /**
     * Sinkronkan tagihan untuk semua penerima beasiswa ini.
     * Jika tagihan sudah ada untuk periode beasiswa, potong nominalnya.
     * Return ['synced'=>int, 'skipped'=>int]
     */
    public function syncTagihan(Beasiswa $beasiswa): array
    {
        $synced = 0; $skipped = 0;
        $assignments = BeasiswaMahasiswa::where('beasiswa_id', $beasiswa->id)->with(['mahasiswa','tagihan'])->get();

        foreach ($assignments as $assign) {
            // Sudah punya tagihan link dan diskon cocok -> skip
            if ($assign->tagihan_id && $assign->diskon_diterapkan > 0) { $skipped++; continue; }

            // Cari tagihan yang sesuai periode beasiswa
            $query = Tagihan::where('mahasiswa_id', $assign->mahasiswa_id);
            if ($beasiswa->tahun_akademik_id && $beasiswa->semester) {
                $ta = $beasiswa->tahunAkademik;
                if ($ta) {
                    $query->where('tahun_akademik', $ta->nama)->where('semester', $this->semesterFlagToNumber($beasiswa, $assign->mahasiswa));
                }
            }
            // Jika beasiswa Umum (tanpa periode), ambil tagihan terbaru yang belum lunas
            $tagihan = $query->latest()->first();
            if (!$tagihan) { $skipped++; continue; }

            // Jika tagihan sudah terpotong / sudah ada diskon untuk tagihan ini, skip
            $existingForTagihan = BeasiswaMahasiswa::where('tagihan_id', $tagihan->id)->exists();
            if ($existingForTagihan && $assign->tagihan_id) { $skipped++; continue; }

            $diskon = $beasiswa->hitungDiskon((float) $tagihan->nominal);
            if ($diskon <= 0) { $skipped++; continue; }

            DB::transaction(function () use ($assign, $tagihan, $diskon) {
                $tagihan->decrement('nominal', $diskon);
                $assign->update(['tagihan_id' => $tagihan->id, 'diskon_diterapkan' => $diskon]);
            });
            $synced++;
        }
        return ['synced' => $synced, 'skipped' => $skipped];
    }

    private function semesterFlagToNumber(Beasiswa $beasiswa, Mahasiswa $mhs): int
    {
        if ($beasiswa->semester) {
            $ta = $beasiswa->tahunAkademik;
            if ($ta) {
                return \App\Helpers\SemesterHelper::hitung((string)$mhs->angkatan, $ta->nama, $beasiswa->semester);
            }
            return (int)$beasiswa->semester;
        }
        // Umum -> pakai semester aktif
        $taAktif = \App\Models\TahunAkademik::aktif();
        if ($taAktif) {
            return \App\Helpers\SemesterHelper::hitung((string)$mhs->angkatan, $taAktif->nama, $taAktif->semester);
        }
        return (int)$mhs->semester;
    }
}
