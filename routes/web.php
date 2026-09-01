<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MahasiswaController as AdminMahasiswaController;
use App\Http\Controllers\Admin\TagihanController as AdminTagihanController;
use App\Http\Controllers\Admin\PembayaranController as AdminPembayaranController;
use App\Http\Controllers\Admin\VerifikasiController as AdminVerifikasiController;
use App\Http\Controllers\Admin\BankController as AdminBankController;
use App\Http\Controllers\Admin\SiakadController as AdminSiakadController;
use App\Http\Controllers\Admin\KomponenBiayaController as AdminKomponenBiayaController;
use App\Http\Controllers\Admin\BiayaKonfigurasiController as AdminBiayaKonfigurasiController;
use App\Http\Controllers\Admin\JurusanController as AdminJurusanController;
use App\Http\Controllers\Admin\FakultasController as AdminFakultasController;
use App\Http\Controllers\Admin\ThemeSettingController as AdminThemeSettingController;
use App\Http\Controllers\Admin\SemesterAktifController as AdminSemesterAktifController;
use App\Http\Controllers\Admin\TahunAkademikController as AdminTahunAkademikController;
use App\Http\Controllers\Admin\OperationsController as AdminOperationsController;
use App\Http\Controllers\Admin\DispensasiController as AdminDispensasiController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WebsiteProfileController as AdminWebsiteProfileController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\Admin\BeasiswaController as AdminBeasiswaController;
use App\Http\Controllers\Admin\BeasiswaPencairanController as AdminBeasiswaPencairanController;
use App\Http\Controllers\Admin\JenisBeasiswaController as AdminJenisBeasiswaController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\TagihanController as MahasiswaTagihanController;
use App\Http\Controllers\Mahasiswa\PembayaranController as MahasiswaPembayaranController;
use App\Http\Controllers\Mahasiswa\RiwayatController as MahasiswaRiwayatController;
use App\Http\Controllers\Mahasiswa\DispensasiController as MahasiswaDispensasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('mahasiswa.dashboard');
    }
    return redirect()->route('login');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/mahasiswa', [AdminMahasiswaController::class, 'index'])->name('mahasiswa.index');
    Route::post('/mahasiswa/{id}/impersonate', [AdminMahasiswaController::class, 'impersonate'])->name('mahasiswa.impersonate');
    Route::get('/mahasiswa/{id}', [AdminMahasiswaController::class, 'show'])->name('mahasiswa.show');
    Route::get('/tagihan', [AdminTagihanController::class, 'index'])->name('tagihan.index');
    Route::get('/tagihan/{id}', [AdminTagihanController::class, 'show'])->name('tagihan.show');
    Route::get('/pembayaran', [AdminPembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/export-lunas', [AdminPembayaranController::class, 'exportLunas'])->name('pembayaran.export-lunas');
    Route::get('/pembayaran/export-lunas-pdf', [AdminPembayaranController::class, 'exportLunasPdf'])->name('pembayaran.export-lunas-pdf');
    Route::get('/pembayaran/{id}', [AdminPembayaranController::class, 'show'])->name('pembayaran.show');
    Route::post('/pembayaran/{id}/verifikasi', [AdminPembayaranController::class, 'verifikasi'])->name('pembayaran.verifikasi');
    Route::post('/pembayaran/{id}/tolak', [AdminPembayaranController::class, 'tolak'])->name('pembayaran.tolak');
    Route::get('/verifikasi', [AdminVerifikasiController::class, 'index'])->name('verifikasi.index');
    Route::get('/verifikasi/ringkasan', [AdminVerifikasiController::class, 'ringkasan'])->name('verifikasi.ringkasan');
    Route::get('/bank', [AdminBankController::class, 'index'])->name('bank.index');
    Route::post('/bank', [AdminBankController::class, 'store'])->name('bank.store');
    Route::put('/bank/{id}', [AdminBankController::class, 'update'])->name('bank.update');
    Route::delete('/bank/{id}', [AdminBankController::class, 'destroy'])->name('bank.destroy');
    Route::post('/bank/{id}/toggle', [AdminBankController::class, 'toggle'])->name('bank.toggle');
    Route::post('/bank/{id}/upload-logo', [AdminBankController::class, 'uploadLogo'])->name('bank.upload-logo');
    Route::post('/siakad/sync-mahasiswa', [AdminSiakadController::class, 'syncMahasiswa'])->name('siakad.sync-mahasiswa');
    Route::get('/siakad/test-connection', [AdminSiakadController::class, 'testConnection'])->name('siakad.test-connection');

    // Komponen Biaya
    Route::get('/komponen-biaya', [AdminKomponenBiayaController::class, 'index'])->name('komponen-biaya.index');
    Route::post('/komponen-biaya', [AdminKomponenBiayaController::class, 'store'])->name('komponen-biaya.store');
    Route::put('/komponen-biaya/{id}', [AdminKomponenBiayaController::class, 'update'])->name('komponen-biaya.update');
    Route::delete('/komponen-biaya/{id}', [AdminKomponenBiayaController::class, 'destroy'])->name('komponen-biaya.destroy');
    Route::post('/komponen-biaya/{id}/toggle', [AdminKomponenBiayaController::class, 'toggle'])->name('komponen-biaya.toggle');

    // Biaya Konfigurasi
    Route::get('/biaya', [AdminBiayaKonfigurasiController::class, 'index'])->name('biaya.index');
    Route::post('/biaya', [AdminBiayaKonfigurasiController::class, 'store'])->name('biaya.store');
    Route::put('/biaya/{id}', [AdminBiayaKonfigurasiController::class, 'update'])->name('biaya.update');
    Route::delete('/biaya/{id}', [AdminBiayaKonfigurasiController::class, 'destroy'])->name('biaya.destroy');
    Route::post('/biaya/{id}/toggle', [AdminBiayaKonfigurasiController::class, 'toggle'])->name('biaya.toggle');

    // Semester Aktif
    Route::get('/semester-aktif', [AdminSemesterAktifController::class, 'index'])->name('semester-aktif.index');
    Route::put('/semester-aktif', [AdminSemesterAktifController::class, 'update'])->name('semester-aktif.update');

    // Jurusan
    Route::get('/jurusan', [AdminJurusanController::class, 'index'])->name('jurusan.index');
    Route::post('/jurusan', [AdminJurusanController::class, 'store'])->name('jurusan.store');
    Route::put('/jurusan/{id}', [AdminJurusanController::class, 'update'])->name('jurusan.update');
    Route::delete('/jurusan/{id}', [AdminJurusanController::class, 'destroy'])->name('jurusan.destroy');
    Route::post('/jurusan/{id}/toggle', [AdminJurusanController::class, 'toggle'])->name('jurusan.toggle');
    Route::get('/jurusan/export', [AdminJurusanController::class, 'export'])->name('jurusan.export');
    Route::post('/jurusan/import', [AdminJurusanController::class, 'import'])->name('jurusan.import');

    // Fakultas
    Route::get('/fakultas', [AdminFakultasController::class, 'index'])->name('fakultas.index');
    Route::post('/fakultas', [AdminFakultasController::class, 'store'])->name('fakultas.store');
    Route::put('/fakultas/{id}', [AdminFakultasController::class, 'update'])->name('fakultas.update');
    Route::delete('/fakultas/{id}', [AdminFakultasController::class, 'destroy'])->name('fakultas.destroy');
    Route::post('/fakultas/{id}/toggle', [AdminFakultasController::class, 'toggle'])->name('fakultas.toggle');
    Route::get('/fakultas/export', [AdminFakultasController::class, 'export'])->name('fakultas.export');
    Route::post('/fakultas/import', [AdminFakultasController::class, 'import'])->name('fakultas.import');

    // Theme Settings
    Route::get('/pengaturan', [AdminThemeSettingController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan', [AdminThemeSettingController::class, 'update'])->name('pengaturan.update');
    Route::post('/pengaturan/reset', [AdminThemeSettingController::class, 'reset'])->name('pengaturan.reset');
    Route::post('/pengaturan/upload-logo', [AdminThemeSettingController::class, 'uploadLogo'])->name('pengaturan.upload-logo');
    Route::post('/pengaturan/upload-header', [AdminThemeSettingController::class, 'uploadInvoiceHeader'])->name('pengaturan.upload-header');

    // Tahun Akademik
    Route::get('/tahun-akademik', [AdminTahunAkademikController::class, 'index'])->name('tahun-akademik.index');
    Route::post('/tahun-akademik', [AdminTahunAkademikController::class, 'store'])->name('tahun-akademik.store');
    Route::put('/tahun-akademik/{id}', [AdminTahunAkademikController::class, 'update'])->name('tahun-akademik.update');
    Route::delete('/tahun-akademik/{id}', [AdminTahunAkademikController::class, 'destroy'])->name('tahun-akademik.destroy');
    Route::post('/tahun-akademik/{id}/toggle', [AdminTahunAkademikController::class, 'toggle'])->name('tahun-akademik.toggle');

    // Pengaturan User
    Route::get('/user', [AdminUserController::class, 'index'])->name('user.index');
    Route::post('/user', [AdminUserController::class, 'store'])->name('user.store');
    Route::put('/user/{id}', [AdminUserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}', [AdminUserController::class, 'destroy'])->name('user.destroy');

    // Profil Website
    Route::get('/profil-website', [AdminWebsiteProfileController::class, 'index'])->name('profil-website.index');
    Route::put('/profil-website', [AdminWebsiteProfileController::class, 'update'])->name('profil-website.update');

    // Operations - Payment Gateway
    Route::get('/operations', [AdminOperationsController::class, 'index'])->name('operations.index');
    Route::post('/operations/test-token', [AdminOperationsController::class, 'testToken'])->name('operations.test-token');
    Route::post('/operations/test-endpoint', [AdminOperationsController::class, 'testEndpoint'])->name('operations.test-endpoint');
    Route::post('/operations/simulate-payment', [AdminOperationsController::class, 'simulatePayment'])->name('operations.simulate-payment');
    Route::get('/operations/transaksi-history', [AdminOperationsController::class, 'transaksiHistory'])->name('operations.transaksi-history');
    Route::post('/operations/clear-api-logs', [AdminOperationsController::class, 'clearApiLogs'])->name('operations.clear-api-logs');
    Route::get('/operations/monitoring', [AdminOperationsController::class, 'monitoring'])->name('operations.monitoring');
    Route::get('/operations/transaction-detail/{id}', [AdminOperationsController::class, 'transactionDetail'])->name('operations.transaction-detail');
    Route::get('/tagihan/{id}/invoice', [MahasiswaTagihanController::class, 'invoice'])->name('tagihan.invoice');
    Route::get('/tagihan/{id}/print', [MahasiswaTagihanController::class, 'print'])->name('tagihan.print');

    // Jenis Beasiswa
    Route::get('/jenis-beasiswa', [AdminJenisBeasiswaController::class, 'index'])->name('jenis-beasiswa.index');
    Route::post('/jenis-beasiswa', [AdminJenisBeasiswaController::class, 'store'])->name('jenis-beasiswa.store');
    Route::put('/jenis-beasiswa/{id}', [AdminJenisBeasiswaController::class, 'update'])->name('jenis-beasiswa.update');
    Route::delete('/jenis-beasiswa/{id}', [AdminJenisBeasiswaController::class, 'destroy'])->name('jenis-beasiswa.destroy');
    Route::post('/jenis-beasiswa/{id}/toggle', [AdminJenisBeasiswaController::class, 'toggle'])->name('jenis-beasiswa.toggle');
    Route::get('/jenis-beasiswa/export', [AdminJenisBeasiswaController::class, 'export'])->name('jenis-beasiswa.export');

    // Beasiswa
    Route::get('/beasiswa', [AdminBeasiswaController::class, 'index'])->name('beasiswa.index');
    Route::post('/beasiswa', [AdminBeasiswaController::class, 'store'])->name('beasiswa.store');
    Route::put('/beasiswa/{id}', [AdminBeasiswaController::class, 'update'])->name('beasiswa.update');
    Route::delete('/beasiswa/{id}', [AdminBeasiswaController::class, 'destroy'])->name('beasiswa.destroy');
    Route::post('/beasiswa/{id}/toggle', [AdminBeasiswaController::class, 'toggle'])->name('beasiswa.toggle');
    Route::get('/beasiswa/export', [AdminBeasiswaController::class, 'export'])->name('beasiswa.export');
    Route::get('/beasiswa/{id}/penerima', [AdminBeasiswaController::class, 'assignments'])->name('beasiswa.assignments');
    Route::get('/beasiswa/{id}/penerima/export', [AdminBeasiswaController::class, 'exportPenerima'])->name('beasiswa.penerima.export');
    Route::get('/beasiswa/{id}/penerima/export-pdf', [AdminBeasiswaController::class, 'exportPenerimaPdf'])->name('beasiswa.penerima.export-pdf');
    Route::get('/beasiswa/{id}/cari-mahasiswa', [AdminBeasiswaController::class, 'searchMahasiswa'])->name('beasiswa.search-mahasiswa');
    Route::post('/beasiswa/{id}/assign', [AdminBeasiswaController::class, 'assign'])->name('beasiswa.assign');
    Route::post('/beasiswa/{id}/assign-bulk', [AdminBeasiswaController::class, 'assignBulk'])->name('beasiswa.assign-bulk');
    Route::post('/beasiswa/{id}/sinkron-tagihan', [AdminBeasiswaController::class, 'syncTagihan'])->name('beasiswa.sync-tagihan');
    Route::delete('/beasiswa/{id}/penerima/{assignmentId}', [AdminBeasiswaController::class, 'revoke'])->name('beasiswa.revoke');
    Route::get('/beasiswa/{id}/pencairan', [AdminBeasiswaPencairanController::class, 'index'])->name('beasiswa.pencairan.index');
    Route::post('/beasiswa/{id}/pencairan', [AdminBeasiswaPencairanController::class, 'store'])->name('beasiswa.pencairan.store');
    Route::put('/beasiswa/pencairan/{id}', [AdminBeasiswaPencairanController::class, 'update'])->name('beasiswa.pencairan.update');
    Route::post('/beasiswa/pencairan/{id}/konfirmasi', [AdminBeasiswaPencairanController::class, 'konfirmasi'])->name('beasiswa.pencairan.konfirmasi');
    Route::delete('/beasiswa/pencairan/{id}', [AdminBeasiswaPencairanController::class, 'destroy'])->name('beasiswa.pencairan.destroy');

    // System
    Route::get('/system/maintenance', [AdminSystemController::class, 'maintenance'])->name('system.maintenance');
    Route::post('/system/maintenance/toggle', [AdminSystemController::class, 'toggleMaintenance'])->name('system.maintenance.toggle');
    Route::get('/system/backup', [AdminSystemController::class, 'backup'])->name('system.backup');
    Route::post('/system/backup/run', [AdminSystemController::class, 'runBackup'])->name('system.backup.run');
    Route::post('/system/backup/import', [AdminSystemController::class, 'importBackup'])->name('system.backup.import');
    Route::get('/system/backup/download/{filename}', [AdminSystemController::class, 'downloadBackup'])->name('system.backup.download');
    Route::delete('/system/backup/{filename}', [AdminSystemController::class, 'deleteBackup'])->name('system.backup.delete');

    // Dispensasi
    Route::get('/dispensasi', [AdminDispensasiController::class, 'index'])->name('dispensasi.index');
    Route::post('/dispensasi/upload-template', [AdminDispensasiController::class, 'uploadTemplate'])->name('dispensasi.upload-template');
    Route::get('/dispensasi/download-template', [AdminDispensasiController::class, 'downloadTemplate'])->name('dispensasi.download-template');
    Route::post('/dispensasi/{id}/approve', [AdminDispensasiController::class, 'approve'])->name('dispensasi.approve');
    Route::post('/dispensasi/{id}/reject', [AdminDispensasiController::class, 'reject'])->name('dispensasi.reject');
});

// Impersonate leave (bisa diakses mahasiswa yang sedang di-impersonate maupun admin)
Route::middleware('auth')->post('/impersonate/leave', [AdminMahasiswaController::class, 'leaveImpersonate'])->name('impersonate.leave');

// Mahasiswa Routes
Route::middleware(['auth', 'verified', 'role:mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/dashboard', [MahasiswaDashboardController::class, 'index'])->name('dashboard');
    Route::get('/tagihan', [MahasiswaTagihanController::class, 'index'])->name('tagihan.index');
    Route::get('/tagihan/{id}', [MahasiswaTagihanController::class, 'show'])->name('tagihan.show');
    Route::get('/tagihan/{id}/invoice', [MahasiswaTagihanController::class, 'invoice'])->name('tagihan.invoice');
    Route::get('/tagihan/{id}/print', [MahasiswaTagihanController::class, 'print'])->name('tagihan.print');
    Route::post('/pembayaran', [MahasiswaPembayaranController::class, 'store'])->name('pembayaran.store');
    Route::get('/pembayaran/{id}', [MahasiswaPembayaranController::class, 'show'])->name('pembayaran.show');
    Route::post('/pembayaran/{id}/check-status', [MahasiswaPembayaranController::class, 'checkStatus'])->name('pembayaran.check-status');
    Route::get('/riwayat', [MahasiswaRiwayatController::class, 'index'])->name('riwayat.index');
    Route::get('/riwayat/{id}', [MahasiswaRiwayatController::class, 'show'])->name('riwayat.show');

    // Dispensasi
    Route::get('/dispensasi', [MahasiswaDispensasiController::class, 'index'])->name('dispensasi.index');
    Route::post('/dispensasi', [MahasiswaDispensasiController::class, 'store'])->name('dispensasi.store');
    Route::get('/dispensasi/download-template', [MahasiswaDispensasiController::class, 'downloadTemplate'])->name('dispensasi.download-template');
});

// Invoice routes - accessible by both admin and mahasiswa
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/invoice/{tagihan_id}', [MahasiswaTagihanController::class, 'invoice'])->name('invoice.show');
});

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

// Public invoice verification via signed URL (linked from printed invoices)
Route::get('/verify/invoice/{pembayaran}', function (Illuminate\Http\Request $request, int $pembayaran) {
    if (!$request->hasValidSignature()) {
        abort(403);
    }

    $data = (new App\Services\UktInvoiceService())->build($pembayaran);

    if (!$data) {
        abort(404);
    }

    return view('pdf.invoice', ['data' => $data]);
})->name('verify.invoice');

require __DIR__.'/auth.php';
