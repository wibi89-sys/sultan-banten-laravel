<?php

namespace App\Services;

use App\Models\TppNaskahKanwil;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class PdfForensicService
{
    /**
     * Daftar software grafis/editor yang tidak lazim sebagai penghasil naskah kedinasan resmi.
     * Jika terdeteksi pada metadata, dokumen patut dicurigai telah diedit/direkayasa.
     */
    protected static array $suspiciousSoftware = [
        'canva' => 'Canva Graphic Design (Aplikasi Desain Grafis Daring)',
        'photoshop' => 'Adobe Photoshop (Software Manipulasi Foto/Gambar)',
        'illustrator' => 'Adobe Illustrator (Software Grafis Vektor)',
        'coreldraw' => 'CorelDRAW (Software Grafis & Ilustrasi)',
        'gimp' => 'GIMP (Image Editor)',
        'ilovepdf' => 'iLovePDF (Layanan Modifikasi/Penyunting PDF Online)',
        'sejda' => 'Sejda PDF Editor (Editor PDF Daring)',
        'smallpdf' => 'Smallpdf (Layanan Modifikasi PDF Online)',
        'pdf24' => 'PDF24 Tools (Editor PDF Online)',
        'nitro' => 'Nitro Pro PDF Editor',
        'foxit' => 'Foxit PDF Editor',
        'inkscape' => 'Inkscape Vector Editor',
        'paint.net' => 'Paint.NET Image Editor',
    ];

    /**
     * Inspeksi forensik mendalam berkas PDF:
     * - Ekstraksi Metadata & XMP History (software asal, author, tanggal buat & edit)
     * - Analisis struktur objek (jumlah revisi inkremental, enkripsi, font)
     * - Deteksi Barcode / QR Code Sultan Banten & validasi silang database
     * - Analisis AI Gemini Forensik
     *
     * @param  string  $filePath  Path absolut berkas PDF
     * @return array<string, mixed>
     */
    public static function inspect(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new \InvalidArgumentException('Berkas PDF tidak ditemukan pada path yang diberikan.');
        }

        $raw = file_get_contents($filePath);
        if ($raw === false || strlen($raw) < 10) {
            throw new \InvalidArgumentException('Berkas kosong atau tidak dapat dibaca.');
        }

        $fileSize = strlen($raw);
        $fileName = basename($filePath);
        $sha256 = hash('sha256', $raw);

        // 1. Validasi Header & Versi PDF
        preg_match('/%PDF-(\d+\.\d+)/', substr($raw, 0, 1024), $versionMatch);
        $pdfVersion = $versionMatch[1] ?? 'Tidak diketahui (Non-standar)';

        // 2. Analisis Struktur Inkremental (Jumlah revisi %%EOF)
        $eofCount = substr_count($raw, '%%EOF');
        $isLinearized = (strpos($raw, '/Linearized') !== false);
        $isEncrypted = (strpos($raw, '/Encrypt') !== false);

        // 3. Ekstraksi Info Dictionary
        $infoData = self::extractInfoDictionary($raw);

        // 4. Ekstraksi XMP Metadata Stream (XML)
        $xmpData = self::extractXmpMetadata($raw);

        // Gabungkan metadata utama dengan fallback XMP
        $creator = $infoData['Creator'] ?? $xmpData['creator_tool'] ?? null;
        $producer = $infoData['Producer'] ?? $xmpData['producer'] ?? null;
        $author = $infoData['Author'] ?? null;
        $title = $infoData['Title'] ?? null;
        $subject = $infoData['Subject'] ?? null;
        $keywords = $infoData['Keywords'] ?? null;
        $creationDateRaw = $infoData['CreationDate'] ?? $xmpData['create_date'] ?? null;
        $modDateRaw = $infoData['ModDate'] ?? $xmpData['modify_date'] ?? null;

        $creationDate = self::parsePdfDate($creationDateRaw);
        $modDate = self::parsePdfDate($modDateRaw);

        // 5. Ekstraksi Font yang Tertanam
        preg_match_all('/\/BaseFont\s*\/([a-zA-Z0-9_\-+]+)/', $raw, $fontMatches);
        $fontsUsed = array_values(array_unique($fontMatches[1] ?? []));

        // 6. Deteksi Barcode & Kode Verifikasi Sultan Banten (SB-...)
        // Dekompresi stream objek agar teks terkompresi FlateDecode dapat dideteksi secara komprehensif
        $searchableText = $raw;
        if (preg_match_all('/stream[\r\n]+([\s\S]*?)[\r\n]+endstream/s', $raw, $streamMatches)) {
            foreach ($streamMatches[1] as $streamChunk) {
                $decompressed = @gzuncompress($streamChunk);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($streamChunk);
                }
                if ($decompressed !== false) {
                    $searchableText .= ' '.$decompressed;
                }
            }
        }

        preg_match_all('/SB-[A-Z0-9]{8,16}/', $searchableText, $sbMatches);
        $detectedCodes = array_values(array_unique($sbMatches[0] ?? []));

        $qrVerificationResults = [];
        $hasInvalidQr = false;

        foreach ($detectedCodes as $code) {
            $naskah = TppNaskahKanwil::where('kode_verifikasi', $code)
                ->with(['sidangTpp.upt'])
                ->first();

            if ($naskah) {
                $qrVerificationResults[] = [
                    'kode' => $code,
                    'status' => 'valid_terdaftar',
                    'naskah_id' => $naskah->id,
                    'jenis_dokumen' => $naskah->jenis_dokumen,
                    'nomor_dokumen' => $naskah->nomor_dokumen ?: '(Belum bernomor)',
                    'satker_upt' => $naskah->sidangTpp?->upt?->nama_upt ?? 'Kantor Wilayah Banten',
                    'tanggal_dokumen' => $naskah->tanggal_dokumen ? $naskah->tanggal_dokumen->format('d-m-Y') : '-',
                    'penandatangan' => $naskah->penandatangan_nama ?: 'Pimpinan Kanwil',
                    'signed_at' => $naskah->signed_at ? $naskah->signed_at->format('d-m-Y H:i:s') : '-',
                    'is_locked' => $naskah->isLocked(),
                    'pesan' => 'Kode terdaftar resmi di database Sultan Banten. Silakan bandingkan rincian nomor surat, tanggal, dan nama satker pada kertas fisik dengan data resmi ini.',
                ];
            } else {
                $hasInvalidQr = true;
                $qrVerificationResults[] = [
                    'kode' => $code,
                    'status' => 'tidak_terdaftar',
                    'pesan' => 'KODE TIDAK DITEMUKAN PADA DATABASE RESMI SULTAN BANTEN! Dokumen ini terindikasi kuat menggunakan kode QR palsu atau rekayasa mandiri.',
                ];
            }
        }

        // 7. Analisis Indikator Manipulasi (Heuristic Rules)
        $indicators = [];
        $heuristicScore = 100; // Asumsi awal 100% otentik

        // Cek software mencurigakan
        $combinedSoftware = strtolower(($creator ?? '').' '.($producer ?? '').' '.implode(' ', $xmpData['software_agents'] ?? []));
        $detectedSuspicious = [];
        foreach (self::$suspiciousSoftware as $keyword => $desc) {
            if (str_contains($combinedSoftware, $keyword)) {
                $detectedSuspicious[] = $desc;
            }
        }

        if (! empty($detectedSuspicious)) {
            $heuristicScore -= 45;
            $indicators[] = [
                'kategori' => 'danger',
                'judul' => 'Software Pembuat Tidak Lazim untuk Surat Dinas',
                'deskripsi' => 'Terdeteksi jejak software editor/grafis: '.implode(', ', $detectedSuspicious).'. Dokumen resmi kedinasan umumnya diproduksi oleh Microsoft Word, scanner resmi, atau sistem penandatangan kedinasan.',
            ];
        }

        // Cek anomali tanggal
        if ($creationDate && $modDate) {
            try {
                $cCarbon = Carbon::parse($creationDate);
                $mCarbon = Carbon::parse($modDate);
                $diffHours = $cCarbon->diffInHours($mCarbon);

                if ($diffHours > 24) {
                    $heuristicScore -= 20;
                    $indicators[] = [
                        'kategori' => 'warning',
                        'judul' => 'Selisih Waktu Modifikasi Cukup Signifikan',
                        'deskripsi' => "Berkas dibuat pada {$creationDate} dan dimodifikasi ulang pada {$modDate} (selisih {$diffHours} jam). Hal ini mengindikasikan berkas sempat diedit kembali setelah pertama kali dibuat.",
                    ];
                }
            } catch (\Throwable) {
                // Abaikan kesalahan parsing tanggal
            }
        }

        // Cek inkremental revisi
        if ($eofCount > 1) {
            $heuristicScore -= 15;
            $indicators[] = [
                'kategori' => 'info',
                'judul' => 'Struktur Inkremental Terdeteksi (Disimpan Berulang)',
                'deskripsi' => "Ditemukan {$eofCount} penanda revisi (%%EOF). Dokumen pernah dibuka dan disimpan ulang beberapa kali, yang memungkinkan adanya penambahan/penimpaan objek.",
            ];
        }

        // Cek QR Code tidak terdaftar
        if ($hasInvalidQr) {
            $heuristicScore -= 60;
            $indicators[] = [
                'kategori' => 'danger',
                'judul' => 'Cap QR Code Tidak Terdaftar di Database Resmi',
                'deskripsi' => 'Kode verifikasi yang tertanam pada dokumen tidak terdata pada server Sultan Banten. Sangat dimungkinkan ini adalah dokumen palsu atau QR tiruan.',
            ];
        }

        $heuristicScore = max(5, min(100, $heuristicScore));

        // 8. Ekstraksi Teks Sampel untuk Analisis AI
        $sampleText = self::extractTextSample($raw);

        // 9. Panggil Analisis AI Gemini
        $aiAnalysis = GeminiAiService::analyzeForensicPdf([
            'file_name' => $fileName,
            'file_size' => self::formatBytes($fileSize),
            'pdf_version' => $pdfVersion,
            'creator' => $creator,
            'producer' => $producer,
            'creation_date' => $creationDate,
            'mod_date' => $modDate,
            'revisions_count' => $eofCount,
            'detected_suspicious_software' => $detectedSuspicious,
            'detected_qr_codes' => $detectedCodes,
            'qr_verification_results' => $qrVerificationResults,
            'fonts_used' => array_slice($fontsUsed, 0, 10),
            'sample_text' => substr($sampleText, 0, 3000),
        ]);

        return [
            'file_info' => [
                'name' => $fileName,
                'size' => $fileSize,
                'size_formatted' => self::formatBytes($fileSize),
                'sha256' => $sha256,
                'version' => $pdfVersion,
                'revisions_count' => $eofCount,
                'is_linearized' => $isLinearized,
                'is_encrypted' => $isEncrypted,
            ],
            'metadata' => [
                'creator' => $creator ?: '(Tidak tertera / Telah dibersihkan)',
                'producer' => $producer ?: '(Tidak tertera / Telah dibersihkan)',
                'author' => $author ?: '(Kosong)',
                'title' => $title ?: '(Kosong)',
                'subject' => $subject ?: '(Kosong)',
                'keywords' => $keywords ?: '(Kosong)',
                'creation_date' => $creationDate ?: '(Tidak tertera)',
                'mod_date' => $modDate ?: '(Tidak tertera)',
                'raw_creation' => $creationDateRaw,
                'raw_mod' => $modDateRaw,
            ],
            'xmp' => $xmpData,
            'fonts' => $fontsUsed,
            'qr_analysis' => [
                'detected_codes' => $detectedCodes,
                'results' => $qrVerificationResults,
                'has_invalid' => $hasInvalidQr,
            ],
            'indicators' => $indicators,
            'heuristic_score' => $heuristicScore,
            'ai_analysis' => $aiAnalysis,
        ];
    }

    /**
     * Ekstraksi Info Dictionary dari sintaks PDF mentah.
     *
     * @return array<string, string>
     */
    protected static function extractInfoDictionary(string $raw): array
    {
        $info = [];
        $fields = ['Creator', 'Producer', 'Author', 'Title', 'Subject', 'Keywords', 'CreationDate', 'ModDate'];

        foreach ($fields as $field) {
            // Format 1: /Field (Text value dengan kurung)
            if (preg_match('/\/'.$field.'\s*\((.*?)\)(?=\s*\/|\s*>>)/s', $raw, $m)) {
                $info[$field] = self::cleanPdfString($m[1]);
            }
            // Format 2: /Field <Hex Encoded String>
            elseif (preg_match('/\/'.$field.'\s*<([0-9a-fA-F\s]+)>/s', $raw, $m)) {
                $hex = preg_replace('/\s+/', '', $m[1]);
                $binary = @hex2bin($hex);
                if ($binary !== false) {
                    $info[$field] = self::cleanPdfString($binary);
                }
            }
        }

        return $info;
    }

    /**
     * Ekstraksi XMP XML Metadata Stream (jika tertanam dalam PDF).
     *
     * @return array<string, mixed>
     */
    protected static function extractXmpMetadata(string $raw): array
    {
        $result = [
            'has_xmp' => false,
            'creator_tool' => null,
            'producer' => null,
            'create_date' => null,
            'modify_date' => null,
            'metadata_date' => null,
            'software_agents' => [],
            'history' => [],
        ];

        if (preg_match('/<x:xmpmeta.*?<\/x:xmpmeta>/s', $raw, $xmpMatch)) {
            $xmlString = $xmpMatch[0];
            $result['has_xmp'] = true;

            // Ekstrak CreatorTool
            if (preg_match('/<xmp:CreatorTool>(.*?)<\/xmp:CreatorTool>/s', $xmlString, $m)) {
                $result['creator_tool'] = trim(strip_tags($m[1]));
            }

            // Ekstrak pdf:Producer
            if (preg_match('/<pdf:Producer>(.*?)<\/pdf:Producer>/s', $xmlString, $m)) {
                $result['producer'] = trim(strip_tags($m[1]));
            }

            // Ekstrak CreateDate
            if (preg_match('/<xmp:CreateDate>(.*?)<\/xmp:CreateDate>/s', $xmlString, $m)) {
                $result['create_date'] = trim(strip_tags($m[1]));
            }

            // Ekstrak ModifyDate
            if (preg_match('/<xmp:ModifyDate>(.*?)<\/xmp:ModifyDate>/s', $xmlString, $m)) {
                $result['modify_date'] = trim(strip_tags($m[1]));
            }

            // Ekstrak softwareAgent dari xmpMM:History
            if (preg_match_all('/softwareAgent="([^"]+)"/i', $xmlString, $agentMatches)) {
                $result['software_agents'] = array_values(array_unique($agentMatches[1]));
            }

            // Ekstrak Action dari xmpMM:History
            if (preg_match_all('/<stEvt:action>(.*?)<\/stEvt:action>/s', $xmlString, $actMatches)) {
                $result['history'] = array_map('trim', $actMatches[1]);
            }
        }

        return $result;
    }

    /**
     * Bersihkan karakter khusus atau UTF-16BE pada string PDF.
     */
    protected static function cleanPdfString(string $val): string
    {
        // Deteksi UTF-16BE BOM (\xFE\xFF)
        if (str_starts_with($val, "\xFE\xFF")) {
            $converted = @mb_convert_encoding(substr($val, 2), 'UTF-8', 'UTF-16BE');
            if ($converted !== false) {
                return trim($converted);
            }
        }

        // Hapus karakter non-printable kecuali spasi & newline standar
        $clean = preg_replace('/[^\x20-\x7E\t\r\n]/', '', $val);

        return trim((string) $clean);
    }

    /**
     * Parsing tanggal PDF standar (D:YYYYMMDDHHmmSSOHH'mm').
     */
    public static function parsePdfDate(?string $rawDate): ?string
    {
        if (empty($rawDate)) {
            return null;
        }

        $cleaned = preg_replace('/^D:/', '', trim($rawDate));

        // Format YYYYMMDDHHmmss
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $cleaned, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}";
        }

        // Format YYYYMMDD
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $cleaned, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        // Format ISO-8601 standar (2026-11-07T13:54:20+07:00)
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $cleaned)) {
            try {
                return Carbon::parse($cleaned)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return $cleaned;
            }
        }

        return $rawDate;
    }

    /**
     * Ekstraksi cuplikan teks naskah PDF secara sederhana.
     */
    protected static function extractTextSample(string $raw): string
    {
        // Cari teks di dalam operator BT ... ET (Begin Text ... End Text)
        preg_match_all('/BT[\s\S]*?ET/', $raw, $textBlocks);
        $extracted = [];

        foreach ($textBlocks[0] ?? [] as $block) {
            // Ekstrak string di dalam kurung (...) Tj atau TJ
            if (preg_match_all('/\((.*?)\)\s*(?:Tj|TJ|\'|")/s', $block, $strMatches)) {
                foreach ($strMatches[1] as $s) {
                    $cleaned = self::cleanPdfString($s);
                    if (strlen($cleaned) > 2) {
                        $extracted[] = $cleaned;
                    }
                }
            }
        }

        $sample = implode(' ', $extracted);

        return trim(preg_replace('/\s+/', ' ', $sample));
    }

    /**
     * Format byte ke satuan yang mudah dibaca (KB / MB).
     */
    protected static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
