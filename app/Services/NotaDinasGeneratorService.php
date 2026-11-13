<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Http\Response;

class NotaDinasGeneratorService
{
    /**
     * Generate berkas Microsoft Word (.doc) untuk Nota Dinas Permohonan Surat Tugas.
     *
     * @param  array<string, mixed>  $params
     */
    public static function generateWord(array $params): Response
    {
        $html = self::renderHtmlTemplate($params);

        $cleanUpt = preg_replace('/[^a-zA-Z0-9_-]/', '_', $params['upt_nama'] ?? 'UPT');
        $filename = 'Nota_Dinas_Surat_Tugas_'.$cleanUpt.'_'.date('Ymd_His').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-word; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Render tampilan HTML cetak pratinjau Nota Dinas.
     *
     * @param  array<string, mixed>  $params
     */
    public static function renderHtmlTemplate(array $params): string
    {
        $setting = AiSetting::getFirst();

        $nomorNotaDinas = ! empty($params['nomor_nota_dinas'])
            ? $params['nomor_nota_dinas']
            : ($setting->nomor_surat_prefix ?? 'WP.12-UM.01.01-');

        $yth = ! empty($params['yth_tujuan'])
            ? $params['yth_tujuan']
            : ($setting->yth_tujuan ?? 'Kepala Bagian Tata Usaha dan Umum');

        $dari = ! empty($params['dari_pengirim'])
            ? $params['dari_pengirim']
            : ($setting->dari_pengirim ?? 'Kepala Bidang Pelayanan dan Pembinaan');

        $hal = ! empty($params['hal_surat'])
            ? $params['hal_surat']
            : ('Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP di '.($params['upt_nama'] ?? 'UPT'));

        $tanggalNota = ! empty($params['tanggal_nota_dinas'])
            ? $params['tanggal_nota_dinas']
            : date('d F Y');

        $nomorUndangan = $params['nomor_undangan'] ?? 'WP.12.PAS...';
        $tanggalSuratUpt = $params['tanggal_surat_upt'] ?? '-';
        $halUndangan = $params['hal_undangan'] ?? 'Undangan Sidang TPP';
        $uptNama = $params['upt_nama'] ?? 'Lapas/Rutan';

        $hariTanggal = $params['hari_tanggal_pelaksanaan'] ?? '-';
        $pukul = $params['jam_pelaksanaan'] ?? '09.00 WIB s.d selesai';
        $tempat = $params['tempat_pelaksanaan'] ?? 'Ruang Sidang TPP';

        $pegawaiList = $params['pegawai_list'] ?? [];

        $penandatanganNama = ! empty($params['penandatangan_nama'])
            ? $params['penandatangan_nama']
            : ($setting->penandatangan_nama ?? 'Surya Dharma');

        $penandatanganJabatan = ! empty($params['penandatangan_jabatan'])
            ? $params['penandatangan_jabatan']
            : ($setting->penandatangan_jabatan ?? 'Kepala Bidang Pelayanan dan Pembinaan');

        // Toggle NIP Penandatangan: jika true tampilkan NIP, jika false (default saat ini) jangan tampilkan NIP
        $tampilkanNip = isset($params['tampilkan_nip'])
            ? filter_var($params['tampilkan_nip'], FILTER_VALIDATE_BOOLEAN)
            : (bool) ($setting->tampilkan_nip ?? false);

        $penandatanganNip = $params['penandatangan_nip'] ?? ($setting->penandatangan_nip ?? '');

        // Narasi Pengantar / Rujukan Nota Dinas yang dinamis & sesuai tata naskah
        $narasiPengantar = ! empty($params['narasi_pengantar'])
            ? trim((string) $params['narasi_pengantar'])
            : "Menindaklanjuti Surat Kepala {$uptNama} Nomor {$nomorUndangan} tanggal {$tanggalSuratUpt} perihal {$halUndangan}, dalam rangka pelaksanaan pembinaan, monitoring, pengawasan, dan pengendalian pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) yang diselenggarakan di {$tempat}, bersama ini kami mohon perkenan Bapak/Ibu untuk dapat menerbitkan Surat Tugas bagi pejabat/pegawai Kantor Wilayah sebagai berikut:";

        // Narasi Transisi Rincian Pelaksanaan & Penutup Dokumen
        $narasiPelaksanaan = ! empty($params['narasi_pelaksanaan'])
            ? trim((string) $params['narasi_pelaksanaan'])
            : 'Adapun pelaksanaan kegiatan pembinaan dan pengawasan sidang TPP dimaksud dilaksanakan dengan rincian jadwal dan tempat sebagai berikut :';

        $penutup = ! empty($params['penutup'])
            ? trim((string) $params['penutup'])
            : 'Demikian kami sampaikan, mohon perkenan dan petunjuk lebih lanjut. Atas perhatian dan kerja samanya diucapkan terima kasih.';

        // Render baris tabel pegawai
        $pegawaiRows = '';
        foreach ($pegawaiList as $idx => $p) {
            $no = $idx + 1;
            $nama = htmlspecialchars($p['nama'] ?? '-');
            $nip = htmlspecialchars($p['nip'] ?? '-');
            $jabatan = htmlspecialchars($p['jabatan'] ?? '-');

            $pegawaiRows .= "
            <tr style='height:25pt'>
                <td align='center' style='border:solid windowtext 1.0pt;border-top:none;padding:3pt 5pt;'>{$no}.</td>
                <td style='border:solid windowtext 1.0pt;border-top:none;border-left:none;padding:3pt 5pt;'>{$nama}</td>
                <td align='center' style='border:solid windowtext 1.0pt;border-top:none;border-left:none;padding:3pt 5pt;'>{$nip}</td>
                <td style='border:solid windowtext 1.0pt;border-top:none;border-left:none;padding:3pt 5pt;'>{$jabatan}</td>
            </tr>";
        }

        if (empty($pegawaiRows)) {
            $pegawaiRows = "
            <tr style='height:25pt'>
                <td colspan='4' align='center' style='border:solid windowtext 1.0pt;border-top:none;padding:8pt;color:#666;'>
                    <em>Belum ada personil pegawai yang dipilih</em>
                </td>
            </tr>";
        }

        $nipSignatureHtml = ($tampilkanNip && ! empty($penandatanganNip))
            ? "<div style='font-family:Arial,sans-serif;font-size:11pt;margin-top:2pt;'>NIP. {$penandatanganNip}</div>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Nota Dinas Permohonan Surat Tugas</title>
<style>
    @page WordSection1 {
        size: 595.3pt 841.9pt; /* A4 */
        margin: 42.5pt 42.5pt 56.7pt 72.0pt;
        mso-header-margin: 36.0pt;
        mso-footer-margin: 36.0pt;
        mso-paper-source: 0;
    }
    div.WordSection1 {
        page: WordSection1;
        font-family: Arial, sans-serif;
        font-size: 11pt;
        line-height: 1.35;
        color: #000;
    }
    p, table, td {
        font-family: Arial, sans-serif;
    }
    .header-instansi {
        text-align: center;
        font-weight: bold;
        font-size: 12pt;
        line-height: 1.25;
        margin-bottom: 2pt;
    }
    .judul-nota {
        text-align: center;
        font-weight: bold;
        font-size: 14pt;
        margin-top: 15pt;
        margin-bottom: 2pt;
        text-decoration: underline;
    }
    .nomor-nota {
        text-align: center;
        font-size: 11pt;
        margin-bottom: 12pt;
    }
    .table-info {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10pt;
    }
    .table-info td {
        padding: 2pt 4pt;
        vertical-align: top;
        font-size: 11pt;
    }
    .divider {
        border-bottom: 2px solid #000;
        margin-bottom: 12pt;
    }
    .content-p {
        text-align: justify;
        text-indent: 36pt;
        line-height: 1.5;
        margin-bottom: 10pt;
    }
    .table-pegawai {
        width: 100%;
        border-collapse: collapse;
        margin: 10pt 0 14pt 0;
    }
    .table-pegawai th {
        background-color: #f2f2f2;
        border: 1pt solid #000;
        padding: 5pt;
        font-weight: bold;
        text-align: center;
        font-size: 11pt;
    }
    .table-jadwal {
        margin-left: 35pt;
        border-collapse: collapse;
        margin-bottom: 12pt;
    }
    .table-jadwal td {
        padding: 2pt 4pt;
        vertical-align: top;
        font-size: 11pt;
        line-height: 1.4;
    }
    .signature-area {
        float: right;
        width: 300pt;
        margin-top: 15pt;
        text-align: left;
    }
</style>
</head>
<body>
<div class="WordSection1">
    <!-- Kop Surat Resmi -->
    <div class="header-instansi">KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN RI</div>
    <div class="header-instansi">DIREKTORAT JENDERAL PEMASYARAKATAN</div>
    <div class="header-instansi">KANTOR WILAYAH BANTEN</div>

    <div class="judul-nota">NOTA DINAS</div>
    <div class="nomor-nota">NOMOR : {$nomorNotaDinas}</div>

    <!-- Metadata Surat -->
    <table class="table-info">
        <tr>
            <td style="width: 75pt; font-weight: bold;">Yth.</td>
            <td style="width: 15pt;">:</td>
            <td>{$yth}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Dari</td>
            <td>:</td>
            <td>{$dari}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Hal</td>
            <td>:</td>
            <td style="text-align: justify;">{$hal}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Lampiran</td>
            <td>:</td>
            <td>1 (satu) berkas</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tanggal</td>
            <td>:</td>
            <td>{$tanggalNota}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Isi Rujukan / Narasi Pengantar -->
    <p class="content-p">
        {$narasiPengantar}
    </p>

    <!-- Tabel Daftar Personil -->
    <table class="table-pegawai" border="1" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th style="width: 30pt;">No</th>
                <th style="width: 180pt;">Nama</th>
                <th style="width: 140pt;">NIP</th>
                <th>Jabatan</th>
            </tr>
        </thead>
        <tbody>
            {$pegawaiRows}
        </tbody>
    </table>

    <!-- Rincian Pelaksanaan -->
    <p style="margin-bottom: 4pt; font-weight: normal;">{$narasiPelaksanaan}</p>
    <table class="table-jadwal" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 90pt;">Hari/tanggal</td>
            <td style="width: 15pt;">:</td>
            <td>{$hariTanggal}</td>
        </tr>
        <tr>
            <td>Pukul</td>
            <td>:</td>
            <td>{$pukul}</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td>{$tempat}</td>
        </tr>
    </table>

    <!-- Penutup -->
    <p class="content-p" style="margin-top: 10pt;">
        {$penutup}
    </p>

    <!-- Tanda Tangan -->
    <table style="width: 100%; border: none; margin-top: 25pt;">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%;">
                <div style="font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.3;">
                    {$penandatanganJabatan}
                    <br><br><br><br><br>
                    <b><u>{$penandatanganNama}</u></b>
                    {$nipSignatureHtml}
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
HTML;
    }
}
