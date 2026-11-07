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

        // 6. Analisis Halaman dan Objek Citra (Deteksi Berkas Pindaian / Scan Fisik)
        preg_match_all('/\/Type\s*\/Page(?![sA-Za-z])/', $raw, $pageMatches);
        $pageCount = count($pageMatches[0] ?? []);
        if ($pageCount === 0 && preg_match('/\/Count\s+(\d+)/', $raw, $countMatch)) {
            $pageCount = (int) $countMatch[1];
        }
        $pageCount = max(1, $pageCount);

        preg_match_all('/\/Subtype\s*\/Image\b/', $raw, $imageMatches);
        $imageCount = count($imageMatches[0] ?? []);

        // 7. Deteksi Barcode & Kode Verifikasi Sultan Banten (SB-...)
        // Dekompresi stream objek agar teks terkompresi FlateDecode dapat dideteksi secara komprehensif
        $searchableText = $raw;
        $decompressedStreams = [];
        if (preg_match_all('/stream[\r\n]+([\s\S]*?)[\r\n]+endstream/s', $raw, $streamMatches)) {
            foreach ($streamMatches[1] as $streamChunk) {
                $trimmed = trim($streamChunk, "\r\n ");
                $decompressed = @gzuncompress($trimmed);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($trimmed);
                }
                if ($decompressed === false && strlen($trimmed) > 6) {
                    $decompressed = @gzinflate(substr($trimmed, 2, -4));
                }
                if ($decompressed !== false) {
                    $decompressedStreams[] = $decompressed;
                    $searchableText .= ' '.$decompressed;
                }
            }
        }

        preg_match_all('/SB-[A-Z0-9]{8,16}/', $searchableText, $sbMatches);
        $detectedCodes = array_values(array_unique($sbMatches[0] ?? []));

        $qrVerificationResults = [];
        $hasInvalidQr = false;
        $hasValidQr = false;

        foreach ($detectedCodes as $code) {
            $naskah = TppNaskahKanwil::where('kode_verifikasi', $code)
                ->with(['sidangTpp.upt'])
                ->first();

            if ($naskah) {
                $hasValidQr = true;
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

        // 8. Deteksi Riwayat Pemotongan / Penghapusan Halaman & Lampiran (Forensik Delta Inkremental & Slack Space)
        $deletionAnalysis = self::detectDeletedContent($raw, $decompressedStreams, $eofCount);

        // 9. Ekstraksi Teks Sampel untuk Analisis AI
        $sampleText = self::extractTextSample($raw, $decompressedStreams);
        $isScanned = ($imageCount >= $pageCount && mb_strlen($sampleText) < 100);
        $docTypeHint = $isScanned
            ? "Berkas Hasil Pemindaian (Scan Fisik - {$pageCount} Halaman, {$imageCount} Objek Citra)"
            : "Dokumen Digital Asli (Native PDF Teks - {$pageCount} Halaman)";

        // 10. Analisis Indikator Manipulasi (Heuristic Rules)
        $indicators = [];
        $heuristicScore = 100; // Asumsi awal 100% otentik

        // Cek temuan penghapusan halaman / lampiran berkas
        if ($deletionAnalysis['has_deletion']) {
            $heuristicScore -= 70; // Penalti berat: terbukti manipulasi / pemotongan berkas
            $indicators[] = [
                'kategori' => 'danger',
                'judul' => 'Terdeteksi Riwayat Pemotongan / Penghapusan Lampiran Naskah',
                'deskripsi' => implode(' ', $deletionAnalysis['evidence']),
            ];
        }

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

                if ($mCarbon->lt($cCarbon->subMinutes(2))) {
                    // ModDate lebih awal dari CreationDate (anomali waktu / time travel)
                    $heuristicScore -= 25;
                    $indicators[] = [
                        'kategori' => 'danger',
                        'judul' => 'Anomali Urutan Waktu Modifikasi (Time Inversion)',
                        'deskripsi' => "Waktu modifikasi ({$modDate}) tercatat lebih awal dari waktu pembuatan berkas ({$creationDate}). Hal ini mengindikasikan rekayasa atribut tanggal.",
                    ];
                } else {
                    $diffHours = $cCarbon->diffInHours($mCarbon);
                    if ($diffHours > 720) { // Selisih lebih dari 30 hari
                        $heuristicScore -= 10;
                        $indicators[] = [
                            'kategori' => 'info',
                            'judul' => 'Jeda Waktu Modifikasi Dokumen Cukup Lama',
                            'deskripsi' => "Berkas dibuat pada {$creationDate} dan disimpan kembali pada {$modDate} (selisih {$diffHours} jam / lebih dari 30 hari).",
                        ];
                    }
                }
            } catch (\Throwable) {
                // Abaikan kesalahan parsing tanggal
            }
        }

        // Cek inkremental revisi (%%EOF)
        // Note: 1 = struktur tunggal, 2 = 1 revisi inkremental (sangat lazim pada berkas tertanda tangan/bercap)
        if ($eofCount > 3) {
            $heuristicScore -= 10;
            $indicators[] = [
                'kategori' => 'info',
                'judul' => 'Struktur Inkremental Terdeteksi (Disimpan Berulang Kali)',
                'deskripsi' => "Ditemukan {$eofCount} penanda revisi (%%EOF). Dokumen pernah dibuka dan disimpan ulang beberapa kali.",
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
        } elseif ($hasValidQr) {
            $indicators[] = [
                'kategori' => 'success',
                'judul' => 'Kode Verifikasi QR Terdaftar Resmi',
                'deskripsi' => 'Naskah memuat kode tanda tangan resmi Sultan Banten yang tercatat di database instansi.',
            ];
        } else {
            $indicators[] = [
                'kategori' => 'info',
                'judul' => 'Dokumen Naskah Tanpa Tanda Tangan QR Sultan Banten',
                'deskripsi' => 'Dokumen tidak memuat kode QR verifikasi Sultan Banten. Kondisi ini wajar untuk surat dinas masuk dari UPT, permohonan eksternal, atau draf naskah sebelum disahkan.',
            ];
        }

        $heuristicScore = max(5, min(100, $heuristicScore));

        // 10. Panggil Analisis AI Gemini
        $aiAnalysis = GeminiAiService::analyzeForensicPdf([
            'file_name' => $fileName,
            'file_size' => self::formatBytes($fileSize),
            'pdf_version' => $pdfVersion,
            'page_count' => $pageCount,
            'image_count' => $imageCount,
            'is_scanned' => $isScanned,
            'doc_type_hint' => $docTypeHint,
            'creator' => $creator,
            'producer' => $producer,
            'creation_date' => $creationDate,
            'mod_date' => $modDate,
            'revisions_count' => $eofCount,
            'detected_suspicious_software' => $detectedSuspicious,
            'detected_qr_codes' => $detectedCodes,
            'qr_verification_results' => $qrVerificationResults,
            'deleted_content' => $deletionAnalysis,
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
                'page_count' => $pageCount,
                'image_count' => $imageCount,
                'is_scanned' => $isScanned,
                'doc_type_hint' => $docTypeHint,
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
            'deleted_content' => $deletionAnalysis,
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

        // Jika teks adalah valid UTF-8, pertahankan karakter huruf/angka/tanda baca standar
        if (mb_check_encoding($val, 'UTF-8')) {
            $clean = preg_replace('/[^\p{L}\p{N}\p{P}\p{Z}\r\n\t]/u', '', $val);
        } else {
            $clean = preg_replace('/[^\x20-\x7E\t\r\n]/', '', $val);
        }

        return trim((string) $clean);
    }

    /**
     * Decode karakter khusus dan escape sequence PDF (misal \(, \), \\, \ddd octal).
     */
    protected static function decodePdfString(string $val): string
    {
        $unescaped = str_replace(
            ['\\(', '\\)', '\\\\', '\\r', '\\n', '\\t'],
            ['(', ')', '\\', "\r", "\n", "\t"],
            $val
        );

        // Decode octal escape sequences (\ddd)
        $unescaped = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $unescaped);

        return self::cleanPdfString((string) $unescaped);
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
     * Ekstraksi cuplikan teks naskah PDF secara komprehensif,
     * mendukung operator string (...), array [...], hex <...>, dan unescape karakter.
     *
     * @param  string  $raw  PDF mentah
     * @param  array<int, string>  $decompressedStreams  Daftar stream objek yang sudah didekompresi
     */
    protected static function extractTextSample(string $raw, array $decompressedStreams = []): string
    {
        $sources = array_merge([$raw], $decompressedStreams);
        $extracted = [];

        foreach ($sources as $source) {
            // 1. Ekstrak teks dalam blok BT ... ET (Begin Text ... End Text)
            if (preg_match_all('/BT[\s\S]*?ET/s', $source, $textBlocks)) {
                foreach ($textBlocks[0] as $block) {
                    // Tangkap string dalam kurung: (Text) Tj, ' atau "
                    // Serta tangkap semua kurung di dalam TJ array: [(Te) 10 (xt)] TJ
                    if (preg_match_all('/\((.*?)(?<!\\\\)\)/s', $block, $strMatches)) {
                        foreach ($strMatches[1] as $s) {
                            $decoded = self::decodePdfString($s);
                            if (mb_strlen($decoded) >= 2) {
                                $extracted[] = $decoded;
                            }
                        }
                    }

                    // Tangkap string Hexadecimal: <48656c6c6f> Tj atau di dalam array TJ
                    if (preg_match_all('/<([0-9a-fA-F\s]+)>/s', $block, $hexMatches)) {
                        foreach ($hexMatches[1] as $hexChunk) {
                            $cleanHex = preg_replace('/\s+/', '', $hexChunk);
                            if (strlen($cleanHex) >= 4 && strlen($cleanHex) % 2 === 0) {
                                $bin = @hex2bin($cleanHex);
                                if ($bin !== false) {
                                    $decoded = self::cleanPdfString($bin);
                                    if (mb_strlen($decoded) >= 2) {
                                        $extracted[] = $decoded;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $sample = implode(' ', $extracted);
        $sample = trim(preg_replace('/\s+/', ' ', $sample));

        return $sample;
    }

    /**
     * Deteksi riwayat penghapusan halaman, berkas lampiran, atau konten tersembunyi (Slack Space).
     *
     * @param  string  $raw  Biner berkas PDF lengkap
     * @param  array<int, string>  $decompressedStreams  Stream yang sudah didekompresi
     * @param  int  $eofCount  Jumlah %%EOF (revisi inkremental)
     * @return array<string, mixed>
     */
    public static function detectDeletedContent(string $raw, array $decompressedStreams, int $eofCount): array
    {
        $evidence = [];
        $hasDeletion = false;
        $deletedPagesCount = 0;
        $deletedAttachments = [];
        $deletedTextSnippets = [];

        // 1. Ekstraksi teks halaman AKTIF (halaman yang benar-benar ditampilkan oleh PDF reader)
        $activePagesInfo = self::getActivePagesInfo($raw);
        $activeVisibleText = $activePagesInfo['active_text'] ?? '';
        $activePageCount = $activePagesInfo['page_count'] ?? 1;

        // 2. Analisis Delta Antar-Revisi Inkremental (Jika %%EOF >= 2)
        if ($eofCount >= 2) {
            $eofPositions = [];
            $offset = 0;
            while (($pos = strpos($raw, '%%EOF', $offset)) !== false) {
                $eofPositions[] = $pos;
                $offset = $pos + 5;
            }

            if (! empty($eofPositions)) {
                // Revisi 1: dari byte 0 sampai %%EOF pertama
                $rev1Raw = substr($raw, 0, $eofPositions[0] + 5);
                $rev1PagesInfo = self::getActivePagesInfo($rev1Raw);
                $rev1PageCount = $rev1PagesInfo['page_count'] ?? 0;

                // Periksa apakah halaman berkurang antara Revisi 1 dan Revisi Final
                if ($rev1PageCount > $activePageCount) {
                    $hasDeletion = true;
                    $diff = $rev1PageCount - $activePageCount;
                    $deletedPagesCount += $diff;

                    // Cari teks dari halaman yang hilang
                    $missingPageIds = array_diff($rev1PagesInfo['page_ids'] ?? [], $activePagesInfo['page_ids'] ?? []);
                    $missingText = self::extractTextFromPageIds($raw, $missingPageIds);
                    if (! empty($missingText)) {
                        $deletedTextSnippets[] = $missingText;
                    }

                    $evidence[] = "Terdeteksi pemotongan {$diff} lembar halaman pada riwayat revisi inkremental berkas (Revisi awal memiliki {$rev1PageCount} halaman, sedangkan naskah saat ini hanya tersisa {$activePageCount} halaman).";
                }
            }
        }

        // 3. Deteksi Berkas Lampiran Tertanam (/Type /Filespec & /EmbeddedFiles)
        // Cari semua referensi nama lampiran yang tertulis di dalam berkas
        if (preg_match_all('/\/Filespec\b[\s\S]*?\/(?:F|UF)\s*\((.*?)\)/i', $raw, $fsMatches)) {
            $foundFilenames = array_unique(array_filter(array_map('trim', $fsMatches[1] ?? [])));
            foreach ($foundFilenames as $fn) {
                $fnClean = self::cleanPdfString($fn);
                if (stripos($fnClean, 'nusakambangan') !== false || stripos($fnClean, 'lampiran') !== false) {
                    if (stripos($activeVisibleText, $fnClean) === false && stripos($activeVisibleText, 'nusakambangan') === false) {
                        $hasDeletion = true;
                        $deletedAttachments[] = $fnClean;
                        $evidence[] = "Ditemukan jejak berkas lampiran '{$fnClean}' yang pernah ditautkan namun kini tidak aktif pada naskah utama.";
                    }
                }
            }
        }

        // 4. Deteksi Objek Konten Teks Yatim / Slack Space (Ghost Content)
        // Gabungkan seluruh teks yang ada di seluruh stream berkas
        $allStreamsText = self::extractTextSample($raw, $decompressedStreams);

        // Daftar entitas kritis Pemasyarakatan
        $criticalKeywords = [
            'nusakambangan' => 'Lapas Nusakambangan',
            'lampiran usulan' => 'Lampiran Usulan Mutasi',
            'daftar narapidana' => 'Daftar Narapidana / WBP',
            'daftar wbp' => 'Daftar WBP',
            'pasir putih' => 'Lapas Pasir Putih',
            'batu nusakambangan' => 'Lapas Batu Nusakambangan',
            'karanganyar' => 'Lapas Karanganyar',
            'besi nusakambangan' => 'Lapas Besi',
            'kembangkuning' => 'Lapas Kembangkuning',
            'permisan' => 'Lapas Permisan',
        ];

        $ghostFindings = [];
        foreach ($criticalKeywords as $keyword => $label) {
            $inAll = (stripos($allStreamsText, $keyword) !== false);
            $inActive = (stripos($activeVisibleText, $keyword) !== false);

            // Jika kata kunci ada di berkas fisik (riwayat/slack space), tapi TIDAK ADA sama sekali di teks naskah aktif:
            if ($inAll && ! $inActive) {
                $ghostFindings[] = $label;
            }
        }

        if (! empty($ghostFindings)) {
            $hasDeletion = true;
            $evidence[] = 'Ditemukan jejak konten teks tersembunyi (slack space / objek terputus) yang memuat entitas: '
                .implode(', ', array_unique($ghostFindings))
                .'. Konten ini tersimpan dalam objek biner berkas tetapi TIDAK ditampilkan pada halaman naskah aktif!';

            // Ambil cuplikan teks di sekitar kata kunci untuk pembuktian
            foreach ($criticalKeywords as $keyword => $label) {
                $pos = stripos($allStreamsText, $keyword);
                if ($pos !== false && stripos($activeVisibleText, $keyword) === false) {
                    $start = max(0, $pos - 80);
                    $len = 220;
                    $snippet = substr($allStreamsText, $start, $len);
                    $deletedTextSnippets[] = '...'.trim($snippet).'...';
                    break;
                }
            }
        }

        // 5. Analisis XMP History (xmpMM:History)
        if (preg_match('/<x:xmpmeta[\s\S]*?<\/x:xmpmeta>/s', $raw, $xmpMatch)) {
            $xml = $xmpMatch[0];
            if (preg_match_all('/<stEvt:action>(.*?)<\/stEvt:action>/i', $xml, $actMatches)) {
                foreach ($actMatches[1] as $act) {
                    $cleanAct = strtolower(trim($act));
                    if (in_array($cleanAct, ['deleted', 'removed', 'cropped'])) {
                        $hasDeletion = true;
                        $evidence[] = "Riwayat XMP mencatat tindakan penyuntingan destruktif: '{$cleanAct}'.";
                    }
                }
            }
        }

        return [
            'has_deletion' => $hasDeletion,
            'deleted_pages_count' => $deletedPagesCount,
            'deleted_attachments' => array_values(array_unique($deletedAttachments)),
            'evidence' => array_values(array_unique($evidence)),
            'deleted_text_sample' => ! empty($deletedTextSnippets) ? implode("\n", array_slice($deletedTextSnippets, 0, 2)) : null,
            'active_pages_count' => $activePageCount,
            'active_text_preview' => substr($activeVisibleText, 0, 500),
        ];
    }

    /**
     * Dapatkan informasi halaman aktif dan teks halaman naskah utama.
     *
     * @return array{
     *   page_count: int,
     *   page_ids: array<int, int>,
     *   content_stream_ids: array<int, int>,
     *   active_text: string
     * }
     */
    protected static function getActivePagesInfo(string $raw): array
    {
        $pageIds = [];
        $contentIds = [];
        $activeTextParts = [];

        // 1. Cari Root Catalog dari trailer terakhir atau /Type /Catalog
        $rootObjId = null;
        if (preg_match_all('/trailer\s*<<[\s\S]*?\/Root\s+(\d+)\s+\d+\s+R[\s\S]*?>>/s', $raw, $trMatches)) {
            $lastIndex = count($trMatches[1]) - 1;
            $rootObjId = (int) $trMatches[1][$lastIndex];
        }

        if (! $rootObjId && preg_match('/(\d+)\s+\d+\s+obj\s*<<[\s\S]*?\/Type\s*\/Catalog[\s\S]*?>>/s', $raw, $catMatch)) {
            $rootObjId = (int) $catMatch[1];
        }

        // 2. Dari Catalog, cari objek /Pages
        $pagesObjId = null;
        if ($rootObjId) {
            if (preg_match_all('/'.preg_quote((string) $rootObjId, '/').'\s+\d+\s+obj\s*<<([\s\S]*?)>>\s*endobj/s', $raw, $catObjMatches)) {
                $lastCat = end($catObjMatches[1]);
                if (preg_match('/\/Pages\s+(\d+)\s+\d+\s+R/', $lastCat, $pMatch)) {
                    $pagesObjId = (int) $pMatch[1];
                }
            }
        }

        if (! $pagesObjId && preg_match('/(\d+)\s+\d+\s+obj\s*<<[\s\S]*?\/Type\s*\/Pages\b[\s\S]*?>>/s', $raw, $pMatch)) {
            $pagesObjId = (int) $pMatch[1];
        }

        // 3. Dari objek Pages, ekstrak /Kids dan /Count
        $pageCount = 0;
        if ($pagesObjId) {
            if (preg_match_all('/'.preg_quote((string) $pagesObjId, '/').'\s+\d+\s+obj\s*<<([\s\S]*?)>>\s*endobj/s', $raw, $pagesObjMatches)) {
                $lastPagesObj = end($pagesObjMatches[1]);
                if (preg_match('/\/Count\s+(\d+)/', $lastPagesObj, $cMatch)) {
                    $pageCount = (int) $cMatch[1];
                }
                if (preg_match('/\/Kids\s*\[([\s\S]*?)\]/', $lastPagesObj, $kidsMatch)) {
                    if (preg_match_all('/(\d+)\s+\d+\s+R/', $kidsMatch[1], $kMatches)) {
                        $pageIds = array_map('intval', $kMatches[1]);
                    }
                }
            }
        }

        // Fallback jika tidak terurai dari hierarki /Pages
        if (empty($pageIds)) {
            if (preg_match_all('/(\d+)\s+\d+\s+obj\s*<<[\s\S]*?\/Type\s*\/Page(?![sA-Za-z])[\s\S]*?>>/s', $raw, $allPageMatches)) {
                $pageIds = array_map('intval', $allPageMatches[1]);
            }
            if ($pageCount === 0) {
                $pageCount = count($pageIds);
            }
        }

        // 4. Untuk setiap Page ID, cari objek /Contents
        foreach ($pageIds as $pId) {
            if (preg_match_all('/'.preg_quote((string) $pId, '/').'\s+\d+\s+obj\s*<<([\s\S]*?)>>\s*endobj/s', $raw, $pageDefMatches)) {
                $lastPageDef = end($pageDefMatches[1]);
                if (preg_match('/\/Contents\s+(\d+)\s+\d+\s+R/', $lastPageDef, $cMatch)) {
                    $contentIds[] = (int) $cMatch[1];
                } elseif (preg_match('/\/Contents\s*\[([\s\S]*?)\]/', $lastPageDef, $cArrMatch)) {
                    if (preg_match_all('/(\d+)\s+\d+\s+R/', $cArrMatch[1], $cMatches)) {
                        foreach ($cMatches[1] as $cid) {
                            $contentIds[] = (int) $cid;
                        }
                    }
                }
            }
        }

        $contentIds = array_values(array_unique($contentIds));

        // 5. Ekstrak dan dekompresi teks HANYA dari stream konten halaman aktif
        foreach ($contentIds as $cId) {
            $streamText = self::extractObjectContentText($raw, $cId);
            if (! empty($streamText)) {
                $activeTextParts[] = $streamText;
            }
        }

        $activeText = implode(' ', $activeTextParts);
        $activeText = trim(preg_replace('/\s+/', ' ', $activeText));

        return [
            'page_count' => max(1, $pageCount ?: count($pageIds)),
            'page_ids' => $pageIds,
            'content_stream_ids' => $contentIds,
            'active_text' => $activeText,
        ];
    }

    /**
     * Ekstraksi teks dari objek stream tertentu berdasarkan ID objek.
     */
    protected static function extractObjectContentText(string $raw, int $objId): string
    {
        if (preg_match_all('/'.preg_quote((string) $objId, '/').'\s+\d+\s+obj([\s\S]*?)endobj/s', $raw, $matches)) {
            $lastObjDef = end($matches[1]);
            if (preg_match('/stream[\r\n]+([\s\S]*?)[\r\n]+endstream/s', $lastObjDef, $streamMatch)) {
                $chunk = trim($streamMatch[1], "\r\n ");
                $decompressed = @gzuncompress($chunk);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($chunk);
                }
                if ($decompressed === false && strlen($chunk) > 6) {
                    $decompressed = @gzinflate(substr($chunk, 2, -4));
                }
                $content = ($decompressed !== false) ? $decompressed : $chunk;

                return self::extractTextSample($content);
            }
        }

        return '';
    }

    /**
     * Ekstraksi teks gabungan dari daftar ID halaman tertentu.
     *
     * @param  array<int, int>  $pageIds
     */
    protected static function extractTextFromPageIds(string $raw, array $pageIds): string
    {
        $texts = [];
        foreach ($pageIds as $pId) {
            if (preg_match_all('/'.preg_quote((string) $pId, '/').'\s+\d+\s+obj\s*<<([\s\S]*?)>>\s*endobj/s', $raw, $pageDefMatches)) {
                $lastPageDef = end($pageDefMatches[1]);
                $cIds = [];
                if (preg_match('/\/Contents\s+(\d+)\s+\d+\s+R/', $lastPageDef, $cMatch)) {
                    $cIds[] = (int) $cMatch[1];
                } elseif (preg_match('/\/Contents\s*\[([\s\S]*?)\]/', $lastPageDef, $cArrMatch)) {
                    if (preg_match_all('/(\d+)\s+\d+\s+R/', $cArrMatch[1], $cMatches)) {
                        $cIds = array_map('intval', $cMatches[1]);
                    }
                }

                foreach ($cIds as $cId) {
                    $t = self::extractObjectContentText($raw, $cId);
                    if (! empty($t)) {
                        $texts[] = $t;
                    }
                }
            }
        }

        return implode(' ', $texts);
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
