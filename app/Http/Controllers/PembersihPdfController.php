<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\PdfCleanerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PembersihPdfController extends Controller
{
    /**
     * Tampilkan halaman utama Pembersih PDF.
     */
    public function index(): View
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAccess('utilitas')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses modul Utilitas.');
        }

        $defaultAuthor = $user && $user->upt ? $user->upt->nama_upt : 'Kantor Wilayah Kemenimipas Banten';
        $defaultCreator = 'Aplikasi SULTAN Banten';

        return view('pembersih_pdf.index', compact('defaultAuthor', 'defaultCreator'));
    }

    /**
     * Bersihkan riwayat dan metadata berkas PDF naskah kedinasan.
     */
    public function clean(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAccess('utilitas')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses modul Utilitas.',
            ], 403);
        }

        // 1. Validasi keberadaan dan status berkas
        $validationError = $this->validateUploadedPdf($request, 'file_pdf');
        if ($validationError !== null) {
            return response()->json([
                'success' => false,
                'message' => $validationError,
            ], 422);
        }

        $file = $request->file('file_pdf');
        $cleanDir = storage_path('app/temp_cleaned');
        if (! File::isDirectory($cleanDir)) {
            File::makeDirectory($cleanDir, 0755, true);
        }

        // Hapus berkas sementara lama (> 2 jam)
        $this->cleanupOldTempFiles($cleanDir);

        $tempSource = $cleanDir.'/src_'.uniqid().'.pdf';
        $file->move($cleanDir, basename($tempSource));

        $token = bin2hex(random_bytes(16));
        $outputPath = $cleanDir.'/cleaned_'.$token.'.pdf';

        try {
            $options = [
                'title' => $request->input('custom_title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'creator' => $request->input('custom_creator') ?: 'Aplikasi SULTAN Banten',
                'author' => $request->input('custom_author') ?: 'Kantor Wilayah Kemenimipas Banten',
                'subject' => $request->input('custom_subject') ?: 'Naskah Dinas Terstandarisasi',
                'lock_document' => $request->boolean('lock_document', true),
                'allow_copy' => $request->boolean('allow_copy', true),
            ];

            $result = PdfCleanerService::cleanAndSanitize($tempSource, $outputPath, $options);

            // Bersihkan file sumber upload
            @unlink($tempSource);

            // Catat ke Activity Log
            ActivityLog::log(
                'export',
                'pembersih_pdf',
                'Sanitasi & standarisasi naskah PDF: '.$file->getClientOriginalName().' (SHA-256: '.$result['cleaned']['sha256'].')'
            );

            // Simpan nama berkas asli untuk pengunduhan
            $cleanFilename = 'BERSIH_'.preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());

            return response()->json([
                'success' => true,
                'message' => 'Berkas PDF berhasil dibersihkan dan distandarisasi.',
                'data' => $result,
                'download_url' => route('pembersih-pdf.download', [
                    'token' => $token,
                    'name' => $cleanFilename,
                ]),
                'download_name' => $cleanFilename,
            ]);
        } catch (\Throwable $e) {
            @unlink($tempSource);
            @unlink($outputPath);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pembersihan berkas: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unduh berkas PDF yang telah dibersihkan.
     */
    public function download(Request $request, string $token): BinaryFileResponse|JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAccess('utilitas')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses modul Utilitas.');
        }

        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            abort(404, 'Token berkas tidak valid.');
        }

        $cleanPath = storage_path('app/temp_cleaned/cleaned_'.$token.'.pdf');
        if (! file_exists($cleanPath)) {
            abort(404, 'Berkas hasil pembersihan telah kedaluwarsa atau tidak ditemukan.');
        }

        $fileName = $request->query('name') ?: 'naskah_bersih_sultan.pdf';

        return response()->download($cleanPath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Validasi berkas PDF unggahan secara komprehensif.
     */
    protected function validateUploadedPdf(Request $request, string $fieldName = 'file_pdf'): ?string
    {
        // Cek jika muatan request melampaui post_max_size server
        if (! $request->hasFile($fieldName) && empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
            $postMaxSize = ini_get('post_max_size');

            return "Ukuran berkas melebihi batas muatan server (post_max_size: {$postMaxSize}). Harap perkecil ukuran berkas Anda.";
        }

        // Cek galat upload PHP
        if (isset($_FILES[$fieldName]['error']) && $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES[$fieldName]['error'];
            $uploadMax = ini_get('upload_max_filesize');

            return match ($errCode) {
                UPLOAD_ERR_INI_SIZE => "Ukuran berkas melebihi batas unggah PHP server (upload_max_filesize: {$uploadMax}). Harap perkecil berkas.",
                UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas melebihi batas form.',
                UPLOAD_ERR_PARTIAL => 'Proses unggah berkas terputus di tengah jalan. Silakan ulangi kembali.',
                UPLOAD_ERR_NO_FILE => 'Tidak ada berkas yang dipilih. Silakan pilih berkas PDF terlebih dahulu.',
                default => "Terjadi kesalahan saat mengunggah berkas (Kode: {$errCode}).",
            };
        }

        if (! $request->hasFile($fieldName)) {
            return 'Silakan pilih berkas PDF naskah kedinasan yang ingin dibersihkan.';
        }

        $file = $request->file($fieldName);
        if (! $file || ! $file->isValid()) {
            return 'Berkas PDF yang diunggah tidak valid atau gagal ditransmisikan.';
        }

        // Maksimal 30 MB
        if ($file->getSize() > 30 * 1024 * 1024) {
            $sizeMb = round($file->getSize() / (1024 * 1024), 2);

            return "Ukuran berkas ({$sizeMb} MB) melebihi batas maksimal 30 MB.";
        }

        // Ekstensi harus .pdf
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'pdf') {
            return "Format berkas harus berekstensi .pdf! Format berkas Anda: .{$ext}";
        }

        // Cek Magic Header %PDF-
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

    /**
     * Hapus berkas sementara yang lebih lama dari 2 jam.
     */
    protected function cleanupOldTempFiles(string $dir): void
    {
        try {
            $files = File::files($dir);
            $now = time();
            foreach ($files as $file) {
                if ($now - $file->getMTime() > 7200) {
                    @unlink($file->getPathname());
                }
            }
        } catch (\Throwable $e) {
            // Ignore cleanup exceptions
        }
    }
}
