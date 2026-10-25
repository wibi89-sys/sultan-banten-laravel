<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class TppExcelService
{
    /**
     * Download template Excel resmi untuk usulan WBP Sidang TPP.
     */
    public static function downloadTemplate(): StreamedResponse
    {
        $filename = 'Template_Usulan_Sidang_TPP_'.date('Ymd').'.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>';
            echo 'th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; font-family: Arial; font-size: 11pt; text-align: center; border: 1px solid #000; padding: 6px; }';
            echo 'td { font-family: Arial; font-size: 10pt; border: 1px solid #d1d5db; padding: 5px; }';
            echo '.txt { mso-number-format:"\@"; }';
            echo '.tgl { mso-number-format:"yyyy-mm-dd"; text-align: center; }';
            echo '.num { text-align: right; }';
            echo '</style></head><body>';

            echo '<table border="1">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>No</th>';
            echo '<th>Nama Warga Binaan</th>';
            echo '<th>Nomor Register</th>';
            echo '<th>Tindak Pidana / Pasal</th>';
            echo '<th>Lama Pidana</th>';
            echo '<th>Tanggal Ekspirasi (YYYY-MM-DD)</th>';
            echo '<th>Tanggal 1/2 Pidana (YYYY-MM-DD)</th>';
            echo '<th>Tanggal 2/3 Pidana (YYYY-MM-DD)</th>';
            echo '<th>Jenis Usulan (PB, CB, CMB, Asimilasi, Remisi, Register F, Lainnya)</th>';
            echo '<th style="background-color: #dc2626; color: #ffffff;">Menarik Perhatian Kanwil (Ya/Tidak)</th>';
            echo '<th>Catatan / Keterangan Khusus</th>';
            echo '</tr>';
            echo '</thead>';

            echo '<tbody>';
            // Sample Row 1 - PB (Biasa / Normal)
            echo '<tr>';
            echo '<td align="center">1</td>';
            echo '<td class="txt">Ahmad Sulaeman bin Dahlan</td>';
            echo '<td class="txt">BI.124/2024</td>';
            echo '<td class="txt">Narkotika Ps. 114 ayat (1) UU 35/2009</td>';
            echo '<td class="txt">5 Tahun</td>';
            echo '<td class="tgl">'.Carbon::now()->addYears(2)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->subMonths(6)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->addMonths(2)->format('Y-m-d').'</td>';
            echo '<td class="txt" align="center">PB</td>';
            echo '<td class="txt" align="center">Tidak</td>';
            echo '<td class="txt">Berkelakuan baik, aktif mengikuti pembinaan kemandirian</td>';
            echo '</tr>';

            // Sample Row 2 - PB (Atensi Khusus / Perhatian Kanwil)
            echo '<tr>';
            echo '<td align="center">2</td>';
            echo '<td class="txt">Budi Santoso bin Anwar</td>';
            echo '<td class="txt">BII.056/2025</td>';
            echo '<td class="txt">Tipikor Ps. 2 UU 31/1999 (Korupsi)</td>';
            echo '<td class="txt">4 Tahun</td>';
            echo '<td class="tgl">'.Carbon::now()->addMonths(18)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->subMonths(3)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->addMonths(1)->format('Y-m-d').'</td>';
            echo '<td class="txt" align="center">PB</td>';
            echo '<td class="txt" align="center" style="background-color: #fee2e2; color: #991b1b; font-weight: bold;">Ya</td>';
            echo '<td class="txt">Perkara Menonjol / Tipikor, mohon ketelitian verifikasi tanda bukti lunas denda dan uang pengganti</td>';
            echo '</tr>';

            // Sample Row 3 - Asimilasi (Cukup Asimilasi)
            echo '<tr>';
            echo '<td align="center">3</td>';
            echo '<td class="txt">Rian Hidayat bin Sukardi</td>';
            echo '<td class="txt">BI.089/2024</td>';
            echo '<td class="txt">Penggelapan Ps. 372 KUHP</td>';
            echo '<td class="txt">2 Tahun 6 Bulan</td>';
            echo '<td class="tgl">'.Carbon::now()->addMonths(14)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->subMonths(2)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->addMonths(3)->format('Y-m-d').'</td>';
            echo '<td class="txt" align="center">Asimilasi</td>';
            echo '<td class="txt" align="center">Tidak</td>';
            echo '<td class="txt">Diusulkan program asimilasi kebersihan lingkungan</td>';
            echo '</tr>';

            // Sample Row 4 - Register F (Atensi Khusus / Perhatian Kanwil)
            echo '<tr>';
            echo '<td align="center">4</td>';
            echo '<td class="txt">Deni Kurniawan bin Sulaeman</td>';
            echo '<td class="txt">BI.112/2024</td>';
            echo '<td class="txt">Pelanggaran Tata Tertib Ps. 351 KUHP</td>';
            echo '<td class="txt">1 Tahun 6 Bulan</td>';
            echo '<td class="tgl">'.Carbon::now()->addMonths(8)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->subMonths(1)->format('Y-m-d').'</td>';
            echo '<td class="tgl">'.Carbon::now()->addMonths(2)->format('Y-m-d').'</td>';
            echo '<td class="txt" align="center">Register F</td>';
            echo '<td class="txt" align="center" style="background-color: #fee2e2; color: #991b1b; font-weight: bold;">Ya</td>';
            echo '<td class="txt">Tercatat dalam Register F pelanggaran tata tertib rutan, usulan hak integrasi tidak disetujui</td>';
            echo '</tr>';

            echo '</tbody>';
            echo '</table>';
            echo '</body></html>';
        }, 200, $headers);
    }

    /**
     * Parse file Excel / CSV yang di-upload oleh user.
     */
    public static function parseUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        if (in_array($extension, ['csv', 'txt'])) {
            return static::parseCsv($realPath);
        }

        if ($extension === 'xlsx') {
            return static::parseXlsx($realPath);
        }

        // Fallback untuk HTML/XML .xls
        return static::parseHtmlXls($realPath);
    }

    /**
     * Parser file CSV
     */
    protected static function parseCsv(string $path): array
    {
        $rows = [];
        $warnings = [];

        if (! file_exists($path) || ! is_readable($path)) {
            return ['success' => false, 'message' => 'Berkas tidak dapat dibaca.', 'data' => []];
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return ['success' => false, 'message' => 'Gagal membuka berkas CSV.', 'data' => []];
        }

        // Deteksi delimiter: koma atau titik koma
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $isHeader = true;
        $rowNum = 1;

        while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if ($isHeader) {
                $isHeader = false;

                continue;
            }

            if (empty(array_filter($data, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $parsed = static::normalizeRow($data, $rowNum);
            if ($parsed) {
                $rows[] = $parsed;
            }
            $rowNum++;
        }

        fclose($handle);

        return [
            'success' => true,
            'data' => $rows,
            'total' => count($rows),
            'warnings' => $warnings,
        ];
    }

    /**
     * Parser native XLSX (Office Open XML) menggunakan ZipArchive & SimpleXML
     */
    protected static function parseXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return static::parseHtmlXls($path);
        }

        // 1. Baca Shared Strings
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = @simplexml_load_string($sharedXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $t = '';
                        foreach ($si->r as $r) {
                            $t .= (string) $r->t;
                        }
                        $sharedStrings[] = $t;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca Worksheet pertama
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return ['success' => false, 'message' => 'Lembar kerja sheet1.xml tidak ditemukan di dalam berkas Excel.', 'data' => []];
        }

        $xml = @simplexml_load_string($sheetXml);
        if (! $xml || ! isset($xml->sheetData->row)) {
            return ['success' => false, 'message' => 'Format file XLSX tidak valid atau lembar kosong.', 'data' => []];
        }

        $rawRows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $type = (string) $cell['t'];
                $val = (string) $cell->v;

                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $cellValue = $sharedStrings[(int) $val];
                } else {
                    $cellValue = $val;
                }
                $rowData[] = $cellValue;
            }
            $rawRows[] = $rowData;
        }

        if (count($rawRows) <= 1) {
            return ['success' => true, 'data' => [], 'total' => 0, 'warnings' => ['Data kosong di berkas Excel']];
        }

        // Buang baris header pertama
        array_shift($rawRows);

        $rows = [];
        $rowNum = 2;
        foreach ($rawRows as $raw) {
            $parsed = static::normalizeRow($raw, $rowNum);
            if ($parsed) {
                $rows[] = $parsed;
            }
            $rowNum++;
        }

        return [
            'success' => true,
            'data' => $rows,
            'total' => count($rows),
            'warnings' => [],
        ];
    }

    /**
     * Parser file XLS format HTML table
     */
    protected static function parseHtmlXls(string $path): array
    {
        $content = @file_get_contents($path);
        if (! $content) {
            return ['success' => false, 'message' => 'Berkas tidak dapat dibaca.', 'data' => []];
        }

        $rows = [];
        // Match table rows
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $trMatches)) {
            $isHeader = true;
            $rowNum = 1;

            foreach ($trMatches[1] as $trHtml) {
                if (preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $trHtml, $tdMatches)) {
                    $cells = array_map(function ($c) {
                        return trim(html_entity_decode(strip_tags($c), ENT_QUOTES, 'UTF-8'));
                    }, $tdMatches[1]);

                    if ($isHeader) {
                        $isHeader = false;

                        continue;
                    }

                    if (empty(array_filter($cells, fn ($val) => trim((string) $val) !== ''))) {
                        continue;
                    }

                    $parsed = static::normalizeRow($cells, $rowNum);
                    if ($parsed) {
                        $rows[] = $parsed;
                    }
                    $rowNum++;
                }
            }
        }

        return [
            'success' => true,
            'data' => $rows,
            'total' => count($rows),
            'warnings' => [],
        ];
    }

    /**
     * Normalisasi baris data mentah ke atribut yang terstruktur
     */
    protected static function normalizeRow(array $data, int $rowNum): ?array
    {
        // Posisi kolom standar:
        // 0: No
        // 1: Nama WBP
        // 2: No Register
        // 3: Tindak Pidana / Pasal
        // 4: Lama Pidana
        // 5: Tgl Ekspirasi
        // 6: Tgl 1/2 Pidana
        // 7: Tgl 2/3 Pidana
        // 8: Jenis Usulan
        // Posisi kolom standar baru:
        // 0: No
        // 1: Nama WBP
        // 2: No Register
        // 3: Tindak Pidana / Pasal
        // 4: Lama Pidana
        // 5: Tgl Ekspirasi
        // 6: Tgl 1/2 Pidana
        // 7: Tgl 2/3 Pidana
        // 8: Jenis Usulan (PB, CB, CMB, Asimilasi, Remisi, Register F, Lainnya)
        // 9: Menarik Perhatian Kanwil (Ya/Tidak)
        // 10: Catatan / Keterangan

        $namaWbp = trim((string) ($data[1] ?? ''));
        $nomorRegister = trim((string) ($data[2] ?? ''));

        // Jika nama dan register kosong, abaikan baris ini
        if ($namaWbp === '' && $nomorRegister === '') {
            return null;
        }

        $jenisUsulanRaw = trim((string) ($data[8] ?? 'PB'));
        $cleanJenis = strtoupper($jenisUsulanRaw);
        $jenisUsulan = match (true) {
            str_contains($cleanJenis, 'REG') || str_contains($cleanJenis, 'REGISTER') => 'Register F',
            str_contains($cleanJenis, 'ASIMILASI') => 'Asimilasi',
            $cleanJenis === 'REMISI' => 'Remisi',
            $cleanJenis === 'CMB' => 'CMB',
            $cleanJenis === 'CB' => 'CB',
            $cleanJenis === 'PB' => 'PB',
            $cleanJenis === 'LAINNYA' => 'Lainnya',
            default => in_array($cleanJenis, ['PB', 'CB', 'CMB', 'ASIMILASI', 'REMISI', 'REGISTER F', 'LAINNYA']) ? $jenisUsulanRaw : 'PB',
        };

        // Deteksi format lama (jika kolom 9 adalah angka SPPN dan ada kolom 11/12)
        $isLegacy = is_numeric($data[9] ?? null) && (isset($data[11]) || isset($data[12]));

        $perluPerhatian = false;
        $catatanPerhatian = '';

        if (! $isLegacy) {
            $rawPerhatian = strtolower(trim((string) ($data[9] ?? '')));
            $perluPerhatian = in_array($rawPerhatian, ['ya', 'y', '1', 'true', 'perhatian', 'atensi', 'centang', 'v', 'x', 'merah']);
            $catatanPerhatian = trim((string) ($data[10] ?? ''));
        } else {
            $catatanPerhatian = trim((string) ($data[13] ?? ''));
        }

        return [
            'row_num' => $rowNum,
            'nama_wbp' => $namaWbp ?: 'Warga Binaan Tanpa Nama',
            'nomor_register' => $nomorRegister ?: '-',
            'perkara_pasal' => trim((string) ($data[3] ?? '-')),
            'lama_pidana' => trim((string) ($data[4] ?? '-')),
            'tanggal_ekspirasi' => static::parseDate((string) ($data[5] ?? '')),
            'tanggal_setengah_pidana' => static::parseDate((string) ($data[6] ?? '')),
            'tanggal_dua_pertiga' => static::parseDate((string) ($data[7] ?? '')),
            'jenis_usulan' => $jenisUsulan,
            'perlu_perhatian' => $perluPerhatian,
            'catatan_perhatian' => $catatanPerhatian,
            'alasan_keterangan' => $catatanPerhatian,
            'keputusan' => 'belum_putus',
        ];
    }

    /**
     * Konversi variasi format tanggal Excel menjadi format Y-m-d standar
     */
    protected static function parseDate(?string $value): ?string
    {
        if (empty($value) || trim($value) === '' || trim($value) === '-') {
            return null;
        }

        $clean = trim($value);

        // Jika angka integer serial Excel (misal: 45678)
        if (is_numeric($clean) && (int) $clean > 25000 && (int) $clean < 80000) {
            $unixTime = ((int) $clean - 25569) * 86400;

            return date('Y-m-d', $unixTime);
        }

        // Coba parsing format teks umum: Y-m-d, d/m/Y, d-m-Y
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'j/n/Y', 'j-n-Y'];
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $clean);
                if ($dt) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Lanjut ke format berikutnya
            }
        }

        // Fallback Carbon::parse
        try {
            return Carbon::parse($clean)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
