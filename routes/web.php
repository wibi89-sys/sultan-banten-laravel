<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BebasHarianController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstansiController;
use App\Http\Controllers\IntegrasiController;
use App\Http\Controllers\KroscekController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\OverstayingController;
use App\Http\Controllers\PenghuniController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SidangTppController;
use App\Http\Controllers\TelramController;
use App\Http\Controllers\UptController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes (Dashboard is public like in native PHP)
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Public Cron WhatsApp endpoint with throttle & secure key check
Route::get('/cron-wa', function (Request $request) {
    $secretKey = env('CRON_SECRET_KEY', 'ditjenpas_banten_2026_secret');
    $providedKey = (string) $request->query('key', '');
    if (! hash_equals($secretKey, $providedKey)) {
        return response()->json(['success' => false, 'message' => 'Invalid secret key'], 403);
    }
    $action = $request->query('action', 'run');
    $result = WhatsAppService::runCron($action);

    $logPath = storage_path('logs/cron-wa.log');
    if (! is_dir(dirname($logPath))) {
        @mkdir(dirname($logPath), 0755, true);
    }
    $logLine = sprintf(
        "[%s] HTTP WEB /CRON-WA - Action: %s - Result: %s\n",
        now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        $action,
        json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
    @file_put_contents($logPath, $logLine, FILE_APPEND);

    return response()->json($result);
})->middleware('throttle:30,1')->name('cron.wa');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Must be logged in)
Route::middleware('auth')->group(function () {
    // Kalkulator Telram PAS (Terbuka untuk semua user)
    Route::get('/telram', [TelramController::class, 'index'])->name('telram.index');
    Route::post('/telram/calculate', [TelramController::class, 'calculate'])->name('telram.calculate');

    // Kroscek Data
    Route::get('/kroscek', [KroscekController::class, 'index'])->name('kroscek.index');

    // Data Penghuni
    Route::get('/penghuni', [PenghuniController::class, 'index'])->name('penghuni.index');
    Route::get('/penghuni/check-exists', [PenghuniController::class, 'checkExists'])->name('penghuni.check-exists');
    Route::get('/penghuni/previous-data', [PenghuniController::class, 'getPreviousData'])->name('penghuni.previous-data');
    Route::post('/penghuni', [PenghuniController::class, 'store'])->name('penghuni.store');
    Route::put('/penghuni/{id}', [PenghuniController::class, 'update'])->name('penghuni.update');
    Route::delete('/penghuni/{id}', [PenghuniController::class, 'destroy'])->name('penghuni.destroy');

    // Data Overstaying
    Route::get('/overstaying', [OverstayingController::class, 'index'])->name('overstaying.index');
    Route::get('/overstaying/check-exists', [OverstayingController::class, 'checkExists'])->name('overstaying.check-exists');
    Route::get('/overstaying/previous-data', [OverstayingController::class, 'getPreviousData'])->name('overstaying.previous-data');
    Route::post('/overstaying', [OverstayingController::class, 'store'])->name('overstaying.store');
    Route::put('/overstaying/{id}', [OverstayingController::class, 'update'])->name('overstaying.update');
    Route::delete('/overstaying/{id}', [OverstayingController::class, 'destroy'])->name('overstaying.destroy');

    // Data Integrasi
    Route::get('/integrasi', [IntegrasiController::class, 'index'])->name('integrasi.index');
    Route::get('/integrasi/check-exists', [IntegrasiController::class, 'checkExists'])->name('integrasi.check-exists');
    Route::post('/integrasi', [IntegrasiController::class, 'store'])->name('integrasi.store');
    Route::put('/integrasi/{id}', [IntegrasiController::class, 'update'])->name('integrasi.update');
    Route::delete('/integrasi/{id}', [IntegrasiController::class, 'destroy'])->name('integrasi.destroy');

    // Bebas Harian Routes
    Route::get('/bebas-harian', [BebasHarianController::class, 'index'])->name('bebas-harian.index');
    Route::get('/bebas-harian/check-exists', [BebasHarianController::class, 'checkExists'])->name('bebas-harian.check-exists');
    Route::get('/bebas-harian/detail', [BebasHarianController::class, 'detail'])->name('bebas-harian.detail');
    Route::post('/bebas-harian', [BebasHarianController::class, 'store'])->name('bebas-harian.store');
    Route::put('/bebas-harian', [BebasHarianController::class, 'update'])->name('bebas-harian.update');
    Route::delete('/bebas-harian', [BebasHarianController::class, 'destroy'])->name('bebas-harian.destroy');

    // Sidang TPP (Sesuai SE Dirjenpas No: PAS-38.OT.02.02 Tahun 2026)
    Route::prefix('sidang-tpp')->name('sidang-tpp.')->group(function () {
        // Anak Menu 1: Jadwal & Undangan Daring
        Route::get('/', [SidangTppController::class, 'index'])->name('index');
        Route::get('/create', [SidangTppController::class, 'create'])->name('create');
        Route::post('/', [SidangTppController::class, 'store'])->name('store');
        Route::post('/preview-excel', [SidangTppController::class, 'previewExcel'])->name('preview-excel');
        Route::get('/download-template', [SidangTppController::class, 'downloadTemplate'])->name('download-template');
        Route::get('/{id}', [SidangTppController::class, 'show'])->whereNumber('id')->name('show');
        Route::delete('/{id}', [SidangTppController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}/download/{type}', [SidangTppController::class, 'downloadFile'])->whereNumber('id')->name('download-file');

        // CRUD Peserta Sidang
        Route::post('/{id}/peserta', [SidangTppController::class, 'pesertaStore'])->whereNumber('id')->name('peserta.store');
        Route::put('/{id}/peserta/{peserta_id}', [SidangTppController::class, 'pesertaUpdate'])->whereNumber('id')->name('peserta.update');
        Route::delete('/{id}/peserta/{peserta_id}', [SidangTppController::class, 'pesertaDestroy'])->whereNumber('id')->name('peserta.destroy');

        // Anak Menu 2: Hasil Sidang UPT
        Route::get('/hasil', [SidangTppController::class, 'hasilIndex'])->name('hasil');
        Route::post('/{id}/hasil', [SidangTppController::class, 'hasilUpdate'])->whereNumber('id')->name('hasil.update');

        // Anak Menu 3: Verifikasi & TTD Kanwil (Bintorwasdal)
        Route::get('/kanwil', [SidangTppController::class, 'kanwilIndex'])->name('kanwil');
        Route::post('/{id}/kanwil-verifikasi', [SidangTppController::class, 'kanwilVerifikasi'])->whereNumber('id')->name('kanwil.verifikasi');
        Route::post('/{id}/upload-laporan-kanwil', [SidangTppController::class, 'uploadLaporanKanwil'])->whereNumber('id')->name('kanwil.upload-laporan');
        Route::get('/{id}/download-laporan-kanwil', [SidangTppController::class, 'downloadLaporanKanwil'])->whereNumber('id')->name('download-laporan-kanwil');

        // Anak Menu 4: Rekap & Download Dokumen
        Route::get('/rekapitulasi', [SidangTppController::class, 'rekapitulasiIndex'])->name('rekapitulasi');
        Route::get('/export-excel', [SidangTppController::class, 'exportExcel'])->name('export-excel');
    });

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
    Route::get('/laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');

    // Data UPT
    Route::get('/upt', [UptController::class, 'index'])->name('upt.index');
    Route::post('/upt', [UptController::class, 'store'])->name('upt.store');
    Route::put('/upt/{id}', [UptController::class, 'update'])->name('upt.update');
    Route::delete('/upt/{id}', [UptController::class, 'destroy'])->name('upt.destroy');

    // Manajemen User
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    // Pengaturan Instansi
    Route::get('/instansi', [InstansiController::class, 'index'])->name('instansi.index');
    Route::match(['put', 'post'], '/instansi', [InstansiController::class, 'update'])->name('instansi.update');

    // Pengumuman
    Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::post('/pengumuman', [PengumumanController::class, 'store'])->name('pengumuman.store');
    Route::put('/pengumuman/{id}', [PengumumanController::class, 'update'])->name('pengumuman.update');
    Route::delete('/pengumuman/{id}', [PengumumanController::class, 'destroy'])->name('pengumuman.destroy');

    // ==========================================
    // Modul Otomatisasi WhatsApp Baru (Terstruktur)
    // ==========================================

    // 1. Gateway & Koneksi
    Route::get('/wa/gateway', [WhatsAppController::class, 'gatewayIndex'])->name('wa.gateway');
    Route::post('/wa/gateway', [WhatsAppController::class, 'gatewayUpdate'])->name('wa.gateway.update');
    Route::post('/wa/gateway/test', [WhatsAppController::class, 'gatewayTest'])->name('wa.gateway.test');

    // 2. Buku Kontak & Target Penerima
    Route::get('/wa/contacts', [WhatsAppController::class, 'contactsIndex'])->name('wa.contacts');
    Route::post('/wa/groups', [WhatsAppController::class, 'groupStore'])->name('wa.groups.store');
    Route::put('/wa/groups/{id}', [WhatsAppController::class, 'groupUpdate'])->name('wa.groups.update');
    Route::delete('/wa/groups/{id}', [WhatsAppController::class, 'groupDestroy'])->name('wa.groups.destroy');
    Route::post('/wa/groups/{id}/toggle', [WhatsAppController::class, 'groupToggle'])->name('wa.groups.toggle');
    Route::post('/wa/pimpinan', [WhatsAppController::class, 'pimpinanStore'])->name('wa.pimpinan.store');
    Route::put('/wa/pimpinan/{id}', [WhatsAppController::class, 'pimpinanUpdate'])->name('wa.pimpinan.update');
    Route::delete('/wa/pimpinan/{id}', [WhatsAppController::class, 'pimpinanDestroy'])->name('wa.pimpinan.destroy');

    // 3. Jadwal & Template Pesan Terpadu
    Route::get('/wa/schedules', [WhatsAppController::class, 'schedulesIndex'])->name('wa.schedules');
    Route::post('/wa/schedules/builtin', [WhatsAppController::class, 'updateBuiltinSettings'])->name('wa.schedules.builtin.update');
    Route::post('/wa/schedules', [WhatsAppController::class, 'scheduleStore'])->name('wa.schedules.store');
    Route::put('/wa/schedules/{id}', [WhatsAppController::class, 'scheduleUpdate'])->name('wa.schedules.update');
    Route::delete('/wa/schedules/{id}', [WhatsAppController::class, 'scheduleDestroy'])->name('wa.schedules.destroy');
    Route::post('/wa/schedules/{id}/toggle', [WhatsAppController::class, 'scheduleToggle'])->name('wa.schedules.toggle');
    Route::post('/wa/schedules/{id}/send-now', [WhatsAppController::class, 'scheduleSendNow'])->name('wa.schedules.sendNow');

    // 4. Riwayat & Monitoring Log Terpadu
    Route::get('/wa/logs', [WhatsAppController::class, 'logsIndex'])->name('wa.logs');

    // Backward-Compatible Aliases (URL Lama Tetap Berfungsi)
    Route::get('/wa-settings', [WhatsAppController::class, 'gatewayIndex'])->name('wa.settings');
    Route::get('/wa-settings-index', [WhatsAppController::class, 'gatewayIndex'])->name('wa.settings.index');
    Route::post('/wa-settings', [WhatsAppController::class, 'gatewayUpdate'])->name('wa.settings.update');
    Route::post('/wa-settings/test', [WhatsAppController::class, 'gatewayTest'])->name('wa.settings.test');

    Route::get('/wa-schedule', [WhatsAppController::class, 'schedulesIndex'])->name('wa.schedule');
    Route::get('/wa-schedule-index', [WhatsAppController::class, 'schedulesIndex'])->name('wa.schedule.index');
    Route::post('/wa-schedule', [WhatsAppController::class, 'scheduleStore'])->name('wa.schedule.store');
    Route::put('/wa-schedule/{id}', [WhatsAppController::class, 'scheduleUpdate'])->name('wa.schedule.update');
    Route::delete('/wa-schedule/{id}', [WhatsAppController::class, 'scheduleDestroy'])->name('wa.schedule.destroy');
    Route::post('/wa-schedule/{id}/toggle', [WhatsAppController::class, 'scheduleToggle'])->name('wa.schedule.toggle');
    Route::post('/wa-schedule/{id}/send-now', [WhatsAppController::class, 'scheduleSendNow'])->name('wa.schedule.sendNow');
    Route::post('/wa-schedule/{id}/send-now-alias', [WhatsAppController::class, 'scheduleSendNow'])->name('wa.schedule.send-now');

    Route::get('/wa-pimpinan', [WhatsAppController::class, 'contactsIndex'])->name('wa.pimpinan');
    Route::get('/wa-pimpinan-index', [WhatsAppController::class, 'contactsIndex'])->name('wa.pimpinan.index');

    // Activity Log & Leaderboard
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('/activity-log/ranking', [ActivityLogController::class, 'ranking'])->name('activity-log.ranking');
    Route::get('/activity-log/ranking/export-excel', [ActivityLogController::class, 'exportRankingExcel'])->name('activity-log.ranking.excel');

    // Profil
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
});
