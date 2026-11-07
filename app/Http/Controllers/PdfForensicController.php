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

        $request->validate([
            'file_pdf' => 'required|file|mimes:pdf|max:30720', // Maks. 30 MB
        ], [
            'file_pdf.required' => 'Silakan pilih berkas PDF yang ingin diinspeksi.',
            'file_pdf.mimes' => 'Berkas harus dalam format PDF.',
            'file_pdf.max' => 'Ukuran berkas PDF maksimal 30 MB.',
        ]);

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

        $request->validate([
            'file_pdf' => 'required|file|mimes:pdf|max:30720',
            'custom_title' => 'nullable|string|max:200',
            'custom_creator' => 'nullable|string|max:150',
            'lock_document' => 'nullable',
            'clean_metadata' => 'nullable',
        ], [
            'file_pdf.required' => 'Silakan pilih berkas PDF yang ingin disanitasi.',
            'file_pdf.mimes' => 'Berkas harus dalam format PDF.',
            'file_pdf.max' => 'Ukuran berkas PDF maksimal 30 MB.',
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
}
