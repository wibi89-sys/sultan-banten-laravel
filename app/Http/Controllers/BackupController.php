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
     * Unggah dan pulihkan (Import / Restore) database langsung dari file .sql atau .sql.gz.
     */
    public function import(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'backup_file' => ['required', 'file', 'max:102400'], // Maks 100 MB
        ], [
            'backup_file.required' => 'Silakan pilih file cadangan (.sql atau .sql.gz) terlebih dahulu.',
            'backup_file.file' => 'File yang diunggah tidak valid.',
            'backup_file.max' => 'Ukuran file cadangan maksimal 100 MB.',
        ]);

        $uploadedFile = $request->file('backup_file');
        $originalName = $uploadedFile->getClientOriginalName();

        if (! preg_match('/\.(sql|sql\.gz)$/i', $originalName)) {
            return redirect()->route('backup.index')->with('error', 'Format file tidak didukung. Gunakan file berekstensi .sql atau .sql.gz.');
        }

        $tempFilename = 'import_temp_'.time().'_'.preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);
        $tempPath = $this->backupService->getBackupDirectory().DIRECTORY_SEPARATOR.$tempFilename;

        try {
            $uploadedFile->move($this->backupService->getBackupDirectory(), $tempFilename);

            $result = $this->backupService->restoreFromFile($tempPath);

            try {
                ActivityLog::log(
                    'import',
                    'database_backup',
                    "Memulihkan database dari unggahan file {$originalName} ({$result['tables_restored']} tabel, {$result['statements_executed']} query)"
                );
            } catch (Exception) {
                // Abaikan jika pencatatan log terkendala skema
            }

            return redirect()->route('backup.index')->with(
                'success',
                "Database berhasil dipulihkan dari file {$originalName} ({$result['tables_restored']} tabel & {$result['statements_executed']} perintah SQL dieksekusi dalam {$result['time_taken_seconds']} detik)."
            );
        } catch (Exception $e) {
            return redirect()->route('backup.index')->with('error', 'Gagal memulihkan database: '.$e->getMessage());
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Pulihkan (Restore) database dari file cadangan yang sudah tersimpan di server.
     */
    public function restoreFile(string $filename): RedirectResponse
    {
        $this->authorizeAdmin();

        $path = $this->backupService->getValidBackupPath($filename);

        if (! $path) {
            return redirect()->route('backup.index')->with('error', 'File cadangan tidak ditemukan di server.');
        }

        try {
            $result = $this->backupService->restoreFromFile($path);

            try {
                ActivityLog::log(
                    'import',
                    'database_backup',
                    "Memulihkan database dari arsip server: {$filename} ({$result['tables_restored']} tabel)"
                );
            } catch (Exception) {
                // Abaikan jika pencatatan log terkendala skema
            }

            return redirect()->route('backup.index')->with(
                'success',
                "Database berhasil dipulihkan dari arsip {$filename} ({$result['tables_restored']} tabel dipulihkan dalam {$result['time_taken_seconds']} detik)."
            );
        } catch (Exception $e) {
            return redirect()->route('backup.index')->with('error', 'Gagal memulihkan database: '.$e->getMessage());
        }
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
