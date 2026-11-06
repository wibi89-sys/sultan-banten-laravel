<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfStamperService
{
    /**
     * Bubuhkan QR Code otentifikasi Sultan Banten ke dalam berkas PDF hasil scan / upload.
     *
     * @param  string  $sourcePdfPath  Path absolut berkas PDF sumber
     * @param  string  $outputPdfPath  Path absolut berkas PDF hasil stamping
     * @param  string  $qrUrl  URL verifikasi publik
     * @param  string  $kodeVerifikasi  Kode unik dokumen (SB-...)
     * @param  array{
     *   percent_x?: float,    // Posisi X dalam persentase lebar halaman 0 - 100%
     *   percent_y?: float,    // Posisi Y dalam persentase tinggi halaman 0 - 100%
     *   target_page?: string, // 'last', 'first', 'all', atau nomor halaman
     *   qr_size?: float,      // Ukuran QR code dalam mm (default: 18)
     *   with_badge?: bool     // Sertakan bingkai dan teks seperti e-meterai (default: true)
     * }  $options
     */
    public static function stampQr(
        string $sourcePdfPath,
        string $outputPdfPath,
        string $qrUrl,
        string $kodeVerifikasi,
        array $options = []
    ): bool {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        if (file_exists($tcpdfPath)) {
            require_once $tcpdfPath;
        }

        try {
            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false);

            $pageCount = $pdf->setSourceFile($sourcePdfPath);
            if ($pageCount < 1) {
                return false;
            }

            $targetPage = $options['target_page'] ?? 'last';
            $percentX = isset($options['percent_x']) ? (float) $options['percent_x'] : 6.0;
            $percentY = isset($options['percent_y']) ? (float) $options['percent_y'] : 86.0;
            $qrSize = isset($options['qr_size']) ? (float) $options['qr_size'] : 17.0;
            $withBadge = ! isset($options['with_badge']) || (bool) $options['with_badge'];

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $pageWidth = $size['width'];
                $pageHeight = $size['height'];

                // Tentukan apakah halaman ini merupakan target pembubuhan
                $isTarget = false;
                if ($targetPage === 'last' && $pageNo === $pageCount) {
                    $isTarget = true;
                } elseif ($targetPage === 'first' && $pageNo === 1) {
                    $isTarget = true;
                } elseif ($targetPage === 'all') {
                    $isTarget = true;
                } elseif (is_numeric($targetPage) && (int) $targetPage === $pageNo) {
                    $isTarget = true;
                }

                if ($isTarget) {
                    // Hitung koordinat mm dari persentase
                    $posX = ($percentX / 100.0) * $pageWidth;
                    $posY = ($percentY / 100.0) * $pageHeight;

                    // Proteksi agar tidak keluar batas halaman
                    $boxWidth = $withBadge ? ($qrSize + 48) : $qrSize;
                    $boxHeight = $withBadge ? ($qrSize + 2) : $qrSize;

                    if ($posX + $boxWidth > $pageWidth - 5) {
                        $posX = $pageWidth - $boxWidth - 5;
                    }
                    if ($posX < 5) {
                        $posX = 5;
                    }
                    if ($posY + $boxHeight > $pageHeight - 5) {
                        $posY = $pageHeight - $boxHeight - 5;
                    }
                    if ($posY < 5) {
                        $posY = 5;
                    }

                    if ($withBadge) {
                        // Gambar Bingkai Elegan (Mirip e-Meterai / Cap Otentifikasi Resmi)
                        $pdf->SetDrawColor(15, 71, 97); // Warna Cyan Gelap Sultan Banten
                        $pdf->SetFillColor(255, 255, 255);
                        $pdf->SetLineWidth(0.3);
                        $pdf->RoundedRect($posX - 1, $posY - 1, $boxWidth, $boxHeight, 1.5, '1111', 'DF');

                        // Garis aksen kiri
                        $pdf->SetFillColor(15, 71, 97);
                        $pdf->Rect($posX - 1, $posY - 1, 1.5, $boxHeight, 'F');
                    }

                    // Gambar QR Code 2D Barcode
                    $qrStyle = [
                        'border' => false,
                        'padding' => 0,
                        'fgcolor' => [0, 0, 0],
                        'bgcolor' => [255, 255, 255],
                    ];
                    $pdf->write2DBarcode($qrUrl, 'QRCODE,L', $posX + ($withBadge ? 1 : 0), $posY, $qrSize, $qrSize, $qrStyle, 'N');

                    if ($withBadge) {
                        // Teks Keterangan di samping QR Code
                        $textX = $posX + $qrSize + 3;
                        $textWidth = 44;

                        $pdf->SetXY($textX, $posY + 0.8);
                        $pdf->SetFont('helvetica', 'B', 6.2);
                        $pdf->SetTextColor(15, 71, 97);
                        $pdf->Cell($textWidth, 3, 'TEROTENTIFIKASI RESMI', 0, 1, 'L');

                        $pdf->SetX($textX);
                        $pdf->SetFont('helvetica', '', 5.2);
                        $pdf->SetTextColor(70, 70, 70);
                        $pdf->Cell($textWidth, 2.7, 'SULTAN DITJENPAS BANTEN', 0, 1, 'L');

                        $pdf->SetX($textX);
                        $pdf->SetFont('helvetica', 'B', 5.2);
                        $pdf->SetTextColor(20, 20, 20);
                        $pdf->Cell($textWidth, 2.7, 'Kode: '.$kodeVerifikasi, 0, 1, 'L');

                        $pdf->SetX($textX);
                        $pdf->SetFont('helvetica', 'I', 4.8);
                        $pdf->SetTextColor(110, 110, 110);
                        $pdf->Cell($textWidth, 2.5, 'Pindai untuk validasi naskah', 0, 1, 'L');
                    }
                }
            }

            $pdf->Output($outputPdfPath, 'F');

            return file_exists($outputPdfPath) && filesize($outputPdfPath) > 0;
        } catch (\Throwable $e) {
            Log::error('PdfStamperService::stampQr failed: '.$e->getMessage(), [
                'source' => $sourcePdfPath,
                'error' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
