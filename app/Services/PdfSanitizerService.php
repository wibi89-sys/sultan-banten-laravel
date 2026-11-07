<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfSanitizerService
{
    /**
     * Bersihkan seluruh riwayat metadata & kunci berkas PDF dengan enkripsi AES-128.
     *
     * @param  string  $sourcePath  Path absolut berkas PDF sumber
     * @param  string  $outputPath  Path absolut berkas PDF target hasil sanitasi
     * @param  array{
     *   clean_metadata?: bool,
     *   lock_document?: bool,
     *   custom_title?: string,
     *   custom_creator?: string
     * }  $options
     * @return array<string, mixed>
     */
    public static function sanitizeAndLock(string $sourcePath, string $outputPath, array $options = []): array
    {
        if (! File::exists($sourcePath)) {
            throw new \InvalidArgumentException('Berkas PDF sumber tidak ditemukan.');
        }

        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        if (file_exists($tcpdfPath)) {
            require_once $tcpdfPath;
        }

        if (! class_exists('\setasign\Fpdi\Tcpdf\Fpdi')) {
            $fpdiAutoload = app_path('Libraries/fpdi/autoload.php');
            if (file_exists($fpdiAutoload)) {
                require_once $fpdiAutoload;
            }
        }

        try {
            $cleanMetadata = $options['clean_metadata'] ?? true;
            $lockDocument = $options['lock_document'] ?? true;
            $customTitle = $options['custom_title'] ?? 'Naskah Resmi Kedinasan';
            $customCreator = $options['custom_creator'] ?? 'Sistem Sultan Banten';

            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetHeaderMargin(0);
            $pdf->SetFooterMargin(0);
            $pdf->SetAutoPageBreak(false, 0);

            if ($cleanMetadata) {
                // Bersihkan seluruh identitas komputer / software lama
                $pdf->SetCreator($customCreator);
                $pdf->SetAuthor('Kantor Wilayah Banten');
                $pdf->SetTitle($customTitle);
                $pdf->SetSubject('Dokumen Terverifikasi Bersih');
                $pdf->SetKeywords('');
            }

            if ($lockDocument) {
                // Proteksi berkas dengan AES-128 bit (Mode 2)
                $ownerPass = bin2hex(random_bytes(16));
                $permissionsToBlock = ['modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble'];
                $pdf->SetProtection($permissionsToBlock, '', $ownerPass, 2);
            }

            $pageCount = $pdf->setSourceFile($sourcePath);
            if ($pageCount < 1) {
                throw new \RuntimeException('Tidak ada halaman yang dapat dibaca pada berkas PDF.');
            }

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
            }

            $dir = dirname($outputPath);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $pdf->Output($outputPath, 'F');

            if (! File::exists($outputPath) || filesize($outputPath) === 0) {
                throw new \RuntimeException('Gagal menulis berkas PDF hasil sanitasi.');
            }

            $originalSize = filesize($sourcePath);
            $cleanSize = filesize($outputPath);

            return [
                'success' => true,
                'source_file' => basename($sourcePath),
                'output_file' => basename($outputPath),
                'pages_count' => $pageCount,
                'original_size' => $originalSize,
                'clean_size' => $cleanSize,
                'is_locked' => $lockDocument,
                'metadata_cleaned' => $cleanMetadata,
                'sha256' => hash_file('sha256', $outputPath),
            ];
        } catch (\Throwable $e) {
            Log::error('PdfSanitizerService failed: '.$e->getMessage(), [
                'source' => $sourcePath,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
