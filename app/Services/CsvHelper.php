<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvHelper
{
    /**
     * Download data sebagai file CSV (dengan BOM agar terbuka rapi di Excel).
     *
     * @param  array<int,string>  $headers
     * @param  array<int,array<int,mixed>>  $rows
     */
    public static function download(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 agar karakter (mis. é) terbaca benar di Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Parse file CSV menjadi array asosiatif (header baris pertama = key).
     *
     * @return array<int,array<string,string>>
     */
    public static function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \Exception('Tidak dapat membaca file CSV.');
        }

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                // Hapus BOM dari header pertama
                $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $line[0]);
                $headers = array_map('trim', $line);
                continue;
            }

            // Lewati baris kosong
            if (count($line) === 1 && trim((string) $line[0]) === '') {
                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = trim((string) ($line[$i] ?? ''));
            }

            $rows[] = $assoc;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Normalisasi nilai status aktif dari berbagai format input.
     */
    public static function parseBoolean(mixed $value): bool
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'aktif', 'a', 'ya', 'yes', 'y', 'on'], true);
    }
}
