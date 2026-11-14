<?php

namespace App\Services;

use App\Models\Instansi;
use App\Models\SidangTppKanwil;
use Illuminate\Http\Response;

class TppKanwilDokumenService
{
    /**
     * Generate berkas Microsoft Word (.doc) untuk Berita Acara Sidang TPP Kanwil Siap Cetak.
     */
    public static function generateBeritaAcaraWord(SidangTppKanwil $sidang): Response
    {
        $html = self::renderBeritaAcaraHtml($sidang, true);

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_berita_acara ?: $sidang->nomor_sidang);
        $filename = 'Berita_Acara_Sidang_TPP_Kanwil_'.$cleanNomor.'_'.date('Ymd_His').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Generate berkas PDF Resmi Berita Acara & Lembar Disposisi Kakanwil Siap Cetak.
     */
    public static function generateBeritaAcaraPdf(SidangTppKanwil $sidang): Response
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public function Header() {}

            public function Footer()
            {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 7.5);
                $this->SetTextColor(120, 120, 120);
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages().' | Berita Acara Sidang TPP Kantor Wilayah Banten', 0, 0, 'R');
            }
        };

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor('Kanwil Ditjenpas Banten');
        $pdf->SetTitle('Berita Acara Sidang TPP Kanwil - '.($sidang->nomor_sidang));
        $pdf->SetMargins(16, 14, 16);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $htmlContent = self::renderBeritaAcaraHtml($sidang, false);
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_berita_acara ?: $sidang->nomor_sidang);
        $filename = 'Berita_Acara_Sidang_TPP_Kanwil_'.$cleanNomor.'_'.date('Ymd_His').'.pdf';

        $output = $pdf->Output($filename, 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Generate berkas Microsoft Word (.doc) untuk Nota Dinas Undangan Sidang TPP Kanwil.
     */
    public static function generateNotaDinasWord(SidangTppKanwil $sidang): Response
    {
        $html = self::renderNotaDinasHtml($sidang, true);

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_nota_dinas ?: $sidang->nomor_sidang);
        $filename = 'Nota_Dinas_Undangan_TPP_Kanwil_'.$cleanNomor.'_'.date('Ymd_His').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Generate berkas PDF Nota Dinas Undangan Sidang TPP Kanwil.
     */
    public static function generateNotaDinasPdf(SidangTppKanwil $sidang): Response
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public function Header() {}

            public function Footer()
            {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 7.5);
                $this->SetTextColor(120, 120, 120);
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages().' | Nota Dinas Sidang TPP Kantor Wilayah Banten', 0, 0, 'R');
            }
        };

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor('Kanwil Ditjenpas Banten');
        $pdf->SetTitle('Nota Dinas Undangan Sidang TPP Kanwil - '.($sidang->nomor_sidang));
        $pdf->SetMargins(18, 15, 18);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $htmlContent = self::renderNotaDinasHtml($sidang, false);
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_nota_dinas ?: $sidang->nomor_sidang);
        $filename = 'Nota_Dinas_Undangan_TPP_Kanwil_'.$cleanNomor.'_'.date('Ymd_His').'.pdf';

        $output = $pdf->Output($filename, 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Render Template HTML untuk Berita Acara & Lembar Disposisi Fisik Siap Cetak.
     */
    public static function renderBeritaAcaraHtml(SidangTppKanwil $sidang, bool $forWord = false): string
    {
        $instansi = Instansi::getFirst();
        $anggotaList = $sidang->anggota()->get();

        $hariTanggal = $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-';
        $jamMulai = substr((string) $sidang->jam_mulai, 0, 5);
        $jamSelesai = ! empty($sidang->jam_selesai) ? substr((string) $sidang->jam_selesai, 0, 5) : 'Selesai';
        $nomorBa = $sidang->nomor_berita_acara ?: ('BA-TPP-KW/'.($sidang->tanggal_sidang ? $sidang->tanggal_sidang->format('Y/m') : date('Y/m')).'/'.str_pad($sidang->id, 3, '0', STR_PAD_LEFT));

        $metodeLabel = match ($sidang->metode) {
            'daring' => 'Secara Virtual / Daring (Media Telekonferensi Zoom/Meet)',
            'hybrid' => 'Secara Hybrid (Tatap Muka di Ruang Rapat Kanwil dan Virtual Daring)',
            default => 'Tatap Muka Langsung di Ruang Rapat Kantor Wilayah',
        };

        // Kop Surat
        $logoBase64 = '';
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoBase64 = base64_encode(file_get_contents(public_path($instansi->logo_kop)));
        }

        $kopHtml = "
        <table width='100%' style='border-bottom:3px double #000;margin-bottom:12pt;padding-bottom:4pt;'>
            <tr>
                <td width='15%' align='center' valign='middle'>
                    ".(! empty($logoBase64) ? "<img src='data:image/png;base64,{$logoBase64}' style='width:62pt;height:auto;' />" : '')."
                </td>
                <td width='85%' align='center' valign='middle' style='line-height:1.2;'>
                    <div style='font-size:11.5pt;font-weight:bold;text-transform:uppercase;'>KEMENTERIAN HUKUM DAN HAK ASASI MANUSIA RI / KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN</div>
                    <div style='font-size:12.5pt;font-weight:bold;text-transform:uppercase;'>KANTOR WILAYAH BANTEN</div>
                    <div style='font-size:13pt;font-weight:bold;text-transform:uppercase;color:#1e3a8a;'>DIVISI PEMASYARAKATAN</div>
                    <div style='font-size:8.5pt;font-family:Arial,sans-serif;margin-top:2pt;'>".($instansi?->alamat ?? 'Jl. KH. Fatah Hasan No. 88, Serang, Banten').'</div>
                </td>
            </tr>
        </table>';

        // Susun Tabel Tanda Tangan Anggota
        $barisTtdHtml = '';
        $no = 1;
        foreach ($anggotaList as $a) {
            $nipText = ! empty($a->nip) ? '<br><small>NIP. '.htmlspecialchars($a->nip).'</small>' : '';
            $statusKehadiranText = match ($a->status_kehadiran) {
                'hadir_luring' => 'Hadir Luring',
                'hadir_daring' => 'Hadir Daring',
                'izin' => 'Izin',
                'sakit' => 'Sakit',
                'tidak_hadir' => 'Tidak Hadir',
                default => 'Belum Konfirmasi',
            };

            $statusPendapatText = match ($a->status_pendapat) {
                'setuju' => 'Setuju',
                'setuju_catatan' => 'Setuju dg Catatan',
                'tolak' => 'Tolak',
                'tunda' => 'Tunda',
                default => '-',
            };

            $catatanText = ! empty($a->catatan_pendapat) ? '<br><em>"'.htmlspecialchars($a->catatan_pendapat).'"</em>' : '';

            $barisTtdHtml .= "
            <tr>
                <td align='center' valign='middle' style='padding:5pt 3pt;border:1px solid #000;'>{$no}</td>
                <td valign='middle' style='padding:5pt 4pt;border:1px solid #000;'>
                    <b>".htmlspecialchars($a->nama)."</b>{$nipText}
                    <div style='font-size:8.5pt;color:#333;'>".htmlspecialchars($a->jabatan)."</div>
                </td>
                <td align='center' valign='middle' style='padding:5pt 3pt;border:1px solid #000;'>
                    <b>".htmlspecialchars($a->peran_sidang)."</b>
                </td>
                <td align='center' valign='middle' style='padding:5pt 3pt;border:1px solid #000;font-size:9pt;'>
                    {$statusKehadiranText}
                </td>
                <td valign='middle' style='padding:5pt 4pt;border:1px solid #000;font-size:9pt;'>
                    <b>{$statusPendapatText}</b>{$catatanText}
                </td>
                <td align='center' valign='bottom' style='padding:18pt 4pt 6pt 4pt;border:1px solid #000;min-width:70pt;'>
                    <div style='font-size:8pt;color:#777;margin-bottom:2pt;'>({$no})</div>
                    <div style='border-bottom:1px dotted #888;height:18pt;'></div>
                </td>
            </tr>";
            $no++;
        }

        $kesimpulan = ! empty($sidang->kesimpulan_sidang)
            ? htmlspecialchars($sidang->kesimpulan_sidang)
            : 'Seluruh tim Sidang TPP Kantor Wilayah telah mencermati berkas dan pertimbangan teknis substantif. Rekomendasi sidang disepakati untuk diajukan kepada Kepala Kantor Wilayah guna memperoleh persetujuan dan arahan lebih lanjut.';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Berita Acara Sidang TPP Kantor Wilayah</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 10pt; line-height: 1.35; color: #000; }
        .judul-ba { text-align: center; font-size: 12pt; font-weight: bold; margin-bottom: 2pt; text-transform: uppercase; }
        .nomor-ba { text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 12pt; }
        table.tbl-data { width: 100%; border-collapse: collapse; margin-top: 6pt; margin-bottom: 10pt; }
        table.tbl-data th { background-color: #f1f5f9; font-weight: bold; text-align: center; padding: 5pt; border: 1px solid #000; font-size: 9pt; }
        .kotak-disposisi { border: 2px solid #000; padding: 8pt; margin-top: 14pt; page-break-inside: avoid; }
    </style>
</head>
<body>
    {$kopHtml}

    <div class="judul-ba">BERITA ACARA SIDANG TIM PENGAMAT PEMASYARAKATAN (TPP)</div>
    <div class="judul-ba" style="font-size:11pt;">KANTOR WILAYAH KEMENTERIAN HUKUM DAN HAM BANTEN</div>
    <div class="nomor-ba">NOMOR : {$nomorBa}</div>

    <p style="text-align:justify;margin-bottom:8pt;">
        Pada hari ini, <b>{$hariTanggal}</b>, bertempat di <b>{$sidang->tempat}</b>, telah dilaksanakan <b>Sidang Tim Pengamat Pemasyarakatan (TPP) Tingkat Kantor Wilayah</b> dengan rincian pelaksanaan sebagai berikut:
    </p>

    <table width="100%" border="0" style="margin-left:10pt;margin-bottom:10pt;line-height:1.4;">
        <tr>
            <td width="24%"><b>Waktu Pelaksanaan</b></td>
            <td width="3%">:</td>
            <td width="73%">Pukul {$jamMulai} s/d {$jamSelesai} WIB</td>
        </tr>
        <tr>
            <td><b>Metode Pelaksanaan</b></td>
            <td>:</td>
            <td>{$metodeLabel}</td>
        </tr>
        <tr>
            <td><b>Nomor Nota Dinas</b></td>
            <td>:</td>
            <td>".($sidang->nomor_nota_dinas ?: '-')."</td>
        </tr>
        <tr>
            <td><b>Kategori Agenda</b></td>
            <td>:</td>
            <td><b>".htmlspecialchars($sidang->agenda_kategori)."</b></td>
        </tr>
        <tr>
            <td valign="top"><b>Judul Agenda Sidang</b></td>
            <td valign="top">:</td>
            <td valign="top"><b>".htmlspecialchars($sidang->agenda_judul)."</b></td>
        </tr>
        <tr>
            <td valign="top"><b>Uraian Agenda</b></td>
            <td valign="top">:</td>
            <td valign="top" style="text-align:justify;">".nl2br(htmlspecialchars($sidang->agenda_deskripsi ?: '-')).'</td>
        </tr>
    </table>

    <div style="font-weight:bold;margin-bottom:4pt;">I. REKAPITULASI DELIBERASI &amp; REKOMENDASI SIDANG TPP KANWIL</div>
    <table width="100%" border="0" style="margin-left:10pt;margin-bottom:8pt;">
        <tr>
            <td width="35%">Total Anggota Tim Ditugaskan</td>
            <td width="3%">:</td>
            <td width="62%"><b>{$sidang->total_anggota} Orang</b></td>
        </tr>
        <tr>
            <td>Total Anggota Hadir</td>
            <td>:</td>
            <td><b>{$sidang->total_hadir} Orang</b></td>
        </tr>
        <tr>
            <td>Hasil Sikap &amp; Pendapat Tim</td>
            <td>:</td>
            <td>
                <b>{$sidang->total_setuju}</b> Setuju &bull;
                <b>{$sidang->total_setuju_catatan}</b> Setuju dg Catatan &bull;
                <b>{$sidang->total_tolak}</b> Tolak &bull;
                <b>{$sidang->total_tunda}</b> Tunda
            </td>
        </tr>
        <tr>
            <td valign="top">Kesimpulan / Rekomendasi</td>
            <td valign="top">:</td>
            <td valign="top" style="text-align:justify;">{$kesimpulan}</td>
        </tr>
    </table>

    <div style="font-weight:bold;margin-top:10pt;margin-bottom:4pt;">II. DAFTAR HADIR &amp; LEMBAR TANDA TANGAN TIM SIDANG TPP KANWIL</div>
    <table class="tbl-data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="32%">Nama / NIP / Jabatan</th>
                <th width="14%">Kedudukan</th>
                <th width="13%">Presensi</th>
                <th width="20%">Pendapat &amp; Catatan</th>
                <th width="16%">Tanda Tangan Manual</th>
            </tr>
        </thead>
        <tbody>
            {$barisTtdHtml}
        </tbody>
    </table>

    <!-- LEMBAR DISPOSISI / TANGGAPAN KEPALA KANTOR WILAYAH (PEMBERKASAN FISIK) -->
    <div class="kotak-disposisi">
        <table width="100%" border="0">
            <tr>
                <td width="65%" valign="top">
                    <div style="font-size:10.5pt;font-weight:bold;text-transform:uppercase;margin-bottom:4pt;">
                        TANGGAPAN / PETUNJUK KEPALA KANTOR WILAYAH
                    </div>
                    <div style="margin-bottom:6pt;font-size:9.5pt;">
                        [ &nbsp; ] &nbsp; <b>DISETUJUI</b><br>
                        [ &nbsp; ] &nbsp; <b>DISETUJUI BERSYARAT / DENGAN CATATAN</b><br>
                        [ &nbsp; ] &nbsp; <b>PERLU PENDALAMAN / DIBAHAS KEMBALI</b><br>
                        [ &nbsp; ] &nbsp; <b>DITOLAK</b>
                    </div>
                    <div style="font-size:9pt;margin-bottom:2pt;"><b>Catatan / Instruksi Pimpinan:</b></div>
                    <div style="border-bottom:1px dotted #000;height:16pt;margin-bottom:4pt;"></div>
                    <div style="border-bottom:1px dotted #000;height:16pt;margin-bottom:4pt;"></div>
                    <div style="border-bottom:1px dotted #000;height:16pt;"></div>
                </td>
                <td width="35%" align="center" valign="top" style="padding-left:10pt;">
                    <div style="font-size:9pt;">Serang, '.(date('d F Y')).'</div>
                    <div style="font-size:9.5pt;font-weight:bold;margin-top:2pt;line-height:1.2;">
                        KEPALA KANTOR WILAYAH<br>KEMENTERIAN HUKUM DAN HAM BANTEN
                    </div>
                    <br><br><br><br>
                    <div style="font-size:9.5pt;font-weight:bold;text-decoration:underline;">
                        '.htmlspecialchars($sidang->kakanwil_nama ?: ($instansi?->nama_kakanwil ?? 'DODOT ADIKOESWANTO, S.H., M.Si.')).'
                    </div>
                    <div style="font-size:8.5pt;">NIP. '.htmlspecialchars($sidang->kakanwil_nip ?: ($instansi?->nip_kakanwil ?? '196811201991031001')).'</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render Template HTML untuk Nota Dinas Undangan Sidang TPP Kanwil Siap Cetak.
     */
    public static function renderNotaDinasHtml(SidangTppKanwil $sidang, bool $forWord = false): string
    {
        $instansi = Instansi::getFirst();
        $anggotaList = $sidang->anggota()->get();

        $hariTanggal = $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-';
        $tanggalNota = $sidang->tanggal_nota_dinas ? $sidang->tanggal_nota_dinas->translatedFormat('d F Y') : date('d F Y');
        $jamMulai = substr((string) $sidang->jam_mulai, 0, 5);
        $jamSelesai = ! empty($sidang->jam_selesai) ? substr((string) $sidang->jam_selesai, 0, 5) : 'Selesai';
        $nomorNota = $sidang->nomor_nota_dinas ?: ('W.12-UM.01.01-'.str_pad($sidang->id, 3, '0', STR_PAD_LEFT));

        $metodeText = match ($sidang->metode) {
            'daring' => 'Daring (Media Virtual Zoom/Meet) | Link: '.($sidang->link_daring ?: '-'),
            'hybrid' => "Hybrid | Tempat: {$sidang->tempat} & Virtual: ".($sidang->link_daring ?: '-'),
            default => "Tatap Muka Langsung di {$sidang->tempat}",
        };

        // Kop Surat
        $logoBase64 = '';
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoBase64 = base64_encode(file_get_contents(public_path($instansi->logo_kop)));
        }

        $kopHtml = "
        <table width='100%' style='border-bottom:3px double #000;margin-bottom:12pt;padding-bottom:4pt;'>
            <tr>
                <td width='15%' align='center' valign='middle'>
                    ".(! empty($logoBase64) ? "<img src='data:image/png;base64,{$logoBase64}' style='width:60pt;height:auto;' />" : '')."
                </td>
                <td width='85%' align='center' valign='middle' style='line-height:1.2;'>
                    <div style='font-size:11.5pt;font-weight:bold;text-transform:uppercase;'>KEMENTERIAN HUKUM DAN HAK ASASI MANUSIA RI / KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN</div>
                    <div style='font-size:12.5pt;font-weight:bold;text-transform:uppercase;'>KANTOR WILAYAH BANTEN</div>
                    <div style='font-size:13pt;font-weight:bold;text-transform:uppercase;color:#1e3a8a;'>DIVISI PEMASYARAKATAN</div>
                    <div style='font-size:8.5pt;font-family:Arial,sans-serif;margin-top:2pt;'>".($instansi?->alamat ?? 'Jl. KH. Fatah Hasan No. 88, Serang, Banten').'</div>
                </td>
            </tr>
        </table>';

        $daftarAnggotaHtml = '<ol style="margin-top:4pt;padding-left:18pt;line-height:1.4;">';
        foreach ($anggotaList as $a) {
            $daftarAnggotaHtml .= '<li><b>'.htmlspecialchars($a->nama).'</b> - '.htmlspecialchars($a->jabatan).' ('.htmlspecialchars($a->peran_sidang).')</li>';
        }
        $daftarAnggotaHtml .= '</ol>';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Dinas Undangan Sidang TPP Kanwil</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.4; color: #000; }
        .judul-nota { text-align: center; font-size: 13pt; font-weight: bold; text-decoration: underline; margin-bottom: 2pt; }
        .nomor-nota { text-align: center; font-size: 10.5pt; margin-bottom: 14pt; }
        table.tbl-meta { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
        table.tbl-meta td { padding: 2pt 4pt; vertical-align: top; }
    </style>
</head>
<body>
    {$kopHtml}

    <div class="judul-nota">NOTA DINAS</div>
    <div class="nomor-nota">Nomor : {$nomorNota}</div>

    <table class="tbl-meta">
        <tr>
            <td width="15%"><b>Yth.</b></td>
            <td width="3%">:</td>
            <td width="82%">Ketua, Sekretaris, dan Anggota Tim Sidang TPP Kantor Wilayah (Terlampir)</td>
        </tr>
        <tr>
            <td><b>Dari</b></td>
            <td>:</td>
            <td>Sekretariat Sidang Tim Pengamat Pemasyarakatan (TPP) Kantor Wilayah</td>
        </tr>
        <tr>
            <td><b>Tanggal</b></td>
            <td>:</td>
            <td>{$tanggalNota}</td>
        </tr>
        <tr>
            <td><b>Sifat</b></td>
            <td>:</td>
            <td>Penting / Segera</td>
        </tr>
        <tr>
            <td><b>Hal</b></td>
            <td>:</td>
            <td><b>Undangan Pelaksanaan Sidang TPP Tingkat Kantor Wilayah</b></td>
        </tr>
    </table>

    <div style="border-top:1px solid #000;margin-bottom:12pt;"></div>

    <p style="text-align:justify;text-indent:25pt;margin-bottom:8pt;">
        Sehubungan dengan pelaksanaan tugas dan fungsi Tim Pengamat Pemasyarakatan (TPP) Tingkat Kantor Wilayah Kementerian Hukum dan HAM Banten dalam rangka membahas pertimbangan dan rekomendasi usulan pembinaan warga binaan, bersama ini kami mengundang Bapak/Ibu untuk hadir pada Sidang TPP Kantor Wilayah yang akan dilaksanakan pada:
    </p>

    <table width="100%" border="0" style="margin-left:20pt;margin-bottom:10pt;line-height:1.5;">
        <tr>
            <td width="22%"><b>Hari, Tanggal</b></td>
            <td width="3%">:</td>
            <td width="75%"><b>{$hariTanggal}</b></td>
        </tr>
        <tr>
            <td><b>Waktu</b></td>
            <td>:</td>
            <td>Pukul {$jamMulai} s/d {$jamSelesai} WIB</td>
        </tr>
        <tr>
            <td><b>Tempat / Pelaksanaan</b></td>
            <td>:</td>
            <td>{$metodeText}</td>
        </tr>
        <tr>
            <td valign="top"><b>Kategori Agenda</b></td>
            <td valign="top">:</td>
            <td valign="top"><b>".htmlspecialchars($sidang->agenda_kategori)."</b></td>
        </tr>
        <tr>
            <td valign="top"><b>Agenda Pembahasan</b></td>
            <td valign="top">:</td>
            <td valign="top"><b>".htmlspecialchars($sidang->agenda_judul)."</b></td>
        </tr>
        <tr>
            <td valign="top"><b>Uraian Ringkas</b></td>
            <td valign="top">:</td>
            <td valign="top" style="text-align:justify;">".nl2br(htmlspecialchars($sidang->agenda_deskripsi ?: '-')).'</td>
        </tr>
    </table>

    <p style="text-align:justify;text-indent:25pt;margin-bottom:8pt;">
        Mengingat pentingnya agenda tersebut, dimohon kesediaan Bapak/Ibu untuk hadir tepat waktu serta menyampaikan konfirmasi presensi dan telaahan/pendapat sidang melalui tautan digital yang telah dikirimkan oleh Sekretariat TPP.
    </p>

    <p style="text-align:justify;text-indent:25pt;margin-bottom:16pt;">
        Demikian nota dinas ini disampaikan, atas perhatian dan kehadiran Bapak/Ibu diucapkan terima kasih.
    </p>

    <table width="100%" border="0" style="page-break-inside:avoid;">
        <tr>
            <td width="55%"></td>
            <td width="45%" align="center">
                Serang, {$tanggalNota}<br>
                <b>Sekretaris Sidang TPP Kantor Wilayah,</b><br><br><br><br><br>
                <u><b>'.htmlspecialchars($sidang->sekretaris_nama).'</b></u><br>
                '.(! empty($sidang->sekretaris_nip) ? 'NIP. '.htmlspecialchars($sidang->sekretaris_nip) : '').'
            </td>
        </tr>
    </table>

    <div style="page-break-before:always;margin-top:20pt;"></div>
    {$kopHtml}
    <div style="font-weight:bold;text-decoration:underline;margin-bottom:6pt;">LAMPIRAN NOTA DINAS</div>
    <table width="100%" border="0" style="margin-bottom:10pt;">
        <tr>
            <td width="15%">Nomor</td>
            <td width="3%">:</td>
            <td width="82%">{$nomorNota}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>:</td>
            <td>{$tanggalNota}</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td>Daftar Susunan Tim Sidang TPP Kantor Wilayah</td>
        </tr>
    </table>

    <p><b>A. Pimpinan Sidang:</b></p>
    <ul style="margin-top:2pt;padding-left:18pt;line-height:1.5;">
        <li><b>Ketua:</b> '.htmlspecialchars($sidang->ketua_nama).' - '.htmlspecialchars($sidang->ketua_jabatan ?: 'Ketua Sidang TPP').'</li>
        <li><b>Sekretaris:</b> '.htmlspecialchars($sidang->sekretaris_nama).' - '.htmlspecialchars($sidang->sekretaris_jabatan ?: 'Sekretaris Sidang TPP').'</li>
    </ul>

    <p><b>B. Anggota Tim Sidang yang Diundang:</b></p>
    {$daftarAnggotaHtml}
</body>
</html>
HTML;
    }
}
