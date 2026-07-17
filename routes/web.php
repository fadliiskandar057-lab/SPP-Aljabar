<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SiswaPortalController;
use App\Http\Controllers\WaliKelasController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('landing'))->name('landing');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::post('/midtrans/webhook', MidtransWebhookController::class)->name('midtrans.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/invoice/{pembayaran}', [InvoiceController::class, 'show'])->name('invoice.show');
    Route::get('/invoice/{pembayaran}/download', [InvoiceController::class, 'download'])->name('invoice.download');

    Route::middleware('role:siswa')->prefix('siswa')->name('siswa.')->group(function () {
        Route::get('/tagihan', [SiswaPortalController::class, 'bills'])->name('tagihan');
        Route::get('/riwayat', [SiswaPortalController::class, 'history'])->name('riwayat');
        Route::get('/profil', [SiswaPortalController::class, 'profile'])->name('profil');
        Route::post('/tagihan/{tagihan}/tunai', [SiswaPortalController::class, 'cash'])->name('cash');
        Route::post('/tagihan/{tagihan}/midtrans', [SiswaPortalController::class, 'payOnline'])->name('midtrans');
        Route::post('/pembayaran/{pembayaran}/batalkan', [SiswaPortalController::class, 'cancelPendingPayment'])->name('payments.cancel');
        Route::post('/midtrans/{pembayaran}/finish', [SiswaPortalController::class, 'finishMidtrans'])->name('midtrans.finish');
    });

    Route::middleware('role:admin_tu')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengaturan-spp', [AdminController::class, 'settings'])->name('settings');
        Route::get('/siswa', [AdminController::class, 'siswa'])->name('siswa');
        Route::get('/siswa/search', [AdminController::class, 'searchSiswa'])->name('siswa.search');
        Route::get('/siswa/export', [AdminController::class, 'exportSiswa'])->name('siswa.export');
        Route::post('/siswa', [AdminController::class, 'storeSiswa'])->name('siswa.store');
        Route::post('/siswa/import', [AdminController::class, 'importSiswa'])->name('siswa.import');
        Route::patch('/siswa/bulk-update', [AdminController::class, 'bulkUpdateSiswa'])->name('siswa.bulk-update');
        Route::delete('/siswa/bulk-delete', [AdminController::class, 'bulkDestroySiswa'])->name('siswa.bulk-delete');
        Route::put('/siswa/{siswa}', [AdminController::class, 'updateSiswa'])->name('siswa.update');
        Route::delete('/siswa/{siswa}', [AdminController::class, 'destroySiswa'])->name('siswa.destroy');
        Route::get('/kelas', [AdminController::class, 'kelas'])->name('kelas');
        Route::post('/kelas', [AdminController::class, 'storeKelas'])->name('kelas.store');
        Route::get('/tahun-ajaran', [AdminController::class, 'tahunAjaran'])->name('tahun');
        Route::post('/tahun-ajaran', [AdminController::class, 'storeTahunAjaran'])->name('tahun.store');
        Route::put('/tahun-ajaran/{tahunAjaran}', [AdminController::class, 'updateTahunAjaran'])->name('tahun.update');
        Route::delete('/tahun-ajaran/{tahunAjaran}', [AdminController::class, 'destroyTahunAjaran'])->name('tahun.destroy');
        Route::get('/biaya-spp', [AdminController::class, 'biaya'])->name('biaya');
        Route::post('/biaya-spp', [AdminController::class, 'storeBiaya'])->name('biaya.store');
        Route::put('/biaya-spp/{biayaSpp}', [AdminController::class, 'updateBiaya'])->name('biaya.update');
        Route::delete('/biaya-spp/{biayaSpp}', [AdminController::class, 'destroyBiaya'])->name('biaya.destroy');
        Route::post('/jadwal-tagihan-otomatis', [AdminController::class, 'updateAutoBillSetting'])->name('auto-bill.update');
        Route::post('/gratis-diskon-tagihan', [AdminController::class, 'storeTagihanExemption'])->name('exemptions.store');
        Route::delete('/gratis-diskon-tagihan/{tagihanExemption}', [AdminController::class, 'destroyTagihanExemption'])->name('exemptions.destroy');
        Route::get('/tunggakan-siswa', [AdminController::class, 'studentArrears'])->name('arrears.students');
        Route::post('/tunggakan-siswa/set-terakhir-bayar', [AdminController::class, 'setLastPaidSiswa'])->name('arrears.set-last-paid');
        Route::post('/tunggakan-siswa/{siswa}/konfirmasi/{tagihan}', [AdminController::class, 'confirmArrearsThrough'])->name('arrears.confirm-through');
        Route::post('/tunggakan-siswa/{siswa}/midtrans/{tagihan}', [AdminController::class, 'confirmArrearsMidtrans'])->name('arrears.midtrans');
        Route::get('/tunggakan-siswa/midtrans/{pembayaran}/bayar', [AdminController::class, 'payArrearsMidtransPage'])->name('arrears.midtrans-pay-page');
        Route::post('/tunggakan-siswa/midtrans/{pembayaran}/finish', [AdminController::class, 'finishMidtransArrears'])->name('arrears.midtrans.finish');
        Route::post('/tunggakan-siswa/{siswa}/buka-ulang/{tagihan}', [AdminController::class, 'reopenArrearsBill'])->name('arrears.reopen-bill');
        Route::get('/antrian-tunai', [AdminController::class, 'cashQueue'])->name('cash.queue');
        Route::post('/antrian-tunai/{pembayaran}/konfirmasi', [AdminController::class, 'confirmCash'])->name('cash.confirm');
        Route::get('/transaksi', [AdminController::class, 'payments'])->name('payments');
        Route::get('/transaksi/search', [AdminController::class, 'searchPayments'])->name('payments.search');
        Route::post('/transaksi/manual', [AdminController::class, 'manualPayment'])->name('payments.manual');
        Route::post('/transaksi/{pembayaran}/batalkan', [AdminController::class, 'cancelPayment'])->name('payments.cancel');
        Route::get('/pencarian-sequential', [AdminController::class, 'sequential'])->name('sequential');
        Route::get('/pencarian-sequential/siswa-search', [AdminController::class, 'searchSequentialSiswa'])->name('sequential.siswa.search');
        Route::get('/laporan', [AdminController::class, 'laporan'])->name('laporan');
        Route::get('/laporan/pdf', [AdminController::class, 'laporanPdf'])->name('laporan.pdf');
        Route::get('/laporan/excel', [AdminController::class, 'laporanExcel'])->name('laporan.excel');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::patch('/users/{user}/password', [AdminController::class, 'changeUserPassword'])->name('users.password');
        Route::patch('/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('users.reset-password');
    });

    Route::middleware('role:wali_kelas')->prefix('wali-kelas')->name('wali.')->group(function () {
        Route::get('/dashboard', [WaliKelasController::class, 'dashboard'])->name('dashboard');
        Route::get('/siswa', [WaliKelasController::class, 'students'])->name('students');
        Route::get('/tagihan', [WaliKelasController::class, 'bills'])->name('bills');
        Route::get('/pembayaran', [WaliKelasController::class, 'payments'])->name('payments');
        Route::get('/tunggakan', [WaliKelasController::class, 'arrears'])->name('arrears');
        Route::get('/laporan', [WaliKelasController::class, 'report'])->name('report');
        Route::get('/kontak-orang-tua', [WaliKelasController::class, 'contacts'])->name('contacts');
    });

    Route::middleware('role:kepala_sekolah')->prefix('kepala-sekolah')->name('kepala.')->group(function () {
        Route::get('/laporan-bulanan', [AdminController::class, 'kepalaLaporanBulanan'])->name('laporan');
        Route::get('/laporan-kelas', [AdminController::class, 'kepalaLaporanKelas'])->name('laporan.kelas');
        Route::get('/grafik-pemasukan', [AdminController::class, 'kepalaGrafik'])->name('grafik');
        Route::get('/laporan/pdf', [AdminController::class, 'laporanPdf'])->name('laporan.pdf');
    });
});
