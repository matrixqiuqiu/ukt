<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelHelper
{
    /**
     * Download data sebagai file Excel (.xlsx).
     *
     * @param  array<int,string>  $headers
     * @param  array<int,array<int,mixed>>  $rows
     */
    public static function download(string $filename, array $headers, array $rows, ?callable $rowStyle = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $colLetter = static fn (int $index): string =>
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);

        // Header
        foreach ($headers as $index => $header) {
            $sheet->setCellValue($colLetter($index + 1) . '1', $header);
        }

        // Data
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue($colLetter($colIndex + 1) . ($rowIndex + 2), $value);
            }
            if ($rowStyle) {
                $color = $rowStyle($row, $rowIndex);
                if ($color) {
                    $range = 'A'.($rowIndex+2).':'.$sheet->getHighestDataColumn().($rowIndex+2);
                    $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($color);
                }
            }
        }

        // Styling header (bold + background)
        $headerRange = 'A1:' . $sheet->getHighestDataColumn() . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');

        // Auto width kolom
        foreach ($headers as $index => $header) {
            $sheet->getColumnDimension($colLetter($index + 1))->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Parse file Excel (.xlsx/.xls) menjadi array asosiatif (baris pertama = header).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function parse(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(); // 0-indexed

        if (empty($rows)) {
            return [];
        }

        $headers = array_map('trim', (array) array_shift($rows));

        $result = [];
        foreach ($rows as $row) {
            // Lewati baris kosong
            if (trim(implode('', $row)) === '') {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = trim((string) ($row[$index] ?? ''));
            }

            $result[] = $assoc;
        }

        return $result;
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
