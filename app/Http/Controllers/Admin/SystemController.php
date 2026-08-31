<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SystemController extends Controller
{
    public function maintenance()
    {
        $isDown = app()->isDownForMaintenance();
        $downData = null;
        if (file_exists(storage_path('framework/down'))) {
            $downData = json_decode(file_get_contents(storage_path('framework/down')), true);
        }

        return Inertia::render('Admin/System/Maintenance', [
            'isDown' => $isDown,
            'downData' => $downData,
        ]);
    }

    public function toggleMaintenance(Request $request)
    {
        $request->validate([
            'action' => 'required|in:down,up',
            'secret' => 'nullable|string|max:100',
            'retry' => 'nullable|integer|min:30|max:3600',
            'message' => 'nullable|string|max:500',
        ]);

        if ($request->action === 'down') {
            $params = [];
            if ($request->filled('secret')) {
                $params['--secret'] = $request->secret;
            }
            if ($request->filled('retry')) {
                $params['--retry'] = $request->retry;
            }
            if ($request->filled('message')) {
                $params['--render'] = $request->message;
            }
            Artisan::call('down', $params);
            $msg = 'Mode maintenance diaktifkan.';
        } else {
            Artisan::call('up');
            $msg = 'Mode maintenance dinonaktifkan.';
        }

        return back()->with('success', $msg);
    }

    public function backup()
    {
        $backups = collect(Storage::disk('local')->files('backups'))
            ->map(function ($file) {
                return [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'size_label' => $this->formatBytes(Storage::disk('local')->size($file)),
                    'modified' => \Carbon\Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->format('d/m/Y H:i'),
                ];
            })
            ->sortByDesc('modified')
            ->values();

        // DB stats for info
        $tables = collect(DB::select('SHOW TABLES'))->map(function ($row) {
            $vals = array_values((array) $row);
            return $vals[0] ?? '';
        })->filter()->values();

        return Inertia::render('Admin/System/Backup', [
            'backups' => $backups,
            'tables' => $tables,
            'dbName' => config('database.connections.mysql.database'),
        ]);
    }

    public function runBackup()
    {
        try {
            $filename = 'backup-' . date('Ymd-His') . '.sql';
            $relative = 'backups/' . $filename;
            $fullPath = storage_path('app/private/' . $relative);
            // Ensure private/backups dir exists (Laravel 11+ uses storage/app/private)
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            // Fallback for older structure storage/app/backups
            $altDir = storage_path('app/backups');
            if (!is_dir(dirname($fullPath)) && !is_dir($altDir)) {
                mkdir($altDir, 0755, true);
                $fullPath = $altDir . '/' . $filename;
                $relative = 'backups/' . $filename;
            }

            // Try mysqldump if available, else fallback to simple SQL dump via PHP
            $dumped = $this->dumpDatabase($fullPath);

            if (!$dumped) {
                return back()->with('error', 'Gagal membuat backup.');
            }

            return back()->with('success', "Backup berhasil: {$filename}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal backup: ' . $e->getMessage());
        }
    }

    public function downloadBackup($filename)
    {
        $filename = basename($filename);
        foreach (['app/private/backups/' . $filename, 'app/backups/' . $filename] as $rel) {
            $path = storage_path($rel);
            if (is_file($path)) {
                return response()->download($path);
            }
        }
        abort(404);
    }

    public function deleteBackup($filename)
    {
        $filename = basename($filename);
        $deleted = false;
        foreach (['backups/' . $filename] as $rel) {
            if (Storage::disk('local')->exists($rel)) {
                Storage::disk('local')->delete($rel);
                $deleted = true;
            }
        }
        if (Storage::exists('backups/' . $filename)) {
            Storage::delete('backups/' . $filename);
            $deleted = true;
        }
        return back()->with($deleted ? 'success' : 'error', $deleted ? 'Backup dihapus.' : 'File tidak ditemukan.');
    }

    public function importBackup(Request $request)
    {
        $request->validate([
            'file' => ['required','file','mimes:sql,txt','max:102400'],
        ]);

        $file = $request->file('file');
        $sql = file_get_contents($file->getRealPath());
        if ($sql === false || trim($sql) === '') {
            return back()->with('error', 'File kosong atau tidak terbaca.');
        }

        try {
            // Try mysql binary restore first (skipped if exec disabled)
            $restored = $this->restoreViaMysql($file->getRealPath());
            if (!$restored) {
                // Fallback: execute via PDO — split by ; for compatibility on shared hosting where exec disabled
                // Also disables FK checks around restore
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                // Try single unprepared first (fast path)
                try {
                    DB::unprepared($sql);
                } catch (\Throwable $inner) {
                    // Fallback to statement-by-statement
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $stmt) {
                        if ($stmt === '' || str_starts_with($stmt, '--') || str_starts_with($stmt, '/*')) continue;
                        DB::unprepared($stmt);
                    }
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            return back()->with('success', 'Restore berhasil dari ' . $file->getClientOriginalName());
        } catch (\Throwable $e) {
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $__) {}
            return back()->with('error', 'Gagal restore: ' . $e->getMessage());
        }
    }

    private function isExecAvailable(): bool
    {
        if (!function_exists('exec')) return false;
        $disabled = ini_get('disable_functions');
        if ($disabled) {
            $list = array_map('trim', explode(',', $disabled));
            if (in_array('exec', $list, true)) return false;
        }
        return true;
    }

    private function restoreViaMysql(string $sqlPath): bool
    {
        if (!$this->isExecAvailable()) return false;

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $mysql = $this->findMysqldump();
        // mysqldump path -> mysql is same dir
        $candidates = ['mysql', str_replace('mysqldump','mysql',$mysql ?? '')];
        foreach ($candidates as $bin) {
            if (!$bin) continue;
            $out = null; $code = null;
            @\exec(\escapeshellarg($bin) . ' --version 2>&1', $out, $code);
            if ($code !== 0) continue;
            $cmd = sprintf(
                '%s --host=%s --port=%s --user=%s %s %s < %s 2>&1',
                \escapeshellarg($bin),
                \escapeshellarg($host),
                \escapeshellarg($port),
                \escapeshellarg($username),
                $password !== '' ? '--password=' . \escapeshellarg($password) : '',
                \escapeshellarg($database),
                \escapeshellarg($sqlPath)
            );
            \exec($cmd, $output, $code);
            if ($code === 0) return true;
        }
        return false;
    }

    private function dumpDatabase(string $fullPath): bool
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Try mysqldump binary (skip if exec disabled — common on shared hosting)
        if ($this->isExecAvailable()) {
            $mysqldump = $this->findMysqldump();
            if ($mysqldump) {
                $cmd = sprintf(
                    '%s --host=%s --port=%s --user=%s %s %s --result-file=%s 2>&1',
                    \escapeshellarg($mysqldump),
                    \escapeshellarg($host),
                    \escapeshellarg($port),
                    \escapeshellarg($username),
                    $password !== '' ? '--password=' . \escapeshellarg($password) : '',
                    \escapeshellarg($database),
                    \escapeshellarg($fullPath)
                );
                \exec($cmd, $output, $code);
                if ($code === 0 && is_file($fullPath) && filesize($fullPath) > 0) {
                    return true;
                }
            }
        }

        // Fallback: pure PHP dump (structure + data)
        try {
            $pdo = DB::connection()->getPdo();
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
            $out = "-- Backup " . date('Y-m-d H:i:s') . " DB: {$database}\nSET FOREIGN_KEY_CHECKS=0;\n\n";
            foreach ($tables as $table) {
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $out .= ($create['Create Table'] ?? '') . ";\n\n";
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $cols = array_map(fn($c) => '`' . str_replace('`','``',$c) . '`', array_keys($row));
                    $vals = array_map(function ($v) use ($pdo) {
                        if ($v === null) return 'NULL';
                        return $pdo->quote($v);
                    }, array_values($row));
                    $out .= "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
                }
                $out .= "\n";
            }
            $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
            file_put_contents($fullPath, $out);
            return is_file($fullPath) && filesize($fullPath) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function findMysqldump(): ?string
    {
        if (!$this->isExecAvailable()) return null;
        $candidates = ['mysqldump', 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe', 'C:\\xampp\\mysql\\bin\\mysqldump.exe'];
        foreach ($candidates as $bin) {
            $out = null; $code = null;
            @\exec(\escapeshellarg($bin) . ' --version 2>&1', $out, $code);
            if ($code === 0) return $bin;
        }
        return null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        $units = ['KB','MB','GB'];
        $i = 0; $v = $bytes / 1024;
        while ($v >= 1024 && $i < count($units)-1) { $v/=1024; $i++; }
        return round($v, 2) . ' ' . $units[$i];
    }
}
