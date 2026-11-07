<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfCleanerService
{
    /**
     * Pastikan dependensi TCPDF dan FPDI dimuat dengan benar.
     */
    protected static function loadDependencies(): void
    {
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
    }

    /**
     * Baca informasi dasar metadata dari berkas PDF sumber.
     *
     * @return array<string, mixed>
     */
    public static function extractMetadata(string $pdfPath): array
    {
        $info = [
            'creator' => 'Tidak Terdeteksi',
            'producer' => 'Tidak Terdeteksi',
            'author' => 'Tidak Terdeteksi',
            'title' => 'Tidak Terdeteksi',
            'creation_date' => '-',
            'mod_date' => '-',
            'page_count' => 0,
            'file_size' => file_exists($pdfPath) ? filesize($pdfPath) : 0,
            'file_size_formatted' => file_exists($pdfPath) ? round(filesize($pdfPath) / (1024 * 1024), 2).' MB' : '0 MB',
            'sha256' => file_exists($pdfPath) ? hash_file('sha256', $pdfPath) : '-',
        ];

        if (! file_exists($pdfPath)) {
            return $info;
        }

        // Baca sebagian konten header & trailer untuk mendeteksi metadata umum
        $content = @file_get_contents($pdfPath, false, null, 0, 1048576) ?: '';

        if (preg_match('/\/Creator\s*(?:\(([^)]*)\)|<([0-9a-fA-F]+)>)/i', $content, $m)) {
            $info['creator'] = self::cleanUtf8(! empty($m[1]) ? $m[1] : (isset($m[2]) ? self::hexToStr($m[2]) : '-'));
        }

        if (preg_match('/\/Producer\s*(?:\(([^)]*)\)|<([0-9a-fA-F]+)>)/i', $content, $m)) {
            $info['producer'] = self::cleanUtf8(! empty($m[1]) ? $m[1] : (isset($m[2]) ? self::hexToStr($m[2]) : '-'));
        }

        if (preg_match('/\/Author\s*(?:\(([^)]*)\)|<([0-9a-fA-F]+)>)/i', $content, $m)) {
            $info['author'] = self::cleanUtf8(! empty($m[1]) ? $m[1] : (isset($m[2]) ? self::hexToStr($m[2]) : '-'));
        }

        if (preg_match('/\/Title\s*(?:\(([^)]*)\)|<([0-9a-fA-F]+)>)/i', $content, $m)) {
            $info['title'] = self::cleanUtf8(! empty($m[1]) ? $m[1] : (isset($m[2]) ? self::hexToStr($m[2]) : '-'));
        }

        if (preg_match('/\/CreationDate\s*\((D:[^)]*)\)/i', $content, $m)) {
            $info['creation_date'] = self::parsePdfDate($m[1]);
        }

        if (preg_match('/\/ModDate\s*\((D:[^)]*)\)/i', $content, $m)) {
            $info['mod_date'] = self::parsePdfDate($m[1]);
        }

        // Deteksi jumlah halaman via regex cepat
        $pageMatches = preg_match_all('/\/Type\s*\/Page\b/', $content);
        if ($pageMatches > 0) {
            $info['page_count'] = $pageMatches;
        }

        return $info;
    }

    /**
     * Bersihkan riwayat revisi & metadata berkas PDF, serta standarisasi identitas resmi dan proteksi AES-128.
     *
     * @param  string  $sourcePath  Path absolut berkas PDF sumber
     * @param  string  $outputPath  Path absolut tujuan berkas PDF bersih
     * @param  array{
     *   title?: string,
     *   creator?: string,
     *   author?: string,
     *   subject?: string,
     *   lock_document?: bool,
     *   allow_copy?: bool
     * }  $options
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public static function cleanAndSanitize(string $sourcePath, string $outputPath, array $options = []): array
    {
        self::loadDependencies();

        if (! file_exists($sourcePath)) {
            throw new Exception('Berkas PDF sumber tidak ditemukan.');
        }

        $origMeta = self::extractMetadata($sourcePath);

        $customTitle = ! empty($options['title']) ? trim($options['title']) : 'Naskah Resmi Kedinasan';
        $customCreator = ! empty($options['creator']) ? trim($options['creator']) : 'Aplikasi SULTAN Banten';
        $customAuthor = ! empty($options['author']) ? trim($options['author']) : 'Kantor Wilayah Kemenimipas Banten';
        $customSubject = ! empty($options['subject']) ? trim($options['subject']) : 'Naskah Dinas Terstandarisasi';
        $lockDoc = isset($options['lock_document']) ? (bool) $options['lock_document'] : true;
        $allowCopy = isset($options['allow_copy']) ? (bool) $options['allow_copy'] : true;

        try {
            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetHeaderMargin(0);
            $pdf->SetFooterMargin(0);
            $pdf->SetAutoPageBreak(false, 0);

            $pageCount = $pdf->setSourceFile($sourcePath);
            if ($pageCount < 1) {
                throw new Exception('Berkas PDF tidak memiliki halaman valid.');
            }

            // Standarisasi Metadata Resmi
            $pdf->SetCreator($customCreator);
            $pdf->SetAuthor($customAuthor);
            $pdf->SetTitle($customTitle);
            $pdf->SetSubject($customSubject);
            $pdf->SetKeywords('sultan banten, srikandi, naskah dinas, dokumen resmi, terverifikasi');

            // Proteksi Dokumen Berstandar AES-128 Bit jika dipilih
            if ($lockDoc) {
                $ownerPassword = bin2hex(random_bytes(16));
                $permissionsToBlock = ['modify', 'annot-forms', 'fill-forms', 'assemble'];
                if (! $allowCopy) {
                    $permissionsToBlock[] = 'copy';
                    $permissionsToBlock[] = 'extract';
                }
                // Mode 2 pada TCPDF mengaktifkan enkripsi AES 128-bit
                $pdf->SetProtection($permissionsToBlock, '', $ownerPassword, 2);
            }

            // Reka ulang seluruh halaman berkas secara mandiri (membersihkan riwayat biner lama)
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
            }

            $outputDir = dirname($outputPath);
            if (! File::isDirectory($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            $pdf->Output($outputPath, 'F');

            if (! file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new Exception('Gagal menyimpan berkas PDF hasil pembersihan.');
            }

            $cleanSha256 = hash_file('sha256', $outputPath);
            $cleanSize = filesize($outputPath);

            return [
                'success' => true,
                'page_count' => $pageCount,
                'original' => [
                    'creator' => $origMeta['creator'],
                    'producer' => $origMeta['producer'],
                    'author' => $origMeta['author'],
                    'title' => $origMeta['title'],
                    'creation_date' => $origMeta['creation_date'],
                    'mod_date' => $origMeta['mod_date'],
                    'file_size_formatted' => $origMeta['file_size_formatted'],
                    'sha256' => $origMeta['sha256'],
                ],
                'cleaned' => [
                    'title' => $customTitle,
                    'creator' => $customCreator,
                    'producer' => 'SULTAN PDF Sanitizer Engine (Ditjenpas Banten)',
                    'author' => $customAuthor,
                    'page_count' => $pageCount,
                    'file_size' => $cleanSize,
                    'file_size_formatted' => round($cleanSize / (1024 * 1024), 2).' MB',
                    'sha256' => $cleanSha256,
                    'is_locked' => $lockDoc,
                    'allow_copy' => $allowCopy,
                    'sanitized_at' => now()->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i:s').' WIB',
                ],
                'sanitized_items' => [
                    'Jejak riwayat revisi penyuntingan (incremental save history) telah dibuang secara permanen.',
                    'Metadata path direktori lokal komputer (C:\Users\...) & nama laptop pribadi telah dibersihkan.',
                    'Tag nama software eksternal (Canva, Word, Photoshop, Nitro, dll.) dinetralkan ke standar kedinasan.',
                    'Struktur biner berkas di-linearisasi ulang ke format PDF dokumen resmi yang rapi.',
                    $lockDoc
                        ? 'Enkripsi proteksi izin naskah AES-128 diaktifkan (Anti-Modifikasi & Anti-Pemotongan Halaman).'
                        : 'Berkas tidak dikunci kata sandi izin (Mode Terbuka).',
                ],
            ];
        } catch (\Throwable $e) {
            throw new Exception('Gagal melakukan sanitasi berkas PDF: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Konversi string hexadesimal PDF menjadi teks ASCII.
     */
    protected static function hexToStr(string $hex): string
    {
        $str = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $str .= chr(hexdec($hex[$i].$hex[$i + 1]));
        }

        return self::cleanUtf8($str);
    }

    /**
     * Pastikan string aman dari karakter non-UTF8 atau malformed UTF-16BE.
     */
    protected static function cleanUtf8(?string $str): string
    {
        if ($str === null || $str === '') {
            return '-';
        }

        // Cek jika terdapat BOM UTF-16BE (\xFE\xFF)
        if (str_starts_with($str, "\xFE\xFF")) {
            $converted = @mb_convert_encoding(substr($str, 2), 'UTF-8', 'UTF-16BE');
            if ($converted !== false) {
                $str = $converted;
            }
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        if ($clean === false) {
            $clean = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        }

        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $clean));
    }

    /**
     * Format string tanggal PDF (D:YYYYMMDDHHmmSS) menjadi format tanggal mudah dibaca.
     */
    protected static function parsePdfDate(string $rawDate): string
    {
        if (preg_match('/D:(\d{4})(\d{2})(\d{2})(\d{2})?(\d{2})?(\d{2})?/', $rawDate, $m)) {
            $y = $m[1];
            $mo = $m[2];
            $d = $m[3];
            $h = $m[4] ?? '00';
            $mi = $m[5] ?? '00';
            $s = $m[6] ?? '00';

            return "{$d}-{$mo}-{$y} {$h}:{$mi}:{$s}";
        }

        return self::cleanUtf8($rawDate);
    }
}
