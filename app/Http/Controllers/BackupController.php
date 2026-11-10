<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\DatabaseBackupService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $backupService
    ) {}

    /**
     * Tampilkan halaman utama Backup & Ekspor Database.
     */
    public function index(): View
    {
        $this->authorizeAdmin();

        $stats = $this->backupService->getDatabaseStats();
        $files = $this->backupService->getBackupFiles();

        return view('backup.index', compact('stats', 'files'));
    }

    /**
     * Unduh database langsung ke browser (1-Klik Unduh).
     */
    public function download(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeAdmin();

        $compress = $request->input('format', 'gz') === 'gz';

        try {
            $backup = $this->backupService->generateBackupFile(null, $compress);

            ActivityLog::log(
                'export',
                'database_backup',
                "Mengunduh cadangan database langsung ({$backup['filename']} - {$backup['size_formatted']})"
            );

            return response()->download($backup['path'], $backup['filename']);
        } catch (Exception $e) {
            return redirect()->route('backup.index')->with('error', 'Gagal membuat cadangan database: '.$e->getMessage());
        }
    }

    /**
     * Buat dan simpan file cadangan baru di server.
     */
    public function generate(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $compress = $request->input('format', 'gz') === 'gz';

        try {
            $backup = $this->backupService->generateBackupFile(null, $compress);

            ActivityLog::log(
                'tambah',
                'database_backup',
                "Membuat arsip cadangan database di server ({$backup['filename']} - {$backup['size_formatted']})"
            );

            return redirect()->route('backup.index')->with('success', "Cadangan database berhasil dibuat ({$backup['filename']} - {$backup['size_formatted']}) dalam {$backup['time_taken_seconds']} detik.");
        } catch (Exception $e) {
            return redirect()->route('backup.index')->with('error', 'Gagal membuat cadangan database: '.$e->getMessage());
        }
    }

    /**
     * Unduh file cadangan yang telah tersimpan di server.
     */
    public function downloadFile(string $filename): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeAdmin();

        $path = $this->backupService->getValidBackupPath($filename);

        if (! $path) {
            abort(404, 'File cadangan tidak ditemukan di server.');
        }

        ActivityLog::log(
            'export',
            'database_backup',
            "Mengunduh file arsip database server: {$filename}"
        );

        return response()->download($path, $filename);
    }

    /**
     * Hapus file cadangan tertentu dari server.
     */
    public function destroyFile(string $filename): RedirectResponse
    {
        $this->authorizeAdmin();

        $deleted = $this->backupService->deleteBackupFile($filename);

        if ($deleted) {
            ActivityLog::log(
                'hapus',
                'database_backup',
                "Menghapus file cadangan database: {$filename}"
            );

            return redirect()->route('backup.index')->with('success', "File cadangan {$filename} berhasil dihapus dari server.");
        }

        return redirect()->route('backup.index')->with('error', 'Gagal menghapus file cadangan atau nama file tidak valid.');
    }

    /**
     * Pastikan hanya akun Administrator yang berhak mengakses fungsi backup.
     */
    protected function authorizeAdmin(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Akses Terbatas: Hanya Administrator Sistem yang memiliki wewenang untuk mengelola cadangan database.');
        }
    }
}
