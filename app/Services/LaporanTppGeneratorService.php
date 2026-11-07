<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\Instansi;
use Illuminate\Http\Response;

class LaporanTppGeneratorService
{
    /**
     * Generate berkas Microsoft Word (.doc) untuk Laporan Pelaksanaan Sidang TPP Kanwil.
     *
     * @param  array<string, mixed>  $params
     */
    public static function generateWord(array $params): Response
    {
        $html = self::renderHtmlTemplate($params, true);

        $cleanUpt = preg_replace('/[^a-zA-Z0-9_-]/', '_', $params['upt_nama'] ?? 'UPT');
        $filename = 'Laporan_Sidang_TPP_Kanwil_'.$cleanUpt.'_'.date('Ymd_His').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Generate berkas PDF Resmi (dengan QR Code Otentifikasi jika dipilih).
     *
     * @param  array<string, mixed>  $params
     */
    public static function generatePdf(array $params): Response
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $instansi = Instansi::getFirst();
        $isDigitalQr = ($params['metode_ttd'] ?? 'digital_qr') === 'digital_qr';
        $qrUrl = $params['qr_url'] ?? '';

        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public $showQrCode = false;

            public $qrValidationUrl = '';

            public $kodeVerifikasi = '';

            public function Header() {}

            public function Footer()
            {
                if ($this->showQrCode && ! empty($this->qrValidationUrl)) {
                    $this->SetY(-24);
                    $y = $this->GetY();
                    $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
                    // Tulis QR Code di margin kiri bawah persis seperti laporan lainnya di Sultan Banten
                    $this->write2DBarcode($this->qrValidationUrl, 'QRCODE,L', 15, $y, 16, 16, $style, 'N');

                    $this->SetXY(34, $y + 1);
                    $this->SetFont('helvetica', '', 6.8);
                    $this->SetTextColor(80, 80, 80);
                    $this->MultiCell(110, 3.2, "Dokumen resmi terotentifikasi Sistem SULTAN BANTEN\nKode Verifikasi: ".($this->kodeVerifikasi ?: '-')."\nPindai QR Code untuk memeriksa keaslian naskah laporan.", 0, 'L');
                }

                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 7.5);
                $this->SetTextColor(120, 120, 120);
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages().' | Laporan Sidang TPP Kanwil Ditjenpas Banten', 0, 0, 'R');
                $this->SetTextColor(0, 0, 0);
            }
        };

        $pdf->showQrCode = $isDigitalQr;
        $pdf->qrValidationUrl = $qrUrl;
        $pdf->kodeVerifikasi = $params['kode_verifikasi'] ?? '';

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor('Kanwil Kemenimipas Banten');
        $pdf->SetTitle('Laporan Pelaksanaan Sidang TPP - '.($params['upt_nama'] ?? 'Kanwil'));
        $pdf->SetMargins(18, 15, 18);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();

        // Render HTML Content
        $htmlContent = self::renderPdfHtml($params, $instansi);
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        $cleanUpt = preg_replace('/[^a-zA-Z0-9_-]/', '_', $params['upt_nama'] ?? 'UPT');
        $filename = 'Laporan_Sidang_TPP_Kanwil_'.$cleanUpt.'_'.date('Ymd_His').'.pdf';

        $output = $pdf->Output($filename, 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Render Template HTML untuk Word (.doc) atau Modal Pratinjau.
     *
     * @param  array<string, mixed>  $params
     */
    public static function renderHtmlTemplate(array $params, bool $forWord = false): string
    {
        $setting = AiSetting::getFirst();
        $instansi = Instansi::getFirst();

        $uptNama = $params['upt_nama'] ?? 'Lembaga Pemasyarakatan';
        $hariTanggal = $params['hari_tanggal_sidang'] ?? '-';
        $metode = $params['metode_sidang'] ?? 'secara virtual melalui media Zoom Meeting';
        $tempat = $params['tempat_sidang'] ?? $uptNama;

        $topikSidang = $params['topik_sidang'] ?? 'Pembahasan Usulan Hak Integrasi dan Remisi';
        $materiItems = (array) ($params['materi_sidang_items'] ?? []);

        $paragrafA1 = $params['paragraf_a1'] ?? "Pada hari {$hariTanggal}, Kantor Wilayah Direktorat Jenderal Pemasyarakatan Banten mengikuti pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) {$metode} di {$uptNama}.";
        $paragrafA2 = $params['paragraf_a2'] ?? "Sidang TPP membahas usulan {$topikSidang} terhadap narapidana yang telah diusulkan oleh {$uptNama}.";
        $paragrafA3 = $params['paragraf_a3'] ?? 'Perwakilan Kantor Wilayah hadir sebagai unsur pembina dan pengawas untuk melakukan verifikasi terhadap pemenuhan persyaratan substantif maupun administratif sesuai ketentuan peraturan perundang-undangan.';

        // Pastikan ada kalimat penghubung alami jika terdapat butir rincian materi sidang
        if (! empty($materiItems)) {
            $trimmedA3 = trim($paragrafA3);
            $hasConnector = (bool) preg_match('/(?:sebagai berikut|meliputi|adalah|yaitu)\s*:?\s*$/i', $trimmedA3);
            if (! $hasConnector) {
                $trimmedA3 = rtrim($trimmedA3, " \t\n\r\0\x0B.");
                $trimmedA3 .= '. Adapun pokok materi dan rincian kuota usulan narapidana yang dibahas serta ditelaah dalam forum sidang TPP ini meliputi sebagai berikut:';
            } elseif (! str_ends_with($trimmedA3, ':')) {
                $trimmedA3 .= ':';
            }
            $paragrafA3 = $trimmedA3;
        }

        $paragrafA4 = $params['paragraf_a4'] ?? 'Perwakilan Kantor Wilayah melakukan pencermatan terhadap kelengkapan dokumen usulan, data registrasi narapidana, perhitungan masa pidana, laporan perkembangan pembinaan, penilaian risiko, serta dokumen pendukung lainnya yang dipersyaratkan dalam Sistem Database Pemasyarakatan (SDP).';

        $paragrafB1 = $params['paragraf_b1'] ?? "Berdasarkan hasil pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) {$uptNama} pada tanggal {$hariTanggal}, Tim Kantor Wilayah melakukan verifikasi terhadap usulan materi sidang.";
        $paragrafB2Poin = (array) ($params['paragraf_b2_poin'] ?? [
            'Seluruh Narapidana yang dibahas telah memenuhi persyaratan substantif sebagaimana diatur dalam ketentuan peraturan perundang-undangan.',
            'Seluruh persyaratan administratif telah lengkap dan sesuai dengan ketentuan serta telah tersedia dalam Sistem Database Pemasyarakatan (SDP).',
            'Perhitungan masa pidana, tanggal 2/3 masa pidana, dan tanggal ekspirasi telah sesuai dengan data register narapidana.',
            'Dengan terpenuhinya persyaratan substantif dan administratif, usulan tersebut direkomendasikan untuk diproses lebih lanjut sesuai mekanisme yang berlaku.',
        ]);

        $simpulanPoin = (array) ($params['simpulan_poin'] ?? [
            'Pelaksanaan Sidang Tim Pengamat Pemasyarakatan berjalan dengan tertib, lancar, dan sesuai dengan ketentuan yang berlaku.',
            'Pembahasan dilakukan secara objektif dengan mempertimbangkan aspek administratif, substantif, keamanan, serta hasil pembinaan terhadap masing-masing narapidana.',
        ]);

        $saranPoin = (array) ($params['saran_poin'] ?? [
            "{$uptNama} agar tetap meningkatkan ketelitian dalam melakukan verifikasi administrasi sebelum usulan diajukan kepada Kantor Wilayah.",
            'Seluruh dokumen pendukung agar dipastikan telah lengkap, valid, dan diunggah ke dalam Sistem Database Pemasyarakatan (SDP) sebelum proses pengusulan dilanjutkan.',
            'Jika ada kendala, agar segera berkoordinasi dengan Kantor Wilayah.',
            'Monitoring terhadap pelaksanaan usulan agar terus dilakukan sesuai ketentuan peraturan perundang-undangan guna menjamin tercapainya tujuan Sistem Pemasyarakatan.',
        ]);

        $penutup = $params['penutup'] ?? 'Demikian laporan pelaksanaan Sidang Tim Pengamat Pemasyarakatan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan tugas serta sebagai bahan informasi bagi pimpinan dalam rangka pembinaan dan pengawasan penyelenggaraan tugas di lingkungan Pemasyarakatan.';

        $pengawasNama = $params['pengawas_nama'] ?? 'Gunawan Wibisono';
        $pengawasJabatan = $params['pengawas_jabatan'] ?? 'Tim Pembinaan Narapidana dan Anak Binaan';

        $penandatanganNama = $params['penandatangan_nama'] ?? ($setting->penandatangan_nama ?? 'Surya Dharma');
        $penandatanganJabatan = $params['penandatangan_jabatan'] ?? 'Kepala Bidang Pelayanan dan Pembinaan';
        $penandatanganNip = $params['penandatangan_nip'] ?? '';
        $tampilkanNip = ! empty($params['tampilkan_nip']);

        $kotaTanggal = ($params['kota'] ?? 'Serang').', '.($params['tanggal_laporan'] ?? date('d F Y'));
        $metodeTtd = $params['metode_ttd'] ?? 'digital_qr';
        $qrUrl = $params['qr_url'] ?? '';

        // Format Butir Materi Sidang
        $materiHtml = '';
        if (! empty($materiItems)) {
            $materiHtml .= '<ul style="margin-top:2pt;margin-bottom:6pt;padding-left:24pt;">';
            foreach ($materiItems as $m) {
                $materiHtml .= '<li style="line-height:150%;">'.htmlspecialchars((string) $m).'</li>';
            }
            $materiHtml .= '</ul>';
        }

        // Format Poin B2
        $poinB2Html = '<ol style="margin-top:2pt;margin-bottom:6pt;padding-left:20pt;">';
        foreach ($paragrafB2Poin as $pb) {
            $poinB2Html .= '<li style="line-height:150%;">'.htmlspecialchars((string) $pb).'</li>';
        }
        $poinB2Html .= '</ol>';

        // Format Simpulan
        $simpulanHtml = '<ol type="a" style="margin-top:2pt;margin-bottom:6pt;padding-left:20pt;">';
        foreach ($simpulanPoin as $sp) {
            $simpulanHtml .= '<li style="line-height:150%;">'.htmlspecialchars((string) $sp).'</li>';
        }
        $simpulanHtml .= '</ol>';

        // Format Saran
        $saranHtml = '<ol type="a" style="margin-top:2pt;margin-bottom:6pt;padding-left:20pt;">';
        foreach ($saranPoin as $sr) {
            $saranHtml .= '<li style="line-height:150%;">'.htmlspecialchars((string) $sr).'</li>';
        }
        $saranHtml .= '</ol>';

        // Dokumentasi 2 Foto
        $fotoDokumentasi = (array) ($params['foto_dokumentasi'] ?? []);
        $foto1 = $fotoDokumentasi[0] ?? null;
        $foto2 = $fotoDokumentasi[1] ?? null;

        $hasFoto1 = ! empty($foto1) && file_exists(public_path($foto1));
        $hasFoto2 = ! empty($foto2) && file_exists(public_path($foto2));

        $dokumentasiHtml = '';
        if ($hasFoto1 || $hasFoto2) {
            $dokumentasiHtml .= '<table width="100%" border="0" style="margin-top:6pt;margin-bottom:12pt;border-collapse:collapse;"><tr>';
            if ($hasFoto1) {
                $b64_1 = base64_encode(file_get_contents(public_path($foto1)));
                $dokumentasiHtml .= '<td width="50%" align="center" valign="top" style="padding:4pt;">
                    <img src="data:image/jpeg;base64,'.$b64_1.'" style="width:230pt;height:auto;border:1px solid #ccc;" /><br>
                    <span style="font-size:9pt;font-style:italic;color:#555;">Dokumentasi 1: Pelaksanaan Sidang TPP</span>
                </td>';
            } else {
                $dokumentasiHtml .= '<td width="50%"></td>';
            }
            if ($hasFoto2) {
                $b64_2 = base64_encode(file_get_contents(public_path($foto2)));
                $dokumentasiHtml .= '<td width="50%" align="center" valign="top" style="padding:4pt;">
                    <img src="data:image/jpeg;base64,'.$b64_2.'" style="width:230pt;height:auto;border:1px solid #ccc;" /><br>
                    <span style="font-size:9pt;font-style:italic;color:#555;">Dokumentasi 2: Verifikasi &amp; Pengawasan Sidang</span>
                </td>';
            } else {
                $dokumentasiHtml .= '<td width="50%"></td>';
            }
            $dokumentasiHtml .= '</tr></table>';
        } else {
            $dokumentasiHtml = '<p style="font-size:10pt;font-style:italic;color:#666;margin-left:20pt;">(Dokumentasi pelaksanaan sidang terlampir dalam berkas Berita Acara UPT)</p>';
        }

        // Kop Surat
        $logoBase64 = '';
        if (! empty($instansi->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoBase64 = base64_encode(file_get_contents(public_path($instansi->logo_kop)));
        }

        $kopHtml = "
        <table width='100%' style='border-bottom:3px double #000;margin-bottom:14pt;padding-bottom:5pt;'>
            <tr>
                <td width='15%' align='center' valign='middle'>
                    ".(! empty($logoBase64) ? "<img src='data:image/png;base64,{$logoBase64}' style='width:65pt;height:auto;' />" : '')."
                </td>
                <td width='85%' align='center' valign='middle' style='line-height:1.2;'>
                    <div style='font-size:12pt;font-weight:bold;text-transform:uppercase;'>KEMENTERIAN HUKUM DAN HAM RI / KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN</div>
                    <div style='font-size:13pt;font-weight:bold;text-transform:uppercase;'>KANTOR WILAYAH BANTEN</div>
                    <div style='font-size:9pt;font-family:Arial,sans-serif;margin-top:2pt;'>{$instansi->alamat}</div>
                </td>
            </tr>
        </table>";

        // Blok Tanda Tangan (Standar Resmi Tanpa QR Menempel di Area TTD)
        $ttdPengawasHtml = "
            <b>Perwakilan Kantor Wilayah</b><br>
            {$pengawasJabatan}<br><br><br><br><br>
            <u><b>{$pengawasNama}</b></u>
        ";

        $nipText = ($tampilkanNip && ! empty($penandatanganNip)) ? "<br><span style='font-size:10pt;'>NIP. {$penandatanganNip}</span>" : '';

        $ttdKakanwilHtml = "
            {$kotaTanggal}<br>
            <b>{$penandatanganJabatan}</b><br><br><br><br><br>
            <u><b>{$penandatanganNama}</b></u>{$nipText}
        ";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pelaksanaan Sidang TPP Kanwil</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        h2 {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            margin-top: 10pt;
            margin-bottom: 15pt;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        p {
            margin: 0 0 6pt 0;
            text-align: justify;
        }
        .section-title {
            font-weight: bold;
            font-size: 11pt;
            margin-top: 10pt;
            margin-bottom: 4pt;
        }
    </style>
</head>
<body>
    {$kopHtml}

    <h2>LAPORAN PELAKSANAAN SIDANG TPP</h2>

    <div class="section-title">A. KEGIATAN YANG DILAKSANAKAN</div>
    <p style="text-indent: 24pt;">{$paragrafA1}</p>
    <p style="text-indent: 24pt;">{$paragrafA2}</p>
    <p style="text-indent: 24pt;">{$paragrafA3}</p>
    {$materiHtml}
    <p style="text-indent: 24pt;">{$paragrafA4}</p>

    <div class="section-title" style="margin-top: 12pt;">B. HASIL YANG DICAPAI</div>
    <p style="text-indent: 24pt;">{$paragrafB1}</p>
    <p style="text-indent: 24pt;">Berdasarkan hasil pencermatan terhadap seluruh dokumen usulan, Perwakilan Kantor Wilayah berpendapat bahwa:</p>
    {$poinB2Html}

    <div class="section-title" style="margin-top: 12pt;">C. SIMPULAN DAN SARAN</div>
    <p style="font-weight: bold; margin-bottom: 2pt;">Simpulan :</p>
    {$simpulanHtml}
    <p style="font-weight: bold; margin-bottom: 2pt; margin-top: 6pt;">Saran :</p>
    {$saranHtml}

    <div class="section-title" style="margin-top: 12pt;">D. PENUTUP</div>
    <p style="text-indent: 24pt;">{$penutup}</p>

    <div class="section-title" style="margin-top: 12pt;">E. DOKUMENTASI</div>
    {$dokumentasiHtml}

    <br>
    <table width="100%" border="0" style="margin-top: 15pt; border-collapse: collapse;">
        <tr>
            <td width="55%" valign="top" style="line-height: 1.4;">
                {$ttdPengawasHtml}
            </td>
            <td width="45%" valign="top" style="line-height: 1.4; padding-left: 10pt;">
                {$ttdKakanwilHtml}
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Render HTML khusus untuk TCPDF.
     */
    protected static function renderPdfHtml(array $params, Instansi $instansi): string
    {
        $setting = AiSetting::getFirst();

        $uptNama = $params['upt_nama'] ?? 'Lembaga Pemasyarakatan';
        $hariTanggal = $params['hari_tanggal_sidang'] ?? '-';
        $metode = $params['metode_sidang'] ?? 'secara virtual melalui media Zoom Meeting';
        $topikSidang = $params['topik_sidang'] ?? 'Pembahasan Usulan Hak Integrasi dan Remisi';
        $materiItems = (array) ($params['materi_sidang_items'] ?? []);

        $paragrafA1 = $params['paragraf_a1'] ?? "Pada hari {$hariTanggal}, Kantor Wilayah Direktorat Jenderal Pemasyarakatan Banten mengikuti pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) {$metode} di {$uptNama}.";
        $paragrafA2 = $params['paragraf_a2'] ?? "Sidang TPP membahas usulan {$topikSidang} terhadap narapidana yang telah diusulkan oleh {$uptNama}.";
        $paragrafA3 = $params['paragraf_a3'] ?? 'Perwakilan Kantor Wilayah hadir sebagai unsur pembina dan pengawas untuk melakukan verifikasi terhadap pemenuhan persyaratan substantif maupun administratif sesuai ketentuan peraturan perundang-undangan.';

        // Pastikan ada kalimat penghubung alami jika terdapat butir rincian materi sidang
        if (! empty($materiItems)) {
            $trimmedA3 = trim($paragrafA3);
            $hasConnector = (bool) preg_match('/(?:sebagai berikut|meliputi|adalah|yaitu)\s*:?\s*$/i', $trimmedA3);
            if (! $hasConnector) {
                $trimmedA3 = rtrim($trimmedA3, " \t\n\r\0\x0B.");
                $trimmedA3 .= '. Adapun pokok materi dan rincian kuota usulan narapidana yang dibahas serta ditelaah dalam forum sidang TPP ini meliputi sebagai berikut:';
            } elseif (! str_ends_with($trimmedA3, ':')) {
                $trimmedA3 .= ':';
            }
            $paragrafA3 = $trimmedA3;
        }

        $paragrafA4 = $params['paragraf_a4'] ?? 'Perwakilan Kantor Wilayah melakukan pencermatan terhadap kelengkapan dokumen usulan, data registrasi narapidana, perhitungan masa pidana, laporan perkembangan pembinaan, penilaian risiko, serta dokumen pendukung lainnya yang dipersyaratkan dalam Sistem Database Pemasyarakatan (SDP).';

        $paragrafB1 = $params['paragraf_b1'] ?? "Berdasarkan hasil pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) {$uptNama} pada tanggal {$hariTanggal}, Tim Kantor Wilayah melakukan verifikasi terhadap usulan materi sidang.";
        $paragrafB2Poin = (array) ($params['paragraf_b2_poin'] ?? [
            'Seluruh Narapidana yang dibahas telah memenuhi persyaratan substantif sebagaimana diatur dalam ketentuan peraturan perundang-undangan.',
            'Seluruh persyaratan administratif telah lengkap dan sesuai dengan ketentuan serta telah tersedia dalam Sistem Database Pemasyarakatan (SDP).',
            'Perhitungan masa pidana, tanggal 2/3 masa pidana, dan tanggal ekspirasi telah sesuai dengan data register narapidana.',
            'Dengan terpenuhinya persyaratan substantif dan administratif, usulan tersebut direkomendasikan untuk diproses lebih lanjut sesuai mekanisme yang berlaku.',
        ]);

        $simpulanPoin = (array) ($params['simpulan_poin'] ?? [
            'Pelaksanaan Sidang Tim Pengamat Pemasyarakatan berjalan dengan tertib, lancar, dan sesuai dengan ketentuan yang berlaku.',
            'Pembahasan dilakukan secara objektif dengan mempertimbangkan aspek administratif, substantif, keamanan, serta hasil pembinaan terhadap masing-masing narapidana.',
        ]);

        $saranPoin = (array) ($params['saran_poin'] ?? [
            "{$uptNama} agar tetap meningkatkan ketelitian dalam melakukan verifikasi administrasi sebelum usulan diajukan kepada Kantor Wilayah.",
            'Seluruh dokumen pendukung agar dipastikan telah lengkap, valid, dan diunggah ke dalam Sistem Database Pemasyarakatan (SDP) sebelum proses pengusulan dilanjutkan.',
            'Jika ada kendala, agar segera berkoordinasi dengan Kantor Wilayah.',
            'Monitoring terhadap pelaksanaan usulan agar terus dilakukan sesuai ketentuan peraturan perundang-undangan guna menjamin tercapainya tujuan Sistem Pemasyarakatan.',
        ]);

        $penutup = $params['penutup'] ?? 'Demikian laporan pelaksanaan Sidang Tim Pengamat Pemasyarakatan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan tugas serta sebagai bahan informasi bagi pimpinan dalam rangka pembinaan dan pengawasan penyelenggaraan tugas di lingkungan Pemasyarakatan.';

        $pengawasNama = $params['pengawas_nama'] ?? 'Gunawan Wibisono';
        $pengawasJabatan = $params['pengawas_jabatan'] ?? 'Tim Pembinaan Narapidana dan Anak Binaan';

        $penandatanganNama = $params['penandatangan_nama'] ?? ($setting->penandatangan_nama ?? 'Surya Dharma');
        $penandatanganJabatan = $params['penandatangan_jabatan'] ?? 'Kepala Bidang Pelayanan dan Pembinaan';
        $penandatanganNip = $params['penandatangan_nip'] ?? '';
        $tampilkanNip = ! empty($params['tampilkan_nip']);

        $kotaTanggal = ($params['kota'] ?? 'Serang').', '.($params['tanggal_laporan'] ?? date('d F Y'));
        $metodeTtd = $params['metode_ttd'] ?? 'digital_qr';

        $materiHtml = '';
        if (! empty($materiItems)) {
            $materiHtml .= '<ul>';
            foreach ($materiItems as $m) {
                $materiHtml .= '<li>'.htmlspecialchars((string) $m).'</li>';
            }
            $materiHtml .= '</ul>';
        }

        $poinB2Html = '<ol>';
        foreach ($paragrafB2Poin as $pb) {
            $poinB2Html .= '<li>'.htmlspecialchars((string) $pb).'</li>';
        }
        $poinB2Html .= '</ol>';

        $simpulanHtml = '<ol>';
        foreach ($simpulanPoin as $sp) {
            $simpulanHtml .= '<li>'.htmlspecialchars((string) $sp).'</li>';
        }
        $simpulanHtml .= '</ol>';

        $saranHtml = '<ol>';
        foreach ($saranPoin as $sr) {
            $saranHtml .= '<li>'.htmlspecialchars((string) $sr).'</li>';
        }
        $saranHtml .= '</ol>';

        // Dokumentasi 2 Foto
        $fotoDokumentasi = (array) ($params['foto_dokumentasi'] ?? []);
        $foto1 = $fotoDokumentasi[0] ?? null;
        $foto2 = $fotoDokumentasi[1] ?? null;

        $hasFoto1 = ! empty($foto1) && file_exists(public_path($foto1));
        $hasFoto2 = ! empty($foto2) && file_exists(public_path($foto2));

        $dokumentasiPdfHtml = '';
        if ($hasFoto1 || $hasFoto2) {
            $dokumentasiPdfHtml .= '<table width="100%" border="0" cellpadding="4" style="margin-top: 4pt; margin-bottom: 8pt;"><tr>';
            if ($hasFoto1) {
                $p1 = public_path($foto1);
                $dokumentasiPdfHtml .= '<td width="50%" align="center" style="border: 1px solid #aaa; padding: 4px;">
                    <img src="'.$p1.'" width="230" height="145" /><br>
                    <span style="font-size: 8pt; color: #444;"><i>Dokumentasi 1: Pelaksanaan Sidang TPP</i></span>
                </td>';
            } else {
                $dokumentasiPdfHtml .= '<td width="50%"></td>';
            }
            if ($hasFoto2) {
                $p2 = public_path($foto2);
                $dokumentasiPdfHtml .= '<td width="50%" align="center" style="border: 1px solid #aaa; padding: 4px;">
                    <img src="'.$p2.'" width="230" height="145" /><br>
                    <span style="font-size: 8pt; color: #444;"><i>Dokumentasi 2: Verifikasi &amp; Pengawasan Sidang</i></span>
                </td>';
            } else {
                $dokumentasiPdfHtml .= '<td width="50%"></td>';
            }
            $dokumentasiPdfHtml .= '</tr></table>';
        } else {
            $dokumentasiPdfHtml = '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>(Dokumentasi pelaksanaan sidang terlampir pada berkas Berita Acara UPT)</i></p>';
        }

        $nipText = ($tampilkanNip && ! empty($penandatanganNip)) ? "<br>NIP. {$penandatanganNip}" : '';

        return <<<HTML
<style>
    h1 { font-size: 13pt; font-weight: bold; text-align: center; margin-bottom: 12pt; }
    h2 { font-size: 11pt; font-weight: bold; margin-top: 8pt; margin-bottom: 3pt; }
    p { font-size: 10.5pt; text-align: justify; line-height: 1.4; margin-bottom: 4pt; }
    li { font-size: 10.5pt; line-height: 1.4; }
</style>

<h1>LAPORAN PELAKSANAAN SIDANG TPP</h1>

<h2>A. KEGIATAN YANG DILAKSANAKAN</h2>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$paragrafA1}</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$paragrafA2}</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$paragrafA3}</p>
{$materiHtml}
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$paragrafA4}</p>

<h2>B. HASIL YANG DICAPAI</h2>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$paragrafB1}</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Berdasarkan hasil pencermatan terhadap seluruh dokumen usulan, Perwakilan Kantor Wilayah berpendapat bahwa:</p>
{$poinB2Html}

<h2>C. SIMPULAN DAN SARAN</h2>
<p><b>Simpulan :</b></p>
{$simpulanHtml}
<p><b>Saran :</b></p>
{$saranHtml}

<h2>D. PENUTUP</h2>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$penutup}</p>

<h2>E. DOKUMENTASI KEGIATAN</h2>
{$dokumentasiPdfHtml}

<br><br>
<table width="100%" border="0" cellpadding="2">
    <tr>
        <td width="55%" align="left">
            <b>Perwakilan Kantor Wilayah</b><br>
            {$pengawasJabatan}<br><br><br><br><br>
            <u><b>{$pengawasNama}</b></u>
        </td>
        <td width="45%" align="left">
            {$kotaTanggal}<br>
            <b>{$penandatanganJabatan}</b><br><br><br><br><br>
            <u><b>{$penandatanganNama}</b></u>{$nipText}
        </td>
    </tr>
</table>
HTML;
    }
}
