<?php

namespace App\Services;

use App\Models\Instansi;
use App\Models\SidangTppKanwil;
use App\Models\TppKanwilSetting;
use Carbon\Carbon;
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
        $pdf->SetFont('helvetica', '', 10);

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
        $pdf->SetFont('helvetica', '', 10);

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

        // Kop Surat Standar Kemenimipas Ditjenpas Kanwil Banten
        $logoBase64 = '';
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoBase64 = base64_encode(file_get_contents(public_path($instansi->logo_kop)));
        }

        $kopHtml = "
        <table width='100%' style='border-bottom:2px solid #000;margin-bottom:12pt;padding-bottom:4pt;'>
            <tr>
                <td width='15%' align='center' valign='middle'>
                    ".(! empty($logoBase64) ? "<img src='data:image/png;base64,{$logoBase64}' style='width:62pt;height:auto;' />" : '')."
                </td>
                <td width='85%' align='center' valign='middle' style='line-height:1.2;font-family:Arial,Helvetica,sans-serif;'>
                    <div style='font-size:10pt;font-weight:bold;text-transform:uppercase;'>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>
                    <div style='font-size:10.5pt;font-weight:bold;text-transform:uppercase;'>DIREKTORAT JENDERAL PEMASYARAKATAN</div>
                    <div style='font-size:11.5pt;font-weight:bold;text-transform:uppercase;'>KANTOR WILAYAH BANTEN</div>
                    <div style='font-size:8pt;margin-top:2pt;'>Jl. KH. Sam'un No. 44 Serang, Banten</div>
                    <div style='font-size:7.5pt;'>Telepon: 0822-6666-2044, Faksimilie: 0822-6666-2044</div>
                    <div style='font-size:7.5pt;'>Laman: banten.kemenimipas.go.id, Pos-el: kanwilbanten@kemenimipas.go.id</div>
                </td>
            </tr>
        </table>";

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

        $setting = TppKanwilSetting::getFirst();
        $customBa = $sidang->dokumen_custom['berita_acara'] ?? null;

        if (! empty($customBa['kesimpulan_rekomendasi'])) {
            $kesimpulan = nl2br(htmlspecialchars($customBa['kesimpulan_rekomendasi']));
        } else {
            $kesimpulan = ! empty($sidang->kesimpulan_sidang)
                ? htmlspecialchars($sidang->kesimpulan_sidang)
                : 'Seluruh tim Sidang TPP Kantor Wilayah telah mencermati berkas dan pertimbangan teknis substantif. Rekomendasi sidang disepakati untuk diajukan kepada Kepala Kantor Wilayah guna memperoleh persetujuan dan arahan lebih lanjut.';
        }

        $uraianAgendaText = ! empty($customBa['uraian_sidang'])
            ? nl2br(htmlspecialchars($customBa['uraian_sidang']))
            : nl2br(htmlspecialchars($sidang->agenda_deskripsi ?: '-'));

        $agendaJudulText = htmlspecialchars($sidang->agenda_judul);
        if ($sidang->materis && $sidang->materis->count() > 1) {
            $agendaJudulText = '<ol style="margin:0;padding-left:15pt;">';
            foreach ($sidang->materis as $m) {
                $agendaJudulText .= '<li><b>'.htmlspecialchars($m->judul_materi).'</b> <span style="font-size:8.5pt;color:#555;">('.htmlspecialchars($m->kategori_badge).')</span></li>';
            }
            $agendaJudulText .= '</ol>';

            if (empty($customBa['uraian_sidang'])) {
                $uraianAgendaText = '<ol style="margin:0;padding-left:15pt;">';
                foreach ($sidang->materis as $m) {
                    $desc = $m->deskripsi_materi ?: $m->judul_materi;
                    $uraianAgendaText .= '<li style="margin-bottom:3pt;"><b>'.htmlspecialchars($m->judul_materi).':</b> '.htmlspecialchars($desc).'</li>';
                }
                $uraianAgendaText .= '</ol>';
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Berita Acara Sidang TPP Kantor Wilayah</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; line-height: 1.35; color: #000; }
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
    <div class="judul-ba" style="font-size:11pt;">KANTOR WILAYAH KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN BANTEN</div>
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
            <td valign="top">{$agendaJudulText}</td>
        </tr>
        <tr>
            <td valign="top"><b>Uraian Agenda</b></td>
            <td valign="top">:</td>
            <td valign="top" style="text-align:justify;">{$uraianAgendaText}</td>
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
                        KEPALA KANTOR WILAYAH<br>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN BANTEN
                    </div>
                    <br><br><br><br>
                    <div style="font-size:9.5pt;font-weight:bold;text-decoration:underline;">
                        '.htmlspecialchars($sidang->kakanwil_nama ?: ($setting->kakanwil_nama ?? ($instansi?->nama_kakanwil ?? 'Dody Fahrudin, Bc.IP., S.H., M.H.'))).'
                    </div>
                    <div style="font-size:8.5pt;">NIP. '.htmlspecialchars($sidang->kakanwil_nip ?: ($setting->kakanwil_nip ?? ($instansi?->nip_kakanwil ?? '197205141994031001'))).'</div>
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

        // Kop Surat Standar Kemenimipas Ditjenpas Kanwil Banten
        $logoBase64 = '';
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoBase64 = base64_encode(file_get_contents(public_path($instansi->logo_kop)));
        }

        $kopHtml = "
        <table width='100%' style='border-bottom:2px solid #000;margin-bottom:12pt;padding-bottom:4pt;'>
            <tr>
                <td width='15%' align='center' valign='middle'>
                    ".(! empty($logoBase64) ? "<img src='data:image/png;base64,{$logoBase64}' style='width:62pt;height:auto;' />" : '')."
                </td>
                <td width='85%' align='center' valign='middle' style='line-height:1.2;font-family:Arial,Helvetica,sans-serif;'>
                    <div style='font-size:10pt;font-weight:bold;text-transform:uppercase;'>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>
                    <div style='font-size:10.5pt;font-weight:bold;text-transform:uppercase;'>DIREKTORAT JENDERAL PEMASYARAKATAN</div>
                    <div style='font-size:11.5pt;font-weight:bold;text-transform:uppercase;'>KANTOR WILAYAH BANTEN</div>
                    <div style='font-size:8pt;margin-top:2pt;'>Jl. KH. Sam'un No. 44 Serang, Banten</div>
                    <div style='font-size:7.5pt;'>Telepon: 0822-6666-2044, Faksimilie: 0822-6666-2044</div>
                    <div style='font-size:7.5pt;'>Laman: banten.kemenimipas.go.id, Pos-el: kanwilbanten@kemenimipas.go.id</div>
                </td>
            </tr>
        </table>";

        $daftarAnggotaHtml = '<ol style="margin-top:4pt;padding-left:18pt;line-height:1.4;">';
        foreach ($anggotaList as $a) {
            $daftarAnggotaHtml .= '<li><b>'.htmlspecialchars($a->nama).'</b> - '.htmlspecialchars($a->jabatan).' ('.htmlspecialchars($a->peran_sidang).')</li>';
        }
        $daftarAnggotaHtml .= '</ol>';

        $agendaNotaHtml = '<b>'.htmlspecialchars($sidang->agenda_judul).'</b>';
        if ($sidang->materis && $sidang->materis->count() > 1) {
            $agendaNotaHtml = '<ol style="margin:0;padding-left:15pt;">';
            foreach ($sidang->materis as $m) {
                $agendaNotaHtml .= '<li><b>'.htmlspecialchars($m->judul_materi).'</b> <span style="font-size:9pt;color:#444;">('.htmlspecialchars($m->kategori_badge).')</span></li>';
            }
            $agendaNotaHtml .= '</ol>';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Dinas Undangan Sidang TPP Kanwil</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5pt; line-height: 1.4; color: #000; }
        .judul-nota { text-align: center; font-size: 13pt; font-weight: bold; text-decoration: underline; margin-bottom: 2pt; }
        .nomor-nota { text-align: center; font-size: 10pt; margin-bottom: 14pt; }
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
            <td valign="top">{$agendaNotaHtml}</td>
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

    /**
     * Generate berkas Microsoft Word (.doc) untuk Risalah Hasil Sidang TPP Kanwil.
     */
    public static function generateRisalahWord(SidangTppKanwil $sidang): Response
    {
        $html = self::renderRisalahHtml($sidang, true);

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_risalah ?: $sidang->nomor_sidang);
        $filename = 'Risalah_Hasil_Sidang_TPP_'.$cleanNomor.'_'.date('Ymd_His').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Generate berkas PDF Resmi Risalah Hasil Sidang TPP Kanwil.
     */
    public static function generateRisalahPdf(SidangTppKanwil $sidang): Response
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
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages().' | Risalah Hasil Sidang TPP Kantor Wilayah Banten', 0, 0, 'R');
            }
        };

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor('Kanwil Ditjenpas Banten');
        $pdf->SetTitle('Risalah Hasil Sidang TPP - '.($sidang->nomor_risalah ?: $sidang->nomor_sidang));
        $pdf->SetMargins(16, 14, 16);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $htmlContent = self::renderRisalahHtml($sidang, false);
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_risalah ?: $sidang->nomor_sidang);
        $filename = 'Risalah_Hasil_Sidang_TPP_'.$cleanNomor.'_'.date('Ymd_His').'.pdf';

        $output = $pdf->Output($filename, 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Render Template HTML untuk Risalah Hasil Sidang TPP Kanwil.
     */
    public static function renderRisalahHtml(SidangTppKanwil $sidang, bool $forWord = false): string
    {
        $instansi = Instansi::getFirst();
        $setting = TppKanwilSetting::getFirst();
        $anggotaList = $sidang->anggota()->get();

        $hariTanggal = $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-';
        $tanggalRisalah = $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('d F Y') : date('d F Y');
        $jamMulai = substr((string) $sidang->jam_mulai, 0, 5);
        $nomorRisalah = $sidang->nomor_risalah ?: (($setting->nomor_risalah_prefix ?? 'WP.12.PK.06.05-').str_pad($sidang->id, 2, '0', STR_PAD_LEFT));

        // Kop Surat Resmi
        $logoBase64 = '';
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoBase64 = base64_encode(file_get_contents(public_path($instansi->logo_kop)));
        }

        $kopHtml = "
        <table width='100%' style='border-bottom:2px solid #000;margin-bottom:12pt;padding-bottom:4pt;'>
            <tr>
                <td width='15%' align='center' valign='middle'>
                    ".(! empty($logoBase64) ? "<img src='data:image/png;base64,{$logoBase64}' style='width:62pt;height:auto;' />" : '')."
                </td>
                <td width='85%' align='center' valign='middle' style='line-height:1.2;'>
                    <div style='font-size:10pt;font-weight:bold;text-transform:uppercase;'>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>
                    <div style='font-size:10.5pt;font-weight:bold;text-transform:uppercase;'>DIREKTORAT JENDERAL PEMASYARAKATAN</div>
                    <div style='font-size:11.5pt;font-weight:bold;text-transform:uppercase;'>KANTOR WILAYAH BANTEN</div>
                    <div style='font-size:8pt;margin-top:2pt;'>Jl. KH. Sam'un No. 44 Serang, Banten</div>
                    <div style='font-size:7.5pt;'>Telepon: 0822-6666-2044, Faksimilie: 0822-6666-2044</div>
                    <div style='font-size:7.5pt;'>Laman: djpasbanten@gmail.com, Pos-el: djpasbanten@gmail.com</div>
                </td>
            </tr>
        </table>";

        // Dasar Hukum
        $dasarHukumList = $setting->dasar_hukum_json ?: TppKanwilSetting::defaultDasarHukum();
        if ($sidang->is_klien_bapas) {
            // Pastikan UU 22/2022 pasal 57 & 58 ada
            $adaPasal57 = false;
            foreach ($dasarHukumList as $dh) {
                if (str_contains($dh, 'Pasal 57') || str_contains($dh, 'Bimbingan Klien')) {
                    $adaPasal57 = true;
                    break;
                }
            }
            if (! $adaPasal57) {
                $dasarHukumList[] = (count($dasarHukumList) + 1).'. Undang-Undang Nomor 22 Tahun 2022 tentang Pemasyarakatan Pasal 57 dan Pasal 58 tentang Pelayanan dan Bimbingan Klien Pemasyarakatan;';
            }
        }

        $dasarHukumHtml = '<ol style="margin-top:3pt;padding-left:18pt;line-height:1.35;margin-bottom:8pt;">';
        foreach ($dasarHukumList as $item) {
            // Bersihkan nomor di depan jika sudah ada
            $cleanItem = preg_replace('/^\d+[\.\)]\s*/', '', trim($item));
            $dasarHukumHtml .= '<li style="margin-bottom:3pt;text-align:justify;">'.$cleanItem.'</li>';
        }
        $dasarHukumHtml .= '</ol>';

        // Peserta Sidang
        $totalAnggota = $anggotaList->count();
        $totalTerbilang = self::terbilang($totalAnggota);
        $barisPesertaHtml = '';
        $noP = 1;
        foreach ($anggotaList as $a) {
            $barisPesertaHtml .= "
            <tr>
                <td align='center' valign='middle' style='border:1px solid #000;padding:4pt;'>{$noP}</td>
                <td valign='middle' style='border:1px solid #000;padding:4pt;'><b>".htmlspecialchars($a->nama)."</b></td>
                <td align='center' valign='middle' style='border:1px solid #000;padding:4pt;'>".htmlspecialchars($a->peran_sidang).'</td>
            </tr>';
            $noP++;
        }

        // Materi Sidang & Hasil Sidang (Prioritas Dokumen Kustom AI / User)
        $customRisalah = $sidang->dokumen_custom['risalah'] ?? null;
        $kd = $sidang->klien_data ?: [];
        $materiHtml = '';
        $hasilHtml = '';

        if (! empty($customRisalah['materi_sidang'])) {
            if (is_array($customRisalah['materi_sidang'])) {
                $materiHtml = '<ol style="margin-top:3pt;padding-left:18pt;line-height:1.4;">';
                foreach ($customRisalah['materi_sidang'] as $item) {
                    $cleanItem = preg_replace('/^\d+[\.\)]\s*/', '', trim($item));
                    $materiHtml .= '<li style="margin-bottom:4pt;text-align:justify;">'.nl2br(htmlspecialchars($cleanItem)).'</li>';
                }
                $materiHtml .= '</ol>';
            } else {
                $materiHtml = '<div style="margin-left:15pt;margin-top:3pt;line-height:1.4;text-align:justify;">'.nl2br(htmlspecialchars($customRisalah['materi_sidang'])).'</div>';
            }
        } elseif ($sidang->materis && $sidang->materis->count() > 0) {
            $materiHtml = '<ol style="margin-top:3pt;padding-left:18pt;line-height:1.4;">';
            foreach ($sidang->materis as $m) {
                if ($m->isMutasiKeinginanSendiri()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $uptAsal = $nd['upt_asal_nama'] ?? 'Lapas/Rutan Asal';
                    $uptTujuan = $m->uptTujuanRekomendasi?->nama_upt ?? ($nd['upt_tujuan_nama'] ?? 'UPT Tujuan');
                    $pkNama = $nd['pk_bapas']['nama'] ?? 'PK Bapas';
                    $baNo = $nd['tpp_upt']['nomor_ba'] ?? '-';
                    $materiHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Mutasi Permintaan Sendiri):</b> Permohonan pemindahan Narapidana an. <b>{$namaWbp}</b> dari {$uptAsal} ke <b>{$uptTujuan}</b> atas permohonan pribadi mendekati keluarga penjamin dengan kesanggupan pembiayaan mandiri. Dilengkapi Surat Pernyataan bermaterai, Litmas PK Bapas ({$pkNama}), Berita Acara Sidang TPP UPT ({$baNo}), dan jaminan keluarga penjamin.</li>";
                } elseif ($m->isMutasiNarapidana()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $uptAsal = $nd['upt_asal_nama'] ?? 'Lapas/Rutan Asal';
                    $uptTujuan = $m->uptTujuanRekomendasi?->nama_upt ?? ($nd['upt_tujuan_nama'] ?? 'UPT Tujuan');
                    $alokasiList = $nd['alokasi'] ?? [];

                    if (! empty($alokasiList) && is_array($alokasiList)) {
                        $rincianAlokasi = [];
                        foreach ($alokasiList as $alok) {
                            $uName = $alok['upt_tujuan_nama'] ?? 'UPT Tujuan';
                            $tot = $alok['total'] ?? 0;
                            $n = $alok['jumlah_narkotika'] ?? 0;
                            $p = $alok['jumlah_pidum'] ?? 0;
                            $k = $alok['jumlah_korupsi'] ?? 0;
                            $rincianAlokasi[] = "<b>{$uName}</b> ({$tot} orang: {$n} Narkotika, {$p} Pidum, {$k} Korupsi)";
                        }
                        $rincianStr = implode('; ', $rincianAlokasi);
                        $totalUsulanStr = ! empty($nd['total_usulan']) ? "sejumlah <b>{$nd['total_usulan']} Orang</b>" : '';
                        $suratNoStr = ! empty($nd['nomor_surat_usulan']) ? " (Surat Usulan No: {$nd['nomor_surat_usulan']})" : '';
                        $materiHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Pemindahan Antar-UPT):</b> Usulan pemindahan Narapidana kolektif dari <b>{$uptAsal}</b>{$suratNoStr} {$totalUsulanStr}. Tim Sidang TPP Kanwil menyepakati rekomendasi distribusi alokasi ke UPT Tujuan Penerima, meliputi: {$rincianStr}. Dilengkapi Surat Usulan, Berkas Lampiran Nominatif WBP, dan Berita Acara (BA) Sidang TPP UPT asal.</li>";
                    } else {
                        $materiHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Mutasi):</b> Usulan pemindahan Narapidana an. <b>{$namaWbp}</b> dari {$uptAsal} ke <b>{$uptTujuan}</b> (Alasan: ".($nd['alasan_pemindahan'] ?? 'Mendekati keluarga').'). Dilengkapi Litmas Bapas, asesmen risiko, buku register F, dan jaminan penjamin.</li>';
                    }
                } elseif ($m->isIzinBerobat()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $uptAsal = $nd['upt_asal_nama'] ?? 'Lapas/Rutan Asal';
                    $rsNama = $nd['rs_rujukan_nama'] ?? 'RS Rujukan';
                    $rsKota = $nd['rs_rujukan_kota'] ?? 'Luar Kota';
                    $materiHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Izin Berobat):</b> Permohonan izin berobat keluar kota bagi Narapidana an. <b>{$namaWbp}</b> ({$uptAsal}) ke <b>{$rsNama} ({$rsKota})</b> dengan diagnosa medis: ".($nd['diagnosa_medis'] ?? '-').'. Dilengkapi resume medis, surat rujukan spesialis, dan BA Sidang UPT.</li>';
                } elseif ($m->isKlienBapas()) {
                    $kd = $m->klien_data ?: [];
                    $namaKlien = $kd['nama_klien'] ?? $m->judul_materi;
                    $bin = ! empty($kd['bin']) ? ' BIN '.strtoupper($kd['bin']) : '';
                    $materiHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Klien Bapas):</b> Permohonan Izin Bepergian ke Luar Negeri untuk ".($kd['keperluan_ln'] ?? 'Ibadah Umroh').' ke '.($kd['negara_tujuan'] ?? 'Arab Saudi')." bagi Klien Pemasyarakatan an. <b>{$namaKlien}{$bin}</b>. Dilengkapi Litmas Bapas dan surat jaminan keluarga.</li>";
                } else {
                    $materiHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan}:</b> <b>".htmlspecialchars($m->judul_materi).'</b>. '.htmlspecialchars($m->deskripsi_materi ?: '-').'</li>';
                }
            }
            $materiHtml .= '</ol>';
        } elseif ($sidang->isMutasiNarapidana() && ! empty($sidang->narapidana_data)) {
            $nd = $sidang->narapidana_data;
            $namaWbp = $nd['nama'] ?? $sidang->agenda_judul;
            $uptTujuan = $nd['upt_tujuan_nama'] ?? ($sidang->uptTujuanRekomendasi?->nama_upt ?? 'UPT Tujuan Rekomendasi');

            $hasilHtml = "
            <ol style='margin-top:3pt;padding-left:18pt;line-height:1.4;'>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    <b>Menyetujui dan merekomendasikan</b> usulan pemindahan / mutasi narapidana an. <b>{$namaWbp}</b> dengan rekomendasi penetapan UPT Tujuan ke <b>{$uptTujuan}</b> guna kepentingan pembinaan lanjutan dan mendekatkan dengan keluarga.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Pelaksanaan pemindahan wajib memedomani Standar Operasional Prosedur (SOP) pengawalan narapidana, berkoordinasi dengan pihak terkait, dan memperhatikan protokol keamanan serta kelengkapan berkas fisik.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Meneruskan hasil rekomendasi Sidang TPP ini kepada Kepala Kantor Wilayah Kementerian Imigrasi dan Pemasyarakatan Banten guna penerbitan Surat Keputusan Persetujuan Mutasi dan sinkronisasi modul SDP.
                </li>
            </ol>";
        } elseif ($sidang->isIzinBerobat() && ! empty($sidang->narapidana_data)) {
            $nd = $sidang->narapidana_data;
            $namaWbp = $nd['nama'] ?? $sidang->agenda_judul;
            $rsNama = $nd['rs_rujukan_nama'] ?? 'RS Rujukan';
            $rsKota = $nd['rs_rujukan_kota'] ?? 'Luar Kota';

            $hasilHtml = "
            <ol style='margin-top:3pt;padding-left:18pt;line-height:1.4;'>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    <b>Menyetujui dan merekomendasikan</b> permohonan izin berobat keluar kota bagi narapidana an. <b>{$namaWbp}</b> ke <b>{$rsNama} ({$rsKota})</b> berdasarkan pertimbangan kedaruratan medis dan hak kesehatan warga binaan.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Pelaksanaan izin berobat keluar kota wajib disertai pengawalan ketat oleh petugas yang berwenang, pendampingan tenaga kesehatan, serta dilaporkan perkembangannya secara berkala kepada Kepala Kantor Wilayah.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Meneruskan hasil rekomendasi Sidang TPP kepada Direktur Jenderal Pemasyarakatan / Kepala Kantor Wilayah guna penerbitan persetujuan definitif sesuai ketentuan peraturan perundang-undangan.
                </li>
            </ol>";
        } elseif ($sidang->is_klien_bapas && ! empty($kd)) {
            $namaKlien = $kd['nama_klien'] ?? $sidang->agenda_judul;
            $bin = ! empty($kd['bin']) ? ' BIN '.strtoupper($kd['bin']) : '';
            $noReg = $kd['no_register_bapas'] ?? '-';
            $bapasNama = $kd['bapas_nama'] ?? 'Balai Pemasyarakatan Kelas I Serang';
            $noSuratBapas = $kd['nomor_surat_bapas'] ?? ('WP.12.PAS.BAPAS-'.str_pad($sidang->id, 3, '0', STR_PAD_LEFT));
            $tglSuratBapas = ! empty($kd['tanggal_surat_bapas']) ? Carbon::parse($kd['tanggal_surat_bapas'])->translatedFormat('d F Y') : $tanggalRisalah;
            $keperluanLn = $kd['keperluan_ln'] ?? 'Menjalankan Ibadah Umroh';
            $program = $kd['program_integrasi'] ?? 'Pembebasan Bersyarat (PB)';
            $durasi = $kd['durasi_hari'] ?? '14';
            $namaPk = $kd['nama_pk'] ?? 'Pembimbing Kemasyarakatan Bapas';
            $penjaminNama = $kd['penjamin_nama'] ?? 'Keluarga';
            $penjaminHub = $kd['penjamin_hubungan'] ?? 'Penjamin';

            $materiHtml = "
            <ol style='margin-top:3pt;padding-left:18pt;line-height:1.4;'>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Surat Kepala {$bapasNama} Nomor : {$noSuratBapas} tanggal {$tglSuratBapas} perihal Permohonan Izin Bepergian ke Luar Negeri untuk {$keperluanLn} Klien {$program} an. <b>{$namaKlien}{$bin}</b> (Nomor Register : {$noReg}).
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Laporan Hasil Penelitian Kemasyarakatan (Litmas) dari Pembimbing Kemasyarakatan {$bapasNama} an. <b>{$namaPk}</b> tentang kelayakan, rekam jejak bimbingan, dan analisis risiko perjalanan ke luar negeri.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Surat Pernyataan Jaminan Bermaterai dari Penjamin Keluarga an. <b>{$penjaminNama}</b> ({$penjaminHub}) serta dokumen bukti pendaftaran/jadwal perjalanan resmi.
                </li>
            </ol>";
        } else {
            $uraianMateri = ! empty($sidang->agenda_deskripsi)
                ? nl2br(htmlspecialchars($sidang->agenda_deskripsi))
                : htmlspecialchars($sidang->agenda_judul);

            $materiHtml = "
            <div style='margin-left:15pt;margin-top:3pt;line-height:1.4;text-align:justify;'>
                {$uraianMateri}
            </div>";
        }

        if (! empty($customRisalah['hasil_sidang'])) {
            if (is_array($customRisalah['hasil_sidang'])) {
                $hasilHtml = '<ol style="margin-top:3pt;padding-left:18pt;line-height:1.4;">';
                foreach ($customRisalah['hasil_sidang'] as $item) {
                    $cleanItem = preg_replace('/^\d+[\.\)]\s*/', '', trim($item));
                    $hasilHtml .= '<li style="margin-bottom:4pt;text-align:justify;">'.nl2br(htmlspecialchars($cleanItem)).'</li>';
                }
                $hasilHtml .= '</ol>';
            } else {
                $hasilHtml = '<div style="margin-left:15pt;margin-top:3pt;line-height:1.4;text-align:justify;">'.nl2br(htmlspecialchars($customRisalah['hasil_sidang'])).'</div>';
            }
        } elseif ($sidang->materis && $sidang->materis->count() > 0) {
            $hasilHtml = '<ol style="margin-top:3pt;padding-left:18pt;line-height:1.4;">';
            foreach ($sidang->materis as $m) {
                $rekLabel = match ($m->rekomendasi_sidang) {
                    'setuju' => 'Menyetujui dan merekomendasikan',
                    'setuju_catatan' => 'Menyetujui dengan catatan khusus',
                    'tolak' => 'Menolak',
                    'tunda' => 'Menunda pembahasan',
                    default => 'Menyetujui dan merekomendasikan',
                };
                if ($m->isMutasiKeinginanSendiri()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $uptTujuan = $m->uptTujuanRekomendasi?->nama_upt ?? ($nd['upt_tujuan_nama'] ?? 'UPT Tujuan Rekomendasi');
                    $catatan = $m->catatan_rekomendasi ? " (Catatan: {$m->catatan_rekomendasi})" : '';
                    $hasilHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Mutasi Permintaan Sendiri):</b> Tim Sidang TPP <b>{$rekLabel}</b> permohonan pemindahan Narapidana an. <b>{$namaWbp}</b> atas keinginan sendiri mendekati keluarga penjamin dengan rekomendasi penetapan UPT Tujuan ke <b>{$uptTujuan}</b> dengan pembiayaan mandiri{$catatan}. Meneruskan berkas kepada Kepala Kantor Wilayah guna penerbitan SK Persetujuan Mutasi dan sinkronisasi modul SDP.</li>";
                } elseif ($m->isMutasiNarapidana()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $uptTujuan = $m->uptTujuanRekomendasi?->nama_upt ?? ($nd['upt_tujuan_nama'] ?? 'UPT Tujuan Rekomendasi');
                    $catatan = $m->catatan_rekomendasi ? " (Catatan: {$m->catatan_rekomendasi})" : '';
                    $alokasiList = $nd['alokasi'] ?? [];

                    if (! empty($alokasiList) && is_array($alokasiList)) {
                        $rincianAlokasi = [];
                        foreach ($alokasiList as $alok) {
                            $uName = $alok['upt_tujuan_nama'] ?? 'UPT Tujuan';
                            $tot = $alok['total'] ?? 0;
                            $rincianAlokasi[] = "{$uName} ({$tot} Orang)";
                        }
                        $rincianStr = implode(', ', $rincianAlokasi);
                        $grandTot = $nd['total_disetujui'] ?? array_sum(array_column($alokasiList, 'total'));
                        $hasilHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Pemindahan Antar-UPT):</b> Tim Sidang TPP <b>{$rekLabel}</b> usulan pemindahan Narapidana kolektif dari <b>".($nd['upt_asal_nama'] ?? 'UPT Asal')."</b> dengan alokasi total disetujui <b>{$grandTot} Orang</b> yang didistribusikan ke: <b>{$rincianStr}</b>{$catatan}. Meneruskan berkas kepada Kepala Kantor Wilayah guna penerbitan SK Persetujuan Pemindahan dan pembukaan tugas pelaksanaan bagi UPT Pengusul serta UPT Penerima.</li>";
                    } else {
                        $hasilHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Mutasi):</b> Tim Sidang TPP <b>{$rekLabel}</b> usulan pemindahan Narapidana an. <b>{$namaWbp}</b> dengan rekomendasi penetapan UPT Tujuan ke <b>{$uptTujuan}</b>{$catatan}. Meneruskan berkas kepada Kepala Kantor Wilayah guna penerbitan SK Persetujuan Mutasi dan sinkronisasi modul SDP.</li>";
                    }
                } elseif ($m->isIzinBerobat()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $rsNama = $nd['rs_rujukan_nama'] ?? 'RS Rujukan';
                    $rsKota = $nd['rs_rujukan_kota'] ?? 'Luar Kota';
                    $catatan = $m->catatan_rekomendasi ? " (Catatan: {$m->catatan_rekomendasi})" : '';
                    $hasilHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Izin Berobat):</b> Tim Sidang TPP <b>{$rekLabel}</b> permohonan izin berobat keluar kota bagi Narapidana an. <b>{$namaWbp}</b> ke <b>{$rsNama} ({$rsKota})</b> dengan pengawalan melekat petugas dan kepatuhan SOP kesehatan{$catatan}. Meneruskan rekomendasi kepada Ditjenpas / Kepala Kantor Wilayah.</li>";
                } elseif ($m->isKlienBapas()) {
                    $kd = $m->klien_data ?: [];
                    $namaKlien = $kd['nama_klien'] ?? $m->judul_materi;
                    $bin = ! empty($kd['bin']) ? ' BIN '.strtoupper($kd['bin']) : '';
                    $catatan = $m->catatan_rekomendasi ? " (Catatan: {$m->catatan_rekomendasi})" : '';
                    $hasilHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan} (Klien Bapas):</b> Tim Sidang TPP <b>{$rekLabel}</b> permohonan izin bepergian ke luar negeri Klien Pemasyarakatan an. <b>{$namaKlien}{$bin}</b> dengan kewajiban melapor paling lambat 3 hari kerja setelah tiba di tanah air{$catatan}. Meneruskan rekomendasi kepada Kepala Kantor Wilayah.</li>";
                } else {
                    $catatan = $m->catatan_rekomendasi ? " (Catatan: {$m->catatan_rekomendasi})" : '';
                    $hasilHtml .= "<li style='margin-bottom:4pt;text-align:justify;'><b>Materi #{$m->urutan}:</b> Tim Sidang TPP <b>{$rekLabel}</b> pokok agenda <b>".htmlspecialchars($m->judul_materi)."</b>{$catatan}.</li>";
                }
            }
            $hasilHtml .= '</ol>';
        } elseif ($sidang->isMutasiNarapidana() && ! empty($sidang->narapidana_data)) {
            $nd = $sidang->narapidana_data;
            $namaWbp = $nd['nama'] ?? $sidang->agenda_judul;
            $uptTujuan = $nd['upt_tujuan_nama'] ?? ($sidang->uptTujuanRekomendasi?->nama_upt ?? 'UPT Tujuan Rekomendasi');

            $hasilHtml = "
            <ol style='margin-top:3pt;padding-left:18pt;line-height:1.4;'>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    <b>Menyetujui dan merekomendasikan</b> usulan pemindahan / mutasi narapidana an. <b>{$namaWbp}</b> dengan rekomendasi penetapan UPT Tujuan ke <b>{$uptTujuan}</b> guna kepentingan pembinaan lanjutan dan mendekatkan dengan keluarga.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Pelaksanaan pemindahan wajib memedomani Standar Operasional Prosedur (SOP) pengawalan narapidana, berkoordinasi dengan pihak terkait, dan memperhatikan protokol keamanan serta kelengkapan berkas fisik.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Meneruskan hasil rekomendasi Sidang TPP ini kepada Kepala Kantor Wilayah Kementerian Imigrasi dan Pemasyarakatan Banten guna penerbitan Surat Keputusan Persetujuan Mutasi dan sinkronisasi modul SDP.
                </li>
            </ol>";
        } elseif ($sidang->isIzinBerobat() && ! empty($sidang->narapidana_data)) {
            $nd = $sidang->narapidana_data;
            $namaWbp = $nd['nama'] ?? $sidang->agenda_judul;
            $rsNama = $nd['rs_rujukan_nama'] ?? 'RS Rujukan';
            $rsKota = $nd['rs_rujukan_kota'] ?? 'Luar Kota';

            $hasilHtml = "
            <ol style='margin-top:3pt;padding-left:18pt;line-height:1.4;'>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    <b>Menyetujui dan merekomendasikan</b> permohonan izin berobat keluar kota bagi narapidana an. <b>{$namaWbp}</b> ke <b>{$rsNama} ({$rsKota})</b> berdasarkan pertimbangan kedaruratan medis dan hak kesehatan warga binaan.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Pelaksanaan izin berobat keluar kota wajib disertai pengawalan ketat oleh petugas yang berwenang, pendampingan tenaga kesehatan, serta dilaporkan perkembangannya secara berkala kepada Kepala Kantor Wilayah.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Meneruskan hasil rekomendasi Sidang TPP kepada Direktur Jenderal Pemasyarakatan / Kepala Kantor Wilayah guna penerbitan persetujuan definitif sesuai ketentuan peraturan perundang-undangan.
                </li>
            </ol>";
        } elseif ($sidang->is_klien_bapas && ! empty($kd)) {
            $namaKlien = $kd['nama_klien'] ?? $sidang->agenda_judul;
            $bin = ! empty($kd['bin']) ? ' BIN '.strtoupper($kd['bin']) : '';
            $keperluanLn = $kd['keperluan_ln'] ?? 'Menjalankan Ibadah Umroh';
            $negara = $kd['negara_tujuan'] ?? 'Kerajaan Arab Saudi';
            $tglBerangkat = ! empty($kd['tgl_berangkat']) ? Carbon::parse($kd['tgl_berangkat'])->translatedFormat('d F Y') : '-';
            $tglKembali = ! empty($kd['tgl_kembali']) ? Carbon::parse($kd['tgl_kembali'])->translatedFormat('d F Y') : '-';
            $durasi = $kd['durasi_hari'] ?? '14';

            $hasilHtml = "
            <ol style='margin-top:3pt;padding-left:18pt;line-height:1.4;'>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    <b>Menyetujui dan merekomendasikan</b> permohonan izin bepergian ke luar negeri ke <b>{$negara}</b> untuk keperluan <b>{$keperluanLn}</b> selama {$durasi} hari terhitung mulai tanggal <b>{$tglBerangkat} s/d {$tglKembali}</b> kepada Klien Pemasyarakatan an. <b>{$namaKlien}{$bin}</b>.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Klien Pemasyarakatan diwajibkan melapor secara langsung kepada Pembimbing Kemasyarakatan pada Balai Pemasyarakatan bersangkutan paling lambat 3 (tiga) hari kerja terhitung setelah tiba kembali di wilayah Negara Kesatuan Republik Indonesia.
                </li>
                <li style='margin-bottom:4pt;text-align:justify;'>
                    Meneruskan hasil rekomendasi Sidang TPP ini kepada Kepala Kantor Wilayah Kementerian Imigrasi dan Pemasyarakatan Banten guna penerbitan penetapan persetujuan izin resmi.
                </li>
            </ol>";
        } else {
            $uraianHasil = ! empty($sidang->kesimpulan_sidang)
                ? nl2br(htmlspecialchars($sidang->kesimpulan_sidang))
                : '1. <b>Menyetujui</b> usulan materi sidang TPP untuk diproses sesuai ketentuan peraturan perundang-undangan yang berlaku.<br>2. Merekomendasikan hasil pembahasan sidang ini kepada Kepala Kantor Wilayah guna memperoleh pengesahan dan tindak lanjut kebijakan.';

            $hasilHtml = "
            <div style='margin-left:15pt;margin-top:3pt;line-height:1.4;text-align:justify;'>
                {$uraianHasil}
            </div>";
        }

        // Tanda Tangan & Stempel
        $stempelBase64 = '';
        if ($setting->auto_embed_ttd && ! empty($setting->kanwil_stempel_image) && file_exists(public_path($setting->kanwil_stempel_image))) {
            $stempelBase64 = base64_encode(file_get_contents(public_path($setting->kanwil_stempel_image)));
        }

        $ttdKetuaBase64 = '';
        $stempelHtml = ! empty($stempelBase64)
            ? "<img src='data:image/png;base64,{$stempelBase64}' style='width:68pt;height:auto;position:relative;margin-bottom:-45pt;margin-left:-30pt;opacity:0.85;' />"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Risalah Hasil Sidang TPP Kanwil</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; line-height: 1.35; color: #000; }
        .judul-doc { text-align: center; font-size: 12pt; font-weight: bold; margin-bottom: 2pt; text-transform: uppercase; }
        .nomor-doc { text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 12pt; }
        table.tbl-peserta { width: 100%; border-collapse: collapse; margin-top: 4pt; margin-bottom: 8pt; }
        table.tbl-peserta th { background-color: #f2f2f2; font-weight: bold; text-align: center; padding: 4pt; border: 1px solid #000; font-size: 9.5pt; }
    </style>
</head>
<body>
    {$kopHtml}

    <div class="judul-doc">RISALAH HASIL SIDANG TIM PENGAMAT PEMASYARAKATAN</div>
    <div class="nomor-doc">Nomor : {$nomorRisalah} tanggal {$tanggalRisalah}</div>

    <p style="text-align:justify;margin-bottom:8pt;">
        Berikut disampaikan hasil pertimbangan pada sidang Tim Pengamatan Pemasyarakatan Kantor Wilayah Direktorat Jenderal Pemasyarakatan Banten :
    </p>

    <div style="font-weight:bold;margin-bottom:2pt;">I. Dasar Hukum :</div>
    {$dasarHukumHtml}

    <div style="font-weight:bold;margin-top:6pt;margin-bottom:2pt;">II. Peserta Sidang dan Waktu Pelaksanaan :</div>
    <p style="margin-top:2pt;margin-bottom:6pt;text-align:justify;">
        Anggota Sidang berjumlah {$totalAnggota} ({$totalTerbilang}) orang dan dilaksanakan pada hari {$hariTanggal} pukul {$jamMulai} WIB s/d selesai, sebagai berikut :
    </p>

    <table class="tbl-peserta">
        <thead>
            <tr>
                <th width="8%">No</th>
                <th width="62%">Nama</th>
                <th width="30%">Jabatan</th>
            </tr>
        </thead>
        <tbody>
            {$barisPesertaHtml}
        </tbody>
    </table>

    <div style="page-break-inside:avoid;margin-top:8pt;">
        <div style="font-weight:bold;margin-bottom:2pt;">III. Materi Sidang TPP :</div>
        <p style="margin-top:2pt;margin-bottom:4pt;">Tim Pengamat Pemasyarakatan Kantor Wilayah Ditjenpas Banten menyidangkan :</p>
        {$materiHtml}
    </div>

    <div style="page-break-inside:avoid;margin-top:8pt;">
        <div style="font-weight:bold;margin-bottom:2pt;">IV. Hasil Sidang TPP :</div>
        <p style="margin-top:2pt;margin-bottom:4pt;">Hasil sidang Tim Pengamat Pemasyarakatan Kantor Wilayah Direktorat Jenderal Pemasyarakatan Banten memutuskan :</p>
        {$hasilHtml}
    </div>

    <div style="page-break-inside:avoid;margin-top:8pt;">
        <div style="font-weight:bold;margin-bottom:2pt;">V. Penutup</div>
        <p style="margin-top:2pt;margin-bottom:14pt;text-align:justify;">
            Demikian risalah sidang ini dibuat agar dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <table width="100%" border="0" style="page-break-inside:avoid;margin-top:10pt;">
        <tr>
            <td width="55%"></td>
            <td width="45%" align="center">
                Ketua TPP,<br><br>
                <div style="height:48pt;position:relative;">
                    {$stempelHtml}
                </div>
                <div style="font-weight:bold;text-transform:uppercase;margin-top:6pt;">
                    <u>".htmlspecialchars($sidang->ketua_nama).'</u>
                </div>
                '.(! empty($sidang->ketua_nip) ? '<div style="font-size:9pt;">NIP. '.htmlspecialchars($sidang->ketua_nip).'</div>' : '').'
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Generate berkas Microsoft Word (.doc) untuk Laporan Pelaksanaan Sidang TPP Kanwil.
     */
    public static function generateLaporanWord(SidangTppKanwil $sidang): Response
    {
        $html = self::renderLaporanHtml($sidang, true);

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_laporan ?: $sidang->nomor_sidang);
        $filename = 'Laporan_Pelaksanaan_Sidang_TPP_'.$cleanNomor.'_'.date('Ymd_His').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Generate berkas PDF Resmi Laporan Pelaksanaan Sidang TPP Kanwil Siap Cetak.
     */
    public static function generateLaporanPdf(SidangTppKanwil $sidang): Response
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
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages().' | Laporan Pelaksanaan Sidang TPP Kanwil Banten', 0, 0, 'R');
            }
        };

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor('Kanwil Ditjenpas Banten');
        $pdf->SetTitle('Laporan Pelaksanaan Sidang TPP - '.($sidang->nomor_laporan ?: $sidang->nomor_sidang));
        $pdf->SetMargins(16, 14, 16);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $htmlContent = self::renderLaporanHtml($sidang, false);
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        // Tulis QR Code Otentikasi pada pojok kiri bawah halaman terakhir
        $verifUrl = url('/sidang-tpp-kanwil/'.$sidang->id);
        $currY = $pdf->GetY();
        $targetY = $currY > 245 ? 250 : max(245, $currY + 2);
        $pdf->write2DBarcode($verifUrl, 'QRCODE,M', 16, $targetY, 22, 22, ['border' => false, 'padding' => 0], 'N');

        $cleanNomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sidang->nomor_laporan ?: $sidang->nomor_sidang);
        $filename = 'Laporan_Pelaksanaan_Sidang_TPP_'.$cleanNomor.'_'.date('Ymd_His').'.pdf';

        $output = $pdf->Output($filename, 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Render Template HTML untuk Laporan Pelaksanaan Sidang TPP Kanwil.
     */
    public static function renderLaporanHtml(SidangTppKanwil $sidang, bool $forWord = false): string
    {
        $instansi = Instansi::getFirst();
        $setting = TppKanwilSetting::getFirst();

        $hariTanggal = $sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('l, d F Y') : '-';
        $tanggalLaporan = $sidang->tanggapan_kakanwil_tanggal
            ? $sidang->tanggapan_kakanwil_tanggal->translatedFormat('d F Y')
            : ($sidang->tanggal_sidang ? $sidang->tanggal_sidang->translatedFormat('d F Y') : date('d F Y'));

        $nomorLaporan = $sidang->nomor_laporan ?: (($setting->nomor_laporan_prefix ?? 'WP.12.PK.06.06-').str_pad($sidang->id, 2, '0', STR_PAD_LEFT));

        // Kop Surat
        $logoBase64 = '';
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoBase64 = base64_encode(file_get_contents(public_path($instansi->logo_kop)));
        }

        $kopHtml = "
        <table width='100%' style='border-bottom:2px solid #000;margin-bottom:12pt;padding-bottom:4pt;'>
            <tr>
                <td width='15%' align='center' valign='middle'>
                    ".(! empty($logoBase64) ? "<img src='data:image/png;base64,{$logoBase64}' style='width:62pt;height:auto;' />" : '')."
                </td>
                <td width='85%' align='center' valign='middle' style='line-height:1.2;font-family:Arial,Helvetica,sans-serif;'>
                    <div style='font-size:10pt;font-weight:bold;text-transform:uppercase;'>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>
                    <div style='font-size:10.5pt;font-weight:bold;text-transform:uppercase;'>DIREKTORAT JENDERAL PEMASYARAKATAN</div>
                    <div style='font-size:11.5pt;font-weight:bold;text-transform:uppercase;'>KANTOR WILAYAH BANTEN</div>
                    <div style='font-size:8pt;margin-top:2pt;'>Jl. KH. Sam'un No. 44 Serang, Banten</div>
                    <div style='font-size:7.5pt;'>Telepon: 0822-6666-2044, Faksimilie: 0822-6666-2044</div>
                    <div style='font-size:7.5pt;'>Laman: banten.kemenimipas.go.id, Pos-el: kanwilbanten@kemenimipas.go.id</div>
                </td>
            </tr>
        </table>";

        // Klien Bapas Data
        $kd = $sidang->klien_data ?: [];
        $pokokMateriText = '';
        $hasilPoin3Text = '';

        if ($sidang->materis && $sidang->materis->count() > 0) {
            $pokokMateriText = 'pembahasan '.$sidang->materis->count().' materi/agenda sidang, meliputi: <ol style="margin-top:3pt;padding-left:18pt;">';
            $hasilPoin3Text = 'Tim Sidang TPP secara mufakat telah membahas dan memberikan rekomendasi terhadap seluruh materi sidang: <ol style="margin-top:3pt;padding-left:18pt;">';
            foreach ($sidang->materis as $m) {
                if ($m->isMutasiKeinginanSendiri()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $uptTujuan = $m->uptTujuanRekomendasi?->nama_upt ?? ($nd['upt_tujuan_nama'] ?? 'UPT Tujuan Rekomendasi');
                    $pokokMateriText .= "<li>Permohonan pemindahan Narapidana an. <b>{$namaWbp}</b> ke <b>{$uptTujuan}</b> (Keinginan Sendiri &amp; Biaya Mandiri);</li>";
                    $hasilPoin3Text .= "<li>Materi #{$m->urutan}: Menyetujui dan merekomendasikan pemindahan Narapidana an. <b>{$namaWbp}</b> atas permohonan pribadi ke {$uptTujuan} guna diproses persetujuan resmi Kepala Kantor Wilayah;</li>";
                } elseif ($m->isMutasiNarapidana()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $uptAsal = $nd['upt_asal_nama'] ?? 'Lapas/Rutan Asal';
                    $uptTujuan = $m->uptTujuanRekomendasi?->nama_upt ?? ($nd['upt_tujuan_nama'] ?? 'UPT Tujuan Rekomendasi');
                    $alokasiList = $nd['alokasi'] ?? [];

                    if (! empty($alokasiList) && is_array($alokasiList)) {
                        $grandTot = $nd['total_disetujui'] ?? array_sum(array_column($alokasiList, 'total'));
                        $pokokMateriText .= "<li>Pembahasan usulan pemindahan narapidana kolektif dari <b>{$uptAsal}</b> sejumlah <b>{$grandTot} Orang</b>;</li>";
                        $hasilPoin3Text .= "<li>Materi #{$m->urutan}: Menyetujui usulan pemindahan narapidana kolektif dari <b>{$uptAsal}</b> sejumlah <b>{$grandTot} Orang</b> dengan rekomendasi distribusi alokasi multi-UPT tujuan;</li>";
                    } else {
                        $pokokMateriText .= "<li>Usulan pemindahan Narapidana an. <b>{$namaWbp}</b> ke <b>{$uptTujuan}</b>;</li>";
                        $hasilPoin3Text .= "<li>Materi #{$m->urutan}: Menyetujui dan merekomendasikan pemindahan Narapidana an. <b>{$namaWbp}</b> ke {$uptTujuan} guna diproses persetujuan resmi Kepala Kantor Wilayah;</li>";
                    }
                } elseif ($m->isIzinBerobat()) {
                    $nd = $m->narapidana_data ?: [];
                    $namaWbp = $nd['nama'] ?? $m->judul_materi;
                    $rsNama = $nd['rs_rujukan_nama'] ?? 'RS Rujukan';
                    $pokokMateriText .= "<li>Permohonan izin berobat keluar kota bagi Narapidana an. <b>{$namaWbp}</b> ke <b>{$rsNama}</b>;</li>";
                    $hasilPoin3Text .= "<li>Materi #{$m->urutan}: Menyetujui dan merekomendasikan permohonan izin berobat keluar kota bagi Narapidana an. <b>{$namaWbp}</b> ke {$rsNama};</li>";
                } elseif ($m->isKlienBapas()) {
                    $kd = $m->klien_data ?: [];
                    $namaKlien = $kd['nama_klien'] ?? $m->judul_materi;
                    $keperluan = $kd['keperluan_ln'] ?? 'Izin Bepergian ke Luar Negeri';
                    $pokokMateriText .= "<li>Permohonan izin bepergian ke luar negeri ({$keperluan}) bagi Klien an. <b>{$namaKlien}</b>;</li>";
                    $hasilPoin3Text .= "<li>Materi #{$m->urutan}: Menyetujui dan merekomendasikan izin bepergian ke luar negeri Klien an. <b>{$namaKlien}</b>;</li>";
                } else {
                    $pokokMateriText .= '<li>Pembahasan pokok agenda: <b>'.htmlspecialchars($m->judul_materi).'</b>;</li>';
                    $hasilPoin3Text .= '<li>Materi #{$m->urutan}: Menyetujui pembahasan pokok agenda <b>'.htmlspecialchars($m->judul_materi).'</b>;</li>';
                }
            }
            $pokokMateriText .= '</ol>';
            $hasilPoin3Text .= '</ol>';
        } elseif ($sidang->isMutasiNarapidana() && ! empty($sidang->narapidana_data)) {
            $nd = $sidang->narapidana_data;
            $namaWbp = $nd['nama'] ?? $sidang->agenda_judul;
            $uptTujuan = $nd['upt_tujuan_nama'] ?? ($sidang->uptTujuanRekomendasi?->nama_upt ?? 'UPT Tujuan Rekomendasi');
            $pokokMateriText = "Permohonan Mutasi / Pemindahan Narapidana an. <b>{$namaWbp}</b> ke <b>{$uptTujuan}</b> (Alasan: ".($nd['alasan_pemindahan'] ?? 'Mendekati Keluarga').').';
            $hasilPoin3Text = "Tim Sidang TPP secara mufakat menyetujui dan merekomendasikan pemindahan narapidana an. <b>{$namaWbp}</b> ke {$uptTujuan} untuk diproses lebih lanjut guna diterbitkan persetujuan resmi Kepala Kantor Wilayah.";
        } elseif ($sidang->isIzinBerobat() && ! empty($sidang->narapidana_data)) {
            $nd = $sidang->narapidana_data;
            $namaWbp = $nd['nama'] ?? $sidang->agenda_judul;
            $rsNama = $nd['rs_rujukan_nama'] ?? 'RS Rujukan';
            $rsKota = $nd['rs_rujukan_kota'] ?? 'Luar Kota';
            $pokokMateriText = "Permohonan Izin Berobat Keluar Kota bagi Narapidana an. <b>{$namaWbp}</b> ke <b>{$rsNama} ({$rsKota})</b> (Diagnosa: ".($nd['diagnosa_medis'] ?? 'Rujukan Spesialis').').';
            $hasilPoin3Text = "Tim Sidang TPP secara mufakat menyetujui dan merekomendasikan permohonan izin berobat keluar kota bagi narapidana an. <b>{$namaWbp}</b> ke {$rsNama} guna diteruskan ke Ditjenpas / Ka. Kanwil.";
        } elseif ($sidang->is_klien_bapas && ! empty($kd)) {
            $namaKlien = $kd['nama_klien'] ?? $sidang->agenda_judul;
            $bin = ! empty($kd['bin']) ? ' BIN '.strtoupper($kd['bin']) : '';
            $keperluan = $kd['keperluan_ln'] ?? 'Izin Bepergian ke Luar Negeri';
            $negara = $kd['negara_tujuan'] ?? 'Kerajaan Arab Saudi';
            $program = $kd['program_integrasi'] ?? 'Pembebasan Bersyarat';
            $bapas = $kd['bapas_nama'] ?? 'Bapas Kelas I Serang';

            $pokokMateriText = "Permohonan Izin Bepergian ke Luar Negeri ({$keperluan} ke {$negara}) bagi Klien {$program} {$bapas} an. <b>{$namaKlien}{$bin}</b>.";
            $hasilPoin3Text = "Tim Sidang TPP secara mufakat menyetujui dan merekomendasikan permohonan izin bepergian ke luar negeri Klien Pemasyarakatan an. <b>{$namaKlien}{$bin}</b> untuk diproses lebih lanjut guna diterbitkan persetujuan resmi Kepala Kantor Wilayah.";
        } else {
            $pokokMateriText = '<b>'.htmlspecialchars($sidang->agenda_judul).'</b> ('.htmlspecialchars($sidang->agenda_kategori).').';
            $hasilPoin3Text = 'Tim TPP secara mufakat menyetujui dan merekomendasikan usulan pembinaan bagi narapidana untuk diproses lebih lanjut sesuai dengan ketentuan perundang-undangan yang berlaku.';
        }

        // Dokumen Kustom AI / User Overrides
        $customLap = $sidang->dokumen_custom['laporan'] ?? null;

        // Bagian A: Kegiatan yang dilaksanakan
        if (! empty($customLap['kegiatan_dilaksanakan'])) {
            if (is_array($customLap['kegiatan_dilaksanakan'])) {
                $kegiatanHtml = '';
                foreach ($customLap['kegiatan_dilaksanakan'] as $p) {
                    $kegiatanHtml .= '<p class="par-body">'.nl2br(htmlspecialchars($p)).'</p>';
                }
            } else {
                $paragraphs = preg_split('/\n\s*\n/', trim($customLap['kegiatan_dilaksanakan']));
                $kegiatanHtml = '';
                foreach ($paragraphs as $p) {
                    $kegiatanHtml .= '<p class="par-body">'.nl2br(htmlspecialchars(trim($p))).'</p>';
                }
            }
        } else {
            $kegiatanHtml = '
            <p class="par-body">
                Sebagai wujud nyata akuntabilitas dalam pelaksanaan fungsi pengawasan teknis pemasyarakatan, perwakilan Tim Sidang TPP Kantor Wilayah Ditjenpas Banten menyelenggarakan persidangan Tim Pengamat Pemasyarakatan (TPP) pada hari <b>'.$hariTanggal.'</b> bertempat di <b>'.$sidang->tempat.'</b>.
            </p>
            <p class="par-body">
                Sidang TPP ini memegang peranan krusial sebagai dapur kebijakan internal Kantor Wilayah dalam mengevaluasi kelayakan program pembinaan narapidana dan bimbingan klien pemasyarakatan secara komprehensif. Pembahasan diarahkan untuk memastikan bahwa setiap usulan hak bersyarat maupun perizinan didasarkan pada penilaian objektif terhadap perkembangan perilaku, penurunan tingkat risiko, serta pemenuhan kewajiban substantif dan administratif, guna mendukung tercapainya tujuan akhir Sistem Pemasyarakatan yaitu mengembalikan warga binaan dan klien pemasyarakatan sebagai warga yang baik dan bertanggung jawab.
            </p>
            <p class="par-body">
                Kehadiran perwakilan Kantor Wilayah dalam forum ini juga menegaskan fungsi pembinaan dan pengawasan agar seluruh tahapan pengambilan keputusan berjalan secara transparan, akuntabel, serta terbebas dari segala bentuk diskriminasi maupun pungutan liar. Adapun pokok materi sidang TPP kali ini adalah '.$pokokMateriText.'
            </p>
            <p class="par-body">
                Terhadap seluruh materi usulan yang diajukan tersebut, perwakilan Kantor Wilayah melakukan pencermatan mendalam terhadap kelengkapan berkas administratif dan substantif, laporan Penelitian Kemasyarakatan dari Balai Pemasyarakatan, serta keabsahan penjamin dan jaminan keluarga guna memitigasi segala potensi risiko keamanan.
            </p>';
        }

        // Bagian B: Hasil yang dicapai
        if (! empty($customLap['hasil_dicapai'])) {
            $hasilDicapaiItems = (array) $customLap['hasil_dicapai'];
            $hasilListHtml = '';
            foreach ($hasilDicapaiItems as $item) {
                $cleanItem = preg_replace('/^\d+[\.\)]\s*/', '', trim($item));
                $hasilListHtml .= '<li style="margin-bottom:3pt;text-align:justify;">'.nl2br(htmlspecialchars($cleanItem)).'</li>';
            }
        } else {
            $hasilListHtml = '
            <li style="margin-bottom:3pt;text-align:justify;">
                Seluruh narapidana / klien pemasyarakatan yang diusulkan telah menunjukkan perkembangan pembinaan kepribadian dan kemandirian yang baik serta memenuhi syarat substantif.
            </li>
            <li style="margin-bottom:3pt;text-align:justify;">
                Kelengkapan dokumen administratif seperti kutipan putusan hakim, register, laporan perkembangan pembinaan, hasil asesmen risiko, Litmas PK Bapas, dan surat jaminan keluarga telah siap dan terverifikasi secara sah.
            </li>
            <li style="margin-bottom:3pt;text-align:justify;">
                '.$hasilPoin3Text.'
            </li>';
        }

        // Bagian C: Simpulan dan saran
        if (! empty($customLap['simpulan'])) {
            $simpulanItems = (array) $customLap['simpulan'];
            $simpulanListHtml = '';
            foreach ($simpulanItems as $item) {
                $cleanItem = preg_replace('/^\d+[\.\)]\s*/', '', trim($item));
                $simpulanListHtml .= '<li style="margin-bottom:2pt;text-align:justify;">'.nl2br(htmlspecialchars($cleanItem)).'</li>';
            }
        } else {
            $simpulanListHtml = '
            <li style="margin-bottom:2pt;text-align:justify;">Penyelenggaraan Sidang TPP di Tingkat Kantor Wilayah Banten telah dilaksanakan secara tertib, objektif, dan sesuai dengan standar operasional prosedur (SOP).</li>
            <li style="margin-bottom:2pt;text-align:justify;">Pertimbangan pembinaan dan aspek keamanan telah diintegrasikan secara seimbang dalam memutuskan kelayakan usulan hak integrasi maupun perizinan warga binaan/klien.</li>';
        }

        if (! empty($customLap['saran'])) {
            $saranItems = (array) $customLap['saran'];
            $saranListHtml = '';
            foreach ($saranItems as $item) {
                $cleanItem = preg_replace('/^\d+[\.\)]\s*/', '', trim($item));
                $saranListHtml .= '<li style="margin-bottom:2pt;text-align:justify;">'.nl2br(htmlspecialchars($cleanItem)).'</li>';
            }
        } else {
            $saranListHtml = '
            <li style="margin-bottom:2pt;text-align:justify;">Jajaran UPT / Bapas agar senantiasa teliti dalam mengunggah data dan dokumen usulan ke dalam Sistem Database Pemasyarakatan guna menghindari pengembalian berkas.</li>
            <li style="margin-bottom:2pt;text-align:justify;">Meningkatkan monitoring dan evaluasi secara berkala pasca-sidang serta memperkuat koordinasi dengan Kantor Wilayah dalam mengawal proses pembimbingan dan kepatuhan klien.</li>';
        }

        // Tanda Tangan & Stempel Kakanwil
        $kakanwilNama = $sidang->kakanwil_nama ?: ($setting->kakanwil_nama ?? ($instansi?->nama_kakanwil ?? 'Dody Fahrudin, Bc.IP., S.H., M.H.'));
        $kakanwilNip = $sidang->kakanwil_nip ?: ($setting->kakanwil_nip ?? ($instansi?->nip_kakanwil ?? '197205141994031001'));

        $ttdKakanwilBase64 = '';
        if ($setting->auto_embed_ttd && ! empty($setting->kakanwil_ttd_image) && file_exists(public_path($setting->kakanwil_ttd_image))) {
            $ttdKakanwilBase64 = base64_encode(file_get_contents(public_path($setting->kakanwil_ttd_image)));
        }

        $stempelBase64 = '';
        if ($setting->auto_embed_ttd && ! empty($setting->kanwil_stempel_image) && file_exists(public_path($setting->kanwil_stempel_image))) {
            $stempelBase64 = base64_encode(file_get_contents(public_path($setting->kanwil_stempel_image)));
        }

        // Susun blok stempel & ttd
        $kakanwilTtdHtml = '';
        if (! empty($stempelBase64) || ! empty($ttdKakanwilBase64)) {
            $stempelImg = ! empty($stempelBase64) ? "<img src='data:image/png;base64,{$stempelBase64}' style='width:68pt;height:auto;position:relative;margin-bottom:-45pt;margin-left:-25pt;opacity:0.85;' />" : '';
            $ttdImg = ! empty($ttdKakanwilBase64) ? "<img src='data:image/png;base64,{$ttdKakanwilBase64}' style='width:75pt;height:auto;position:relative;margin-top:-20pt;' />" : '';
            $kakanwilTtdHtml = "
            <div style='height:50pt;position:relative;'>
                {$stempelImg}
                {$ttdImg}
            </div>";
        } else {
            $kakanwilTtdHtml = '<div style="height:50pt;"></div>';
        }

        // Dokumentasi Foto
        $fotoHtml1 = '';
        if (! empty($sidang->foto_dokumentasi_1) && file_exists(public_path($sidang->foto_dokumentasi_1))) {
            $f1Base64 = base64_encode(file_get_contents(public_path($sidang->foto_dokumentasi_1)));
            $fotoHtml1 = "<img src='data:image/jpeg;base64,{$f1Base64}' style='max-width:100%;height:115pt;object-fit:cover;border-radius:3pt;' />";
        } else {
            $fotoHtml1 = "<div style='height:100pt;border:1px dashed #aaa;display:flex;align-items:center;justify-content:center;font-size:9pt;color:#666;padding-top:35pt;'>[ Dokumentasi Sidang Deliberasi TPP Kanwil ]</div>";
        }

        $fotoHtml2 = '';
        if (! empty($sidang->foto_dokumentasi_2) && file_exists(public_path($sidang->foto_dokumentasi_2))) {
            $f2Base64 = base64_encode(file_get_contents(public_path($sidang->foto_dokumentasi_2)));
            $fotoHtml2 = "<img src='data:image/jpeg;base64,{$f2Base64}' style='max-width:100%;height:115pt;object-fit:cover;border-radius:3pt;' />";
        } else {
            $fotoHtml2 = "<div style='height:100pt;border:1px dashed #aaa;display:flex;align-items:center;justify-content:center;font-size:9pt;color:#666;padding-top:35pt;'>[ Dokumentasi Verifikasi Berkas &amp; Paparan PK/UPT ]</div>";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pelaksanaan Sidang TPP Kanwil</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; line-height: 1.35; color: #000; }
        .judul-lap { text-align: center; font-size: 12.5pt; font-weight: bold; margin-bottom: 2pt; text-transform: uppercase; }
        .nomor-lap { text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 14pt; }
        .sec-title { font-weight: bold; margin-top: 10pt; margin-bottom: 4pt; }
        p.par-body { text-align: justify; text-indent: 25pt; margin-bottom: 6pt; }
    </style>
</head>
<body>
    {$kopHtml}

    <div class="judul-lap">LAPORAN PELAKSANAAN SIDANG TPP</div>
    <div class="nomor-lap">Nomor : {$nomorLaporan}</div>

    <div class="sec-title">A. KEGIATAN YANG DILAKSANAKAN</div>
    {$kegiatanHtml}

    <div class="sec-title">B. HASIL YANG DICAPAI</div>
    <p style="text-align:justify;margin-bottom:4pt;">
        Berdasarkan hasil telaahan mendalam terhadap jalannya persidangan serta dokumen yang disajikan oleh Tim Sidang TPP Kantor Wilayah, Perwakilan Kantor Wilayah berpendapat bahwa:
    </p>
    <ol style="margin-top:2pt;padding-left:18pt;line-height:1.4;">
        {$hasilListHtml}
    </ol>

    <div class="sec-title">C. SIMPULAN DAN SARAN</div>
    <div style="font-weight:bold;margin-left:10pt;margin-bottom:2pt;">Simpulan :</div>
    <ol style="margin-top:2pt;padding-left:25pt;line-height:1.35;">
        {$simpulanListHtml}
    </ol>

    <div style="font-weight:bold;margin-left:10pt;margin-top:4pt;margin-bottom:2pt;">Saran :</div>
    <ol style="margin-top:2pt;padding-left:25pt;line-height:1.35;">
        {$saranListHtml}
    </ol>

    <div class="sec-title">D. PENUTUP</div>
    <p class="par-body">
        Demikian laporan hasil pengawasan dan penelaahan Sidang TPP ini disusun secara objektif dan akuntabel sebagai bahan pertanggungjawaban serta masukan kebijakan lebih lanjut kepada pimpinan.
    </p>

    <div style="page-break-inside:avoid;margin-top:10pt;">
        <div class="sec-title">E. DOKUMENTASI</div>
        <table width="100%" border="0" style="margin-bottom:12pt;">
            <tr>
                <td width="49%" align="center" style="border:1px solid #ccc;padding:4pt;background-color:#fafafa;">
                    {$fotoHtml1}
                    <div style="font-size:7.5pt;color:#555;margin-top:3pt;">Dokumentasi 1: Persidangan &amp; Deliberasi Sidang TPP Kanwil</div>
                </td>
                <td width="2%"></td>
                <td width="49%" align="center" style="border:1px solid #ccc;padding:4pt;background-color:#fafafa;">
                    {$fotoHtml2}
                    <div style="font-size:7.5pt;color:#555;margin-top:3pt;">Dokumentasi 2: Verifikasi Berkas Substantif &amp; Kesepakatan Tim</div>
                </td>
            </tr>
        </table>

        <!-- Tanda Tangan Ganda -->
        <table width="100%" border="0" style="page-break-inside:avoid;">
            <tr>
                <td width="50%" align="center" valign="top">
                    Perwakilan Kantor Wilayah<br>
                    <b>Ketua Sidang TPP,</b><br><br>
                    <div style="height:48pt;"></div>
                    <div style="font-weight:bold;text-decoration:underline;">
                        ".htmlspecialchars($sidang->ketua_nama).'
                    </div>
                    '.(! empty($sidang->ketua_nip) ? '<div style="font-size:8.5pt;">NIP. '.htmlspecialchars($sidang->ketua_nip).'</div>' : '').'
                </td>
                <td width="50%" align="center" valign="top">
                    Serang, '.$tanggalLaporan.'<br>
                    <b>Kepala Kantor Wilayah,</b><br><br>
                    '.$kakanwilTtdHtml.'
                    <div style="font-weight:bold;text-decoration:underline;">
                        '.htmlspecialchars($kakanwilNama).'
                    </div>
                    '.(! empty($kakanwilNip) ? '<div style="font-size:8.5pt;">NIP. '.htmlspecialchars($kakanwilNip).'</div>' : '').'
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Helper Konversi Angka Menjadi Terbilang Bahasa Indonesia.
     */
    public static function terbilang(int $angka): string
    {
        $bilangan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($angka < 12) {
            return $bilangan[$angka];
        } elseif ($angka < 20) {
            return self::terbilang($angka - 10).' belas';
        } elseif ($angka < 100) {
            return self::terbilang((int) ($angka / 10)).' puluh '.self::terbilang($angka % 10);
        }

        return (string) $angka;
    }
}
