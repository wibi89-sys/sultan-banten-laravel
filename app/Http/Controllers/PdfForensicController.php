<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\PdfForensicService;
use App\Services\PdfSanitizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfForensicController extends Controller
{
    /**
     * Pastikan hanya pengguna yang memiliki hak akses forensik dokumen (atau Admin) yang dapat mengakses.
     */
    protected function checkAccess(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->canAccessForensikDokumen()) {
            abort(403, 'Akses ditolak. Fitur Forensik & Sanitasi Dokumen hanya dapat diakses oleh Administrator atau akun yang diberikan wewenang khusus.');
        }
    }

    /**
     * Halaman Utama Modul Forensik & Sanitasi Dokumen
     */
    public function index(Request $request): View
    {
        $this->checkAccess();

        $activeTab = $request->query('tab', 'inspeksi');
        if (! in_array($activeTab, ['inspeksi', 'sanitasi'])) {
            $activeTab = 'inspeksi';
        }

        return view('forensik_dokumen.index', compact('activeTab'));
    }

    /**
     * AJAX: Inspeksi Forensik Berkas PDF, Ekstraksi Jejak Digital & Uji Keaslian AI
     */
    public function inspect(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validationError = $this->validateUploadedPdf($request, 'file_pdf');
        if ($validationError !== null) {
            return response()->json([
                'success' => false,
                'message' => $validationError,
            ], 422);
        }

        $file = $request->file('file_pdf');
        $tempDir = storage_path('app/temp_forensic');
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $tempPath = $tempDir.'/forensic_'.uniqid().'_'.$file->getClientOriginalName();
        $file->move($tempDir, basename($tempPath));

        try {
            $result = PdfForensicService::inspect($tempPath);

            ActivityLog::log(
                'view',
                'forensik_dokumen',
                'Inspeksi forensik berkas PDF: '.$file->getClientOriginalName().' (Skor Keaslian: '.($result['ai_analysis']['authenticity_score'] ?? $result['heuristic_score']).'%)'
            );

            @unlink($tempPath);

            return response()->json([
                'success' => true,
                'message' => 'Inspeksi forensik dokumen berhasil diselesaikan.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            @unlink($tempPath);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menginspeksi berkas PDF: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Sanitasi Jejak Metadata & Penguncian Dokumen (Download File Bersih)
     */
    public function sanitize(Request $request): BinaryFileResponse|JsonResponse|RedirectResponse
    {
        $this->checkAccess();

        $validationError = $this->validateUploadedPdf($request, 'file_pdf');
        if ($validationError !== null) {
            return back()->with('error', $validationError);
        }

        $request->validate([
            'custom_title' => 'nullable|string|max:200',
            'custom_creator' => 'nullable|string|max:150',
            'lock_document' => 'nullable',
            'clean_metadata' => 'nullable',
        ]);

        $file = $request->file('file_pdf');
        $tempDir = storage_path('app/temp_sanitized');
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $sourcePath = $tempDir.'/src_'.uniqid().'.pdf';
        $outputPath = $tempDir.'/clean_'.uniqid().'.pdf';
        $file->move($tempDir, basename($sourcePath));

        try {
            $lockDoc = $request->boolean('lock_document', true);
            $cleanMeta = $request->boolean('clean_metadata', true);
            $customTitle = $request->input('custom_title') ?: 'Naskah Resmi Kedinasan';
            $customCreator = $request->input('custom_creator') ?: 'Kemenimipas Ditjenpas Banten';

            $result = PdfSanitizerService::sanitizeAndLock($sourcePath, $outputPath, [
                'clean_metadata' => $cleanMeta,
                'lock_document' => $lockDoc,
                'custom_title' => $customTitle,
                'custom_creator' => $customCreator,
            ]);

            @unlink($sourcePath);

            ActivityLog::log(
                'export',
                'forensik_dokumen',
                'Sanitasi & Penguncian berkas PDF: '.$file->getClientOriginalName().' (Terkunci AES-128: '.($lockDoc ? 'Ya' : 'Tidak').')'
            );

            $downloadName = 'TERKUNCI_BERSIH_'.$file->getClientOriginalName();

            return response()->download($outputPath, $downloadName, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($sourcePath);
            @unlink($outputPath);

            return back()->with('error', 'Gagal melakukan sanitasi berkas: '.$e->getMessage());
        }
    }

    /**
     * Validasi komprehensif berkas PDF yang diunggah
     */
    protected function validateUploadedPdf(Request $request, string $fieldName = 'file_pdf'): ?string
    {
        // 1. Cek jika muatan request melampaui post_max_size server (dimana PHP mengosongkan $_POST dan $_FILES)
        if (! $request->hasFile($fieldName) && empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
            $postMaxSize = ini_get('post_max_size');

            return "Ukuran berkas melebihi batas muatan server (post_max_size: {$postMaxSize}). Harap perkecil ukuran berkas Anda.";
        }

        // 2. Cek galat upload PHP bawaan jika ada
        if (isset($_FILES[$fieldName]['error']) && $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES[$fieldName]['error'];
            $uploadMax = ini_get('upload_max_filesize');

            return match ($errCode) {
                UPLOAD_ERR_INI_SIZE => "Ukuran berkas melebihi batas unggah PHP server (upload_max_filesize: {$uploadMax}). Harap perkecil berkas.",
                UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas melebihi batas form.',
                UPLOAD_ERR_PARTIAL => 'Proses unggah berkas terputus di tengah jalan. Silakan ulangi kembali.',
                UPLOAD_ERR_NO_FILE => 'Tidak ada berkas yang dipilih. Silakan pilih berkas PDF terlebih dahulu.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara server tidak ditemukan.',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis berkas ke penyimpanan server.',
                default => "Terjadi kesalahan saat mengunggah berkas (Kode: {$errCode}).",
            };
        }

        // 3. Cek keberadaan berkas
        if (! $request->hasFile($fieldName)) {
            return 'Silakan pilih berkas PDF yang ingin diuji / diunggah.';
        }

        $file = $request->file($fieldName);

        if (! $file || ! $file->isValid()) {
            return 'Berkas PDF yang diunggah tidak valid atau gagal ditransmisikan.';
        }

        // 4. Validasi ukuran (maksimal 30 MB)
        $maxSizeBytes = 30 * 1024 * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            $sizeMb = round($file->getSize() / (1024 * 1024), 2);

            return "Ukuran berkas ({$sizeMb} MB) melebihi batas maksimal 30 MB.";
        }

        // 5. Validasi ekstensi nama berkas
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'pdf') {
            return "Format berkas harus berformat PDF (.pdf). Format berkas Anda: .{$ext}";
        }

        // 6. Validasi Magic Header PDF (%PDF-)
        $realPath = $file->getRealPath();
        $header = '';
        if ($realPath && file_exists($realPath)) {
            $fp = @fopen($realPath, 'rb');
            if ($fp) {
                $header = fread($fp, 1024);
                fclose($fp);
            }
        }

        if (empty($header) || ! str_contains($header, '%PDF-')) {
            return 'Format berkas tidak valid. Berkas bukan merupakan dokumen PDF standar (header %PDF- tidak ditemukan).';
        }

        return null;
    }
}
