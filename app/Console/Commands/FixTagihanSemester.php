<?php

namespace App\Console\Commands;

use App\Models\Tagihan;
use Illuminate\Console\Command;

class FixTagihanSemester extends Command
{
    protected $signature = 'tagihan:fix-semester {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Geser semester tagihan -1 (koreksi bug hitungNext) dan perbaiki keterangan';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tagihans = Tagihan::orderBy('mahasiswa_id')->orderBy('semester')->get();

        $updated = 0;
        $skipped = 0;
        $collisions = 0;

        // Tracks semester values already assigned during this pass, per (mahasiswa, tahun_akademik).
        // Needed so the dry-run (which never writes to DB) mirrors the real run correctly.
        $assigned = [];

        foreach ($tagihans as $tagihan) {
            $newSemester = $tagihan->semester - 1;
            $key = $tagihan->mahasiswa_id . '|' . $tagihan->tahun_akademik;

            if ($newSemester < 1) {
                $skipped++;
                $this->line("  SKIP id={$tagihan->id} semester {$tagihan->semester} -> {$newSemester} (di bawah 1)");
                continue;
            }

            $collision = $dryRun
                ? in_array($newSemester, $assigned[$key] ?? [], true)
                : Tagihan::where('mahasiswa_id', $tagihan->mahasiswa_id)
                    ->where('tahun_akademik', $tagihan->tahun_akademik)
                    ->where('semester', $newSemester)
                    ->where('id', '!=', $tagihan->id)
                    ->exists();

            if ($collision) {
                $collisions++;
                $this->line("  KONFLIK id={$tagihan->id} mhs={$tagihan->mahasiswa_id} semester {$tagihan->semester} -> {$newSemester} sudah ada");
                continue;
            }

            $newKeterangan = 'Tagihan UKT ' . $tagihan->tahun_akademik . ' semester ' . $newSemester;

            $this->line("  FIX id={$tagihan->id} semester {$tagihan->semester} -> {$newSemester} (keterangan: {$tagihan->keterangan} => {$newKeterangan})");

            if (!$dryRun) {
                $tagihan->update([
                    'semester' => $newSemester,
                    'keterangan' => $newKeterangan,
                ]);
            }

            $assigned[$key][] = $newSemester;
            $updated++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Diperbarui: {$updated}, dilewati: {$skipped}, konflik: {$collisions}");

        return $collisions > 0 ? self::FAILURE : self::SUCCESS;
    }
}
