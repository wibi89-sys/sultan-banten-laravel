<?php

namespace App\Services;

use App\Models\DataIntegrasi;
use App\Models\Instansi;
use App\Models\Upt;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PdfReportService
{
    public static function generate(string $reportType, ?int $uptId, string $tanggalMulai, string $tanggalAkhir, array $signData = [])
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $instansi = Instansi::getFirst();
        $user = auth()->user();

        $reportTitles = [
            'penghuni' => 'LAPORAN DATA PENGHUNI & OVERKAPASITAS',
            'overstaying' => 'LAPORAN OVERSTAYING TAHANAN',
            'integrasi' => 'LAPORAN DATA INTEGRASI',
            'bebas_harian' => 'LAPORAN PENGELUARAN / BEBAS HARIAN',
            'rekapitulasi' => 'LAPORAN REKAPITULASI',
        ];

        $orientation = in_array($reportType, ['overstaying', 'penghuni', 'bebas_harian']) ? 'P' : 'L';

        // Instantiate TCPDF
        $pdf = new class($orientation, 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public $instansiModel = null;

            public $reportType = 'penghuni';

            public $showQrCode = true;

            public function Header() {}

            public function Footer()
            {
                if ($this->showQrCode) {
                    $this->SetY(-20);
                    $y = $this->GetY();
                    $qrText = "✅ Dokumen Valid\ndicetak melalui :\nSULTAN BANTEN @Ditjenpas Banten";
                    $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
                    $this->write2DBarcode($qrText, 'QRCODE,L', 15, $y, 15, 15, $style, 'N');
                }

                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 7);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages(), 0, 0, 'C');
                $this->SetTextColor(0, 0, 0);
            }
        };

        $pdf->instansiModel = $instansi;
        $pdf->reportType = $reportType;
        $pdf->showQrCode = ! empty($signData['show_qrcode']);
        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor($user->nama_lengkap ?? 'Admin');
        $pdf->SetTitle($reportTitles[$reportType] ?? 'Laporan');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();

        $isKanwil = $user ? $user->isKanwil() : false;
        $isUpt = ! $isKanwil || ! empty($signData['is_upt']);
        if ($isKanwil) {
            $isUpt = false;
        }
        $uptModel = $uptId ? Upt::find($uptId) : null;

        // Header / Kop
        if ($isUpt) {
            // Mode UPT: Tanpa Kop Kanwil, langsung judul laporan & nama Satker UPT
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, $reportTitles[$reportType] ?? 'LAPORAN', 0, 1, 'C');
            if ($uptModel) {
                $pdf->SetFont('helvetica', 'B', 10.5);
                $pdf->Cell(0, 5, strtoupper($uptModel->nama_upt), 0, 1, 'C');
            }
            $pdf->Ln(1);
            $pdf->SetLineWidth(0.4);
            $lineEnd = in_array($reportType, ['overstaying', 'penghuni', 'bebas_harian']) ? 200 : 287;
            $pdf->Line(10, $pdf->GetY(), $lineEnd, $pdf->GetY());
            $pdf->Ln(4);
        } elseif (in_array($reportType, ['overstaying', 'penghuni', 'bebas_harian'])) {
            $logoKop = null;
            if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
                $logoKop = public_path($instansi->logo_kop);
            } elseif (! empty($instansi?->logo) && file_exists(public_path($instansi->logo))) {
                $logoKop = public_path($instansi->logo);
            }

            if ($logoKop) {
                $pdf->Image($logoKop, 15, 10, 25);
            }

            $wKop = 155;
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetX(45);
            $pdf->Cell($wKop, 5, 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', 0, 1, 'C');
            $pdf->SetX(45);
            $pdf->Cell($wKop, 5, 'DIREKTORAT JENDERAL PEMASYARAKATAN', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetX(45);
            $pdf->Cell($wKop, 5, 'KANTOR WILAYAH BANTEN', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetX(45);
            $pdf->Cell($wKop, 5, "Jl.KH.Sam'un No.44 Serang, Banten", 0, 1, 'C');
            $pdf->SetX(45);
            $pdf->Cell($wKop, 5, 'Telepon: 0822-6666-2044, Faksimilie: 0822-6666-2044', 0, 1, 'C');
            $pdf->SetX(45);
            $pdf->Cell($wKop, 5, 'Laman: djpasbanten@gmail.com, Pos-el: djpasbanten@gmail.com', 0, 1, 'C');

            $pdf->Ln(2);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(5);

            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, $reportTitles[$reportType] ?? 'LAPORAN', 0, 1, 'C');
            $pdf->Cell(0, 6, 'UPT PEMASYARAKATAN KANTOR WILAYAH BANTEN', 0, 1, 'C');
            $pdf->Ln(2);
        } else {
            // Mode Kanwil Landscape (Integrasi & Rekapitulasi)
            $logoKop = null;
            if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
                $logoKop = public_path($instansi->logo_kop);
            } elseif (! empty($instansi?->logo) && file_exists(public_path($instansi->logo))) {
                $logoKop = public_path($instansi->logo);
            }

            if ($logoKop) {
                $pdf->Image($logoKop, 15, 10, 22);
            }

            $wKop = 230;
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4.5, 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', 0, 1, 'C');
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4.5, 'DIREKTORAT JENDERAL PEMASYARAKATAN', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetX(40);
            $pdf->Cell($wKop, 5, 'KANTOR WILAYAH BANTEN', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4, "Jl.KH.Sam'un No.44 Serang, Banten | Telepon: 0822-6666-2044 | Faksimilie: 0822-6666-2044", 0, 1, 'C');
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4, 'Laman: djpasbanten@gmail.com | Pos-el: djpasbanten@gmail.com', 0, 1, 'C');

            $pdf->Ln(2);
            $pdf->SetLineWidth(0.6);
            $pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
            $pdf->SetLineWidth(0.2);
            $pdf->Line(10, $pdf->GetY() + 0.8, 287, $pdf->GetY() + 0.8);
            $pdf->Ln(3);

            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 5, $reportTitles[$reportType] ?? 'LAPORAN', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 9.5);
            $pdf->Cell(0, 5, 'UPT PEMASYARAKATAN KANTOR WILAYAH BANTEN', 0, 1, 'C');
            $pdf->Ln(1);
        }

        // Fetch Data & Draw Table
        self::renderReportContent($pdf, $reportType, $uptId, $tanggalMulai, $tanggalAkhir, $instansi, $signData);

        $filename = "Laporan_{$reportType}_".date('Ymd_His').'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private static function renderReportContent($pdf, $reportType, $uptId, $tanggalMulai, $tanggalAkhir, $instansi, $signData)
    {
        // Date String
        $hariTanggalStr = 'Semua Periode';
        if (! empty($tanggalMulai) && ! empty($tanggalAkhir)) {
            if ($tanggalMulai == $tanggalAkhir) {
                $hariTanggalStr = formatIndonesianDate($tanggalMulai, true);
            } else {
                $hariTanggalStr = formatIndonesianDate($tanggalMulai, false).' s/d '.formatIndonesianDate($tanggalAkhir, false);
            }
        } elseif (! empty($tanggalMulai)) {
            $hariTanggalStr = 'Mulai '.formatIndonesianDate($tanggalMulai, false);
        } elseif (! empty($tanggalAkhir)) {
            $hariTanggalStr = 'Sampai '.formatIndonesianDate($tanggalAkhir, false);
        }

        if ($reportType === 'penghuni') {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Hari/Tanggal :', 0, 1, 'L');
            $pdf->Cell(0, 5, $hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            $widths = [10, 65, 18, 18, 20, 20, 20, 19];
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[0], 8, 'No', 1, 0, 'C', true);
            $pdf->Cell($widths[1], 8, 'Unit Pelaksana Teknis', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 8, 'Kapasitas', 1, 0, 'C', true);
            $pdf->Cell($widths[3], 8, 'Tahanan', 1, 0, 'C', true);
            $pdf->Cell($widths[4], 8, 'Narapidana', 1, 0, 'C', true);
            $pdf->Cell($widths[5], 8, 'Total', 1, 0, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell($widths[6], 8, "Tingkat\nHunian", 1, 'C', true, 0, $x, $y, true, 0, false, true, 8, 'M');
            $pdf->Cell($widths[7], 8, '% Over', 1, 1, 'C', true);

            // Fetch data
            $exclude = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
            $q = DB::table('upt as u')
                ->join('data_penghuni as dp', 'u.id', '=', 'dp.upt_id')
                ->whereNotIn('dp.klasifikasi_pidana', $exclude);

            if ($uptId) {
                $q->where('u.id', $uptId);
            }
            if ($tanggalMulai && $tanggalAkhir) {
                $q->whereBetween('dp.tanggal', [$tanggalMulai, $tanggalAkhir]);
            }

            $data = $q->select(
                'u.nama_upt', 'u.kapasitas', 'dp.tanggal',
                DB::raw('COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan), 0) as total_tahanan'),
                DB::raw('COALESCE(SUM(dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_narapidana'),
                DB::raw('COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni')
            )->groupBy('u.id', 'u.nama_upt', 'u.kapasitas', 'dp.tanggal')
                ->orderBy('u.nama_upt')->orderBy('dp.tanggal')->get();

            $no = 1;
            $gKapasitas = 0;
            $gTahanan = 0;
            $gNapi = 0;
            $gTotal = 0;
            $pdf->SetFont('helvetica', '', 8);

            foreach ($data as $r) {
                $kap = (int) $r->kapasitas;
                $tah = (int) $r->total_tahanan;
                $nap = (int) $r->total_narapidana;
                $tot = (int) $r->total_penghuni;

                $gKapasitas += $kap;
                $gTahanan += $tah;
                $gNapi += $nap;
                $gTotal += $tot;
                $persen = $kap > 0 ? round((($tot - $kap) / $kap) * 100, 1) : 0;
                $tingkat = $kap > 0 ? round(($tot / $kap) * 100, 1) : 0;

                $textUPT = $r->nama_upt;
                $numLines = $pdf->getNumLines($textUPT, $widths[1]);
                $rowH = max(7, $numLines * 3.8);

                $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
                $pdf->Cell($widths[2], $rowH, number_format($kap), 1, 0, 'C');
                $pdf->Cell($widths[3], $rowH, number_format($tah), 1, 0, 'C');
                $pdf->Cell($widths[4], $rowH, number_format($nap), 1, 0, 'C');
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->Cell($widths[5], $rowH, number_format($tot), 1, 0, 'C');

                $fillColor = $persen <= 0 ? [40, 167, 69] : ($persen <= 20 ? [23, 162, 184] : ($persen <= 50 ? [255, 193, 7] : [220, 53, 69]));
                $textColor = ($persen > 20 && $persen <= 50) ? [0, 0, 0] : [255, 255, 255];

                $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
                $pdf->SetFont('helvetica', '', 7);
                $pdf->Cell($widths[6], $rowH, $tingkat.'%', 1, 0, 'C', true);
                $pdf->Cell($widths[7], $rowH, ($persen > 0 ? '+' : '').$persen.'%', 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('helvetica', '', 8);
            }

            if ($tanggalMulai === $tanggalAkhir && ! empty($tanggalMulai)) {
                $totPersen = $gKapasitas > 0 ? round((($gTotal - $gKapasitas) / $gKapasitas) * 100, 1) : 0;
                $totHunian = $gKapasitas > 0 ? round(($gTotal / $gKapasitas) * 100, 1) : 0;

                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(220, 220, 220);
                $pdf->Cell($widths[0] + $widths[1], 7, 'TOTAL', 1, 0, 'C', true);
                $pdf->Cell($widths[2], 7, number_format($gKapasitas), 1, 0, 'C', true);
                $pdf->Cell($widths[3], 7, number_format($gTahanan), 1, 0, 'C', true);
                $pdf->Cell($widths[4], 7, number_format($gNapi), 1, 0, 'C', true);
                $pdf->Cell($widths[5], 7, number_format($gTotal), 1, 0, 'C', true);
                $pdf->Cell($widths[6], 7, $totHunian.'%', 1, 0, 'C', true);
                $pdf->Cell($widths[7], 7, ($totPersen > 0 ? '+' : '').$totPersen.'%', 1, 1, 'C', true);
            }

            self::renderSignature($pdf, $instansi, $signData);

        } elseif ($reportType === 'bebas_harian') {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Hari/Tanggal : '.$hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            $widths = [8, 42, 12, 12, 12, 12, 12, 12, 14, 14, 40];
            $pdf->SetFont('helvetica', 'B', 7.5);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[0], 7, 'NO', 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, 'NAMA UPT', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, 'BM', 1, 0, 'C', true);
            $pdf->Cell($widths[3], 7, 'PB', 1, 0, 'C', true);
            $pdf->Cell($widths[4], 7, 'CB', 1, 0, 'C', true);
            $pdf->Cell($widths[5], 7, 'CMB', 1, 0, 'C', true);
            $pdf->Cell($widths[6], 7, 'ASIM', 1, 0, 'C', true);
            $pdf->Cell($widths[7], 7, 'BDH', 1, 0, 'C', true);
            $pdf->Cell($widths[8], 7, 'LAINNYA', 1, 0, 'C', true);
            $pdf->Cell($widths[9], 7, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[10], 7, 'KETERANGAN', 1, 1, 'C', true);

            $q = DB::table('data_bebas_harian as dbh')->join('upt as u', 'dbh.upt_id', '=', 'u.id');
            if ($uptId) {
                $q->where('dbh.upt_id', $uptId);
            }
            if ($tanggalMulai && $tanggalAkhir) {
                $q->whereBetween('dbh.tanggal', [$tanggalMulai, $tanggalAkhir]);
            }

            $data = $q->select(
                'u.nama_upt',
                DB::raw('SUM(dbh.bebas_murni) as total_bm'),
                DB::raw('SUM(dbh.pb) as total_pb'),
                DB::raw('SUM(dbh.cb) as total_cb'),
                DB::raw('SUM(dbh.cmb) as total_cmb'),
                DB::raw('SUM(dbh.asimilasi) as total_asimilasi'),
                DB::raw('SUM(dbh.bdh) as total_bdh'),
                DB::raw('SUM(dbh.lainnya) as total_lainnya'),
                DB::raw('SUM(dbh.bebas_murni + dbh.pb + dbh.cb + dbh.cmb + dbh.asimilasi + dbh.bdh + dbh.lainnya) as total_bebas'),
                DB::raw('GROUP_CONCAT(DISTINCT NULLIF(dbh.ket_lainnya, "") SEPARATOR "; ") as rincian_lainnya'),
                DB::raw('MAX(dbh.keterangan) as keterangan')
            )->groupBy('u.id', 'u.nama_upt')->orderBy('u.nama_upt')->get();

            $no = 1;
            $tBm = 0;
            $tPb = 0;
            $tCb = 0;
            $tCmb = 0;
            $tAsim = 0;
            $tBdh = 0;
            $tLain = 0;
            $gTotal = 0;
            $pdf->SetFont('helvetica', '', 7.5);

            foreach ($data as $r) {
                $bm = (int) $r->total_bm;
                $pb = (int) $r->total_pb;
                $cb = (int) $r->total_cb;
                $cmb = (int) $r->total_cmb;
                $asim = (int) $r->total_asimilasi;
                $bdh = (int) $r->total_bdh;
                $lain = (int) $r->total_lainnya;
                $sub = (int) $r->total_bebas;

                $tBm += $bm;
                $tPb += $pb;
                $tCb += $cb;
                $tCmb += $cmb;
                $tAsim += $asim;
                $tBdh += $bdh;
                $tLain += $lain;
                $gTotal += $sub;

                $textUPT = $r->nama_upt;
                $textKet = trim(($r->keterangan ?? '').(! empty($r->rincian_lainnya) ? ' ('.$r->rincian_lainnya.')' : ''));
                if (empty($textKet)) {
                    $textKet = '-';
                }

                $nUPT = $pdf->getNumLines($textUPT, $widths[1]);
                $nKet = $pdf->getNumLines($textKet, $widths[10]);
                $rowH = max(6, max($nUPT, $nKet) * 4.5);

                $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
                $pdf->Cell($widths[2], $rowH, $bm, 1, 0, 'C');
                $pdf->Cell($widths[3], $rowH, $pb, 1, 0, 'C');
                $pdf->Cell($widths[4], $rowH, $cb, 1, 0, 'C');
                $pdf->Cell($widths[5], $rowH, $cmb, 1, 0, 'C');
                $pdf->Cell($widths[6], $rowH, $asim, 1, 0, 'C');
                $pdf->Cell($widths[7], $rowH, $bdh, 1, 0, 'C');
                $pdf->Cell($widths[8], $rowH, $lain, 1, 0, 'C');
                $pdf->SetFont('helvetica', 'B', 7.5);
                $pdf->Cell($widths[9], $rowH, $sub, 1, 0, 'C');
                $pdf->SetFont('helvetica', '', 7.5);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell($widths[10], $rowH, $textKet, 1, 'L', false, 1, $x, $y, true, 0, false, true, $rowH, 'M');
            }

            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell($widths[0] + $widths[1], 6, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 6, $tBm, 1, 0, 'C', true);
            $pdf->Cell($widths[3], 6, $tPb, 1, 0, 'C', true);
            $pdf->Cell($widths[4], 6, $tCb, 1, 0, 'C', true);
            $pdf->Cell($widths[5], 6, $tCmb, 1, 0, 'C', true);
            $pdf->Cell($widths[6], 6, $tAsim, 1, 0, 'C', true);
            $pdf->Cell($widths[7], 6, $tBdh, 1, 0, 'C', true);
            $pdf->Cell($widths[8], 6, $tLain, 1, 0, 'C', true);
            $pdf->Cell($widths[9], 6, $gTotal, 1, 0, 'C', true);
            $pdf->Cell($widths[10], 6, '', 1, 1, 'C', true);

            self::renderSignature($pdf, $instansi, $signData);

        } elseif ($reportType === 'overstaying') {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Hari/Tanggal : '.$hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            $widths = [8, 55, 13, 13, 13, 13, 13, 15, 47];
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[0], 7, 'NO', 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, 'NAMA UPT', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, 'AI', 1, 0, 'C', true);
            $pdf->Cell($widths[3], 7, 'AII', 1, 0, 'C', true);
            $pdf->Cell($widths[4], 7, 'AIII', 1, 0, 'C', true);
            $pdf->Cell($widths[5], 7, 'AIV', 1, 0, 'C', true);
            $pdf->Cell($widths[6], 7, 'AV', 1, 0, 'C', true);
            $pdf->Cell($widths[7], 7, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[8], 7, 'KETERANGAN', 1, 1, 'C', true);

            $q = DB::table('data_overstaying as do')->join('upt as u', 'do.upt_id', '=', 'u.id');
            if ($uptId) {
                $q->where('do.upt_id', $uptId);
            }
            if ($tanggalMulai && $tanggalAkhir) {
                $q->whereBetween('do.tanggal', [$tanggalMulai, $tanggalAkhir]);
            }

            $data = $q->select('do.*', 'u.nama_upt')->orderBy('u.nama_upt')->orderBy('do.tanggal')->get();

            $no = 1;
            $tAI = 0;
            $tAII = 0;
            $tAIII = 0;
            $tAIV = 0;
            $tAV = 0;
            $gTotal = 0;
            $pdf->SetFont('helvetica', '', 8);

            foreach ($data as $r) {
                $sub = $r->overstaying_ai + $r->overstaying_aii + $r->overstaying_aiii + $r->overstaying_aiv + $r->overstaying_av;
                $tAI += $r->overstaying_ai;
                $tAII += $r->overstaying_aii;
                $tAIII += $r->overstaying_aiii;
                $tAIV += $r->overstaying_aiv;
                $tAV += $r->overstaying_av;
                $gTotal += $sub;

                $textUPT = $r->nama_upt;
                $textKet = ! empty($r->keterangan) ? $r->keterangan : 'Nihil';

                $nUPT = $pdf->getNumLines($textUPT, $widths[1]);
                $nKet = $pdf->getNumLines($textKet, $widths[8]);
                $rowH = max(6, max($nUPT, $nKet) * 4);

                $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
                $pdf->Cell($widths[2], $rowH, $r->overstaying_ai, 1, 0, 'C');
                $pdf->Cell($widths[3], $rowH, $r->overstaying_aii, 1, 0, 'C');
                $pdf->Cell($widths[4], $rowH, $r->overstaying_aiii, 1, 0, 'C');
                $pdf->Cell($widths[5], $rowH, $r->overstaying_aiv, 1, 0, 'C');
                $pdf->Cell($widths[6], $rowH, $r->overstaying_av, 1, 0, 'C');
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->Cell($widths[7], $rowH, $sub, 1, 0, 'C');
                $pdf->SetFont('helvetica', '', 8);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell($widths[8], $rowH, $textKet, 1, 'L', false, 1, $x, $y, true, 0, false, true, $rowH, 'M');
            }

            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell($widths[0] + $widths[1], 6, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 6, $tAI, 1, 0, 'C', true);
            $pdf->Cell($widths[3], 6, $tAII, 1, 0, 'C', true);
            $pdf->Cell($widths[4], 6, $tAIII, 1, 0, 'C', true);
            $pdf->Cell($widths[5], 6, $tAIV, 1, 0, 'C', true);
            $pdf->Cell($widths[6], 6, $tAV, 1, 0, 'C', true);
            $pdf->Cell($widths[7], 6, $gTotal, 1, 0, 'C', true);
            $pdf->Cell($widths[8], 6, '', 1, 1, 'C', true);

            self::renderSignature($pdf, $instansi, $signData);

        } else {
            // Landscape reports (Integrasi, Rekapitulasi)
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Periode: '.$hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            if ($reportType === 'rekapitulasi') {
                $headers = ['No', 'UPT', 'Kapasitas', 'Tahanan', 'Narapidana', 'Total', '% Kapasitas', 'Overstaying', 'Integrasi'];
                $widths = [10, 85, 24, 24, 24, 24, 24, 24, 24];

                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($headers as $i => $h) {
                    $pdf->Cell($widths[$i], 7, $h, 1, 0, 'C', true);
                }
                $pdf->Ln();

                $uptQuery = Upt::orderBy('nama_upt');
                if ($uptId) {
                    $uptQuery->where('id', $uptId);
                }
                $upts = $uptQuery->get();

                $no = 1;
                $gKap = 0;
                $gTah = 0;
                $gNap = 0;
                $gTot = 0;
                $gOvs = 0;
                $gInt = 0;
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetTextColor(0, 0, 0);

                $exclude = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

                $penghuniMap = DB::table('data_penghuni')
                    ->select(
                        'upt_id',
                        DB::raw('COALESCE(SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan), 0) as tah'),
                        DB::raw('COALESCE(SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan), 0) as nap')
                    )
                    ->whereNotIn('klasifikasi_pidana', $exclude)
                    ->when($uptId, fn ($q) => $q->where('upt_id', $uptId))
                    ->when($tanggalMulai && $tanggalAkhir, fn ($q) => $q->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]))
                    ->groupBy('upt_id')
                    ->get()
                    ->keyBy('upt_id');

                $overstayingMap = DB::table('data_overstaying')
                    ->select(
                        'upt_id',
                        DB::raw('COALESCE(SUM(overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av), 0) as ovs')
                    )
                    ->when($uptId, fn ($q) => $q->where('upt_id', $uptId))
                    ->when($tanggalMulai && $tanggalAkhir, fn ($q) => $q->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]))
                    ->groupBy('upt_id')
                    ->get()
                    ->keyBy('upt_id');

                $integrasiMap = DB::table('data_integrasi')
                    ->select(
                        'upt_id',
                        DB::raw('COALESCE(SUM(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi), 0) as int_total')
                    )
                    ->when($uptId, fn ($q) => $q->where('upt_id', $uptId))
                    ->when($tanggalMulai && $tanggalAkhir, fn ($q) => $q->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]))
                    ->groupBy('upt_id')
                    ->get()
                    ->keyBy('upt_id');

                foreach ($upts as $u) {
                    $pData = $penghuniMap->get($u->id);
                    $tah = (int) ($pData->tah ?? 0);
                    $nap = (int) ($pData->nap ?? 0);
                    $tot = $tah + $nap;
                    $kap = (int) $u->kapasitas;
                    $persen = $kap > 0 ? round(($tot / $kap) * 100, 1) : 0;

                    $ovs = (int) ($overstayingMap->get($u->id)?->ovs ?? 0);
                    $int = (int) ($integrasiMap->get($u->id)?->int_total ?? 0);

                    $gKap += $kap;
                    $gTah += $tah;
                    $gNap += $nap;
                    $gTot += $tot;
                    $gOvs += $ovs;
                    $gInt += $int;

                    $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
                    $pdf->Cell($widths[1], 6, $u->nama_upt, 1, 0, 'L');
                    $pdf->Cell($widths[2], 6, number_format($kap), 1, 0, 'C');
                    $pdf->Cell($widths[3], 6, number_format($tah), 1, 0, 'C');
                    $pdf->Cell($widths[4], 6, number_format($nap), 1, 0, 'C');
                    $pdf->Cell($widths[5], 6, number_format($tot), 1, 0, 'C');
                    $pdf->Cell($widths[6], 6, $persen.'%', 1, 0, 'C');
                    $pdf->Cell($widths[7], 6, number_format($ovs), 1, 0, 'C');
                    $pdf->Cell($widths[8], 6, number_format($int), 1, 1, 'C');
                }

                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($widths[0] + $widths[1], 7, 'TOTAL', 1, 0, 'C', true);
                $pdf->Cell($widths[2], 7, number_format($gKap), 1, 0, 'C', true);
                $pdf->Cell($widths[3], 7, number_format($gTah), 1, 0, 'C', true);
                $pdf->Cell($widths[4], 7, number_format($gNapi ?? $gNap), 1, 0, 'C', true);
                $pdf->Cell($widths[5], 7, number_format($gTot), 1, 0, 'C', true);
                $totPersen = $gKap > 0 ? round(($gTot / $gKap) * 100, 1) : 0;
                $pdf->Cell($widths[6], 7, $totPersen.'%', 1, 0, 'C', true);
                $pdf->Cell($widths[7], 7, number_format($gOvs), 1, 0, 'C', true);
                $pdf->Cell($widths[8], 7, number_format($gInt), 1, 1, 'C', true);

                self::renderSignature($pdf, $instansi, $signData, 'L');

            } elseif ($reportType === 'integrasi') {
                $widths = [8, 65, 18, 13, 13, 13, 13, 14, 13, 13, 13, 13, 14, 15];

                // Header row 1
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);

                $pdf->Cell($widths[0], 12, 'No', 1, 0, 'C', true);
                $pdf->Cell($widths[1], 12, 'UPT', 1, 0, 'C', true);
                $pdf->Cell($widths[2], 12, 'Tanggal', 1, 0, 'C', true);
                $pdf->Cell(66, 6, 'Usulan', 1, 0, 'C', true);
                $pdf->Cell(66, 6, 'SK Terbit', 1, 0, 'C', true);
                $pdf->Cell($widths[13], 12, 'Total', 1, 1, 'C', true);

                // Header row 2
                $pdf->Cell(array_sum(array_slice($widths, 0, 3)), 0, '', 0, 0);
                $pdf->SetFillColor(66, 87, 108);
                $pdf->Cell($widths[3], 6, 'PB', 1, 0, 'C', true);
                $pdf->Cell($widths[4], 6, 'CB', 1, 0, 'C', true);
                $pdf->Cell($widths[5], 6, 'CMB', 1, 0, 'C', true);
                $pdf->Cell($widths[6], 6, 'CMK', 1, 0, 'C', true);
                $pdf->Cell($widths[7], 6, 'Asim', 1, 0, 'C', true);
                $pdf->Cell($widths[8], 6, 'PB', 1, 0, 'C', true);
                $pdf->Cell($widths[9], 6, 'CB', 1, 0, 'C', true);
                $pdf->Cell($widths[10], 6, 'CMB', 1, 0, 'C', true);
                $pdf->Cell($widths[11], 6, 'CMK', 1, 0, 'C', true);
                $pdf->Cell($widths[12], 6, 'Asim', 1, 0, 'C', true);
                $pdf->Cell($widths[13], 0, '', 0, 1);

                $q = DataIntegrasi::with('upt')->orderBy('tanggal', 'desc');
                if ($uptId) {
                    $q->where('upt_id', $uptId);
                }
                if ($tanggalMulai && $tanggalAkhir) {
                    $q->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
                }
                $data = $q->get();

                $pdf->SetFont('helvetica', '', 7);
                $pdf->SetTextColor(0, 0, 0);
                $no = 1;
                $grandTotal = 0;

                foreach ($data as $r) {
                    $totRow = (int) $r->usulan_pb + (int) $r->usulan_cb + (int) $r->usulan_cmb + (int) $r->usulan_cmk + (int) $r->usulan_asimilasi
                            + (int) $r->sk_terbit_pb + (int) $r->sk_terbit_cb + (int) $r->sk_terbit_cmb + (int) $r->sk_terbit_cmk + (int) $r->sk_terbit_asimilasi;
                    $grandTotal += $totRow;

                    $pdf->Cell($widths[0], 5.5, $no++, 1, 0, 'C');
                    $pdf->Cell($widths[1], 5.5, $r->upt?->nama_upt ?? '-', 1, 0, 'L');
                    $pdf->Cell($widths[2], 5.5, $r->tanggal ? date('d/m/Y', strtotime($r->tanggal)) : '-', 1, 0, 'C');
                    $pdf->Cell($widths[3], 5.5, (int) $r->usulan_pb, 1, 0, 'C');
                    $pdf->Cell($widths[4], 5.5, (int) $r->usulan_cb, 1, 0, 'C');
                    $pdf->Cell($widths[5], 5.5, (int) $r->usulan_cmb, 1, 0, 'C');
                    $pdf->Cell($widths[6], 5.5, (int) $r->usulan_cmk, 1, 0, 'C');
                    $pdf->Cell($widths[7], 5.5, (int) $r->usulan_asimilasi, 1, 0, 'C');
                    $pdf->Cell($widths[8], 5.5, (int) $r->sk_terbit_pb, 1, 0, 'C');
                    $pdf->Cell($widths[9], 5.5, (int) $r->sk_terbit_cb, 1, 0, 'C');
                    $pdf->Cell($widths[10], 5.5, (int) $r->sk_terbit_cmb, 1, 0, 'C');
                    $pdf->Cell($widths[11], 5.5, (int) $r->sk_terbit_cmk, 1, 0, 'C');
                    $pdf->Cell($widths[12], 5.5, (int) $r->sk_terbit_asimilasi, 1, 0, 'C');
                    $pdf->Cell($widths[13], 5.5, $totRow, 1, 1, 'C');
                }

                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(array_sum(array_slice($widths, 0, 13)), 6, 'TOTAL', 1, 0, 'C', true);
                $pdf->Cell($widths[13], 6, $grandTotal, 1, 1, 'C', true);

                self::renderSignature($pdf, $instansi, $signData, 'L');
            }
        }
    }

    private static function renderSignature($pdf, $instansi, $signData, $orientation = 'P')
    {
        // Mode UPT: Nonaktifkan semua part tanda tangan (tempat, tanggal, jabatan, nama, ttd, dan stempel)
        if (! empty($signData['is_upt'])) {
            return;
        }

        $showTtd = ! isset($signData['show_ttd']) || (bool) $signData['show_ttd'];
        if (! $showTtd) {
            return;
        }

        $showTtdImage = ! isset($signData['show_ttd_image']) || (bool) $signData['show_ttd_image'];
        $showStempel = ! isset($signData['show_stempel']) || (bool) $signData['show_stempel'];

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Demikian laporan ini disampaikan, atas perhatiannya diucapkan terima kasih.', 0, 1, 'L');
        $pdf->Ln(4);

        $tempatTtd = $signData['tempat_ttd'] ?? 'Serang';
        $jabatanTtd = $signData['jabatan_ttd'] ?? 'Kepala Bidang Pelayanan dan Pembinaan';
        $namaTtd = $signData['nama_ttd'] ?? 'Surya Dharma';
        $tglTtd = function_exists('formatIndonesianDate') ? formatIndonesianDate(date('Y-m-d'), false) : date('d/m/Y');

        $sigX = ($orientation === 'L') ? 215 : 130;
        $pdf->SetX($sigX);
        $pdf->Cell(70, 5, $tempatTtd.', '.$tglTtd, 0, 1, 'C');
        $pdf->SetX($sigX);
        $pdf->Cell(70, 5, $jabatanTtd, 0, 1, 'C');
        $pdf->Ln(2);

        $yBeforeSig = $pdf->GetY();
        if ($showTtdImage && ! empty($instansi?->ttd_image) && file_exists(public_path($instansi->ttd_image))) {
            $pdf->Image(public_path($instansi->ttd_image), $sigX + 15, $yBeforeSig - 2, 40, 0);
        }
        if ($showStempel && ! empty($instansi?->stempel_image) && file_exists(public_path($instansi->stempel_image))) {
            // Geser stempel ke kiri (sigX - 12) agar berada di sisi kiri dan hanya menyinggung tepi tanda tangan, tidak menutupi tanda tangan sepenuhnya
            $pdf->Image(public_path($instansi->stempel_image), $sigX - 12, $yBeforeSig - 4, 35, 0);
        }

        $pdf->Ln(20);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX($sigX);
        $pdf->Cell(70, 5, $namaTtd, 0, 1, 'C');
    }

    /**
     * Generate PDF Kelengkapan Pelaporan & Kroscek Harian per Tanggal (Detil Tiap UPT)
     */
    public static function generateKroscekDetail(array $data, array $signData = [])
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $instansi = Instansi::getFirst();
        $user = auth()->user();

        // Instantiate TCPDF in Landscape
        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public $instansiModel = null;

            public $showQrCode = true;

            public function Header() {}

            public function Footer()
            {
                if ($this->showQrCode) {
                    $this->SetY(-20);
                    $y = $this->GetY();
                    $qrText = "✅ Dokumen Valid\ndicetak melalui :\nSULTAN BANTEN @Ditjenpas Banten";
                    $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
                    $this->write2DBarcode($qrText, 'QRCODE,L', 15, $y, 15, 15, $style, 'N');
                }

                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 7);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages(), 0, 0, 'C');
                $this->SetTextColor(0, 0, 0);
            }
        };

        $pdf->instansiModel = $instansi;
        $pdf->showQrCode = ! empty($signData['show_qrcode']);
        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor($user->nama_lengkap ?? 'Admin');
        $pdf->SetTitle('Laporan Kelengkapan Pelaporan & Kroscek Harian per Tanggal');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();

        $isKanwil = $user ? $user->isKanwil() : false;
        $isUpt = ! $isKanwil || ! empty($signData['is_upt']);
        if ($isKanwil) {
            $isUpt = false;
        }

        $namaUpt = $data['upt']->nama_upt ?? 'UPT Pemasyarakatan';

        if ($isUpt) {
            // 1. Header Dokumen UPT (Bersih Tanpa Kop Kanwil)
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, 'LAPORAN KELENGKAPAN PELAPORAN & KROSCEK HARIAN PER TANGGAL', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 10.5);
            $pdf->Cell(0, 5, strtoupper($namaUpt), 0, 1, 'C');
            $pdf->Ln(1);
            $pdf->SetLineWidth(0.4);
            $pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
            $pdf->Ln(3);
        } else {
            // 1. KOP Resmi Instansi Kantor Wilayah (Landscape)
            $logoKop = null;
            if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
                $logoKop = public_path($instansi->logo_kop);
            } elseif (! empty($instansi?->logo) && file_exists(public_path($instansi->logo))) {
                $logoKop = public_path($instansi->logo);
            }

            if ($logoKop) {
                $pdf->Image($logoKop, 15, 10, 22);
            }

            $wKop = 230;
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4.5, 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', 0, 1, 'C');
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4.5, 'DIREKTORAT JENDERAL PEMASYARAKATAN', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetX(40);
            $pdf->Cell($wKop, 5, 'KANTOR WILAYAH BANTEN', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4, "Jl.KH.Sam'un No.44 Serang, Banten | Telepon: 0822-6666-2044 | Faksimilie: 0822-6666-2044", 0, 1, 'C');
            $pdf->SetX(40);
            $pdf->Cell($wKop, 4, 'Laman: djpasbanten@gmail.com | Pos-el: djpasbanten@gmail.com', 0, 1, 'C');

            $pdf->Ln(2);
            $pdf->SetLineWidth(0.6);
            $pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
            $pdf->SetLineWidth(0.2);
            $pdf->Line(10, $pdf->GetY() + 0.8, 287, $pdf->GetY() + 0.8);
            $pdf->Ln(3);

            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 5, 'LAPORAN KELENGKAPAN PELAPORAN & KROSCEK HARIAN PER TANGGAL', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 9.5);
            $pdf->Cell(0, 5, strtoupper($namaUpt), 0, 1, 'C');
            $pdf->Ln(1);
        }

        // 3. Metadata Periode & Filter Info
        $tglMulaiStr = function_exists('formatIndonesianDate') ? formatIndonesianDate($data['tanggal_mulai'], false) : $data['tanggal_mulai'];
        $tglAkhirStr = function_exists('formatIndonesianDate') ? formatIndonesianDate($data['tanggal_akhir'], false) : $data['tanggal_akhir'];
        $urutanStr = ($data['sort'] ?? 'desc') === 'asc' ? 'Terlama ke Terbaru (ASC)' : 'Terbaru ke Terlama (DESC)';
        $totalHari = $data['stats']['total_hari'] ?? count($data['list']);
        $jamSelesai = substr($data['jam_selesai_str'] ?? '11:00:00', 0, 5);

        $pdf->SetFont('helvetica', '', 7.5);
        $metaText = "Periode: {$tglMulaiStr} s/d {$tglAkhirStr} ({$totalHari} Hari Kalender) | Batas Waktu Input: {$jamSelesai} WIB | Urutan: {$urutanStr}";
        $pdf->Cell(0, 4, $metaText, 0, 1, 'C');
        $pdf->SetFont('helvetica', 'I', 6.8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 3.5, 'Keterangan Penanda Waktu: [H] = Input hari yang sama | [H-1] = Input lebih awal/kemarin | [H+1] = Input terlambat hari berikutnya', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1.5);

        // 4. Ringkasan KPI Cards
        $stats = $data['stats'];
        $pdf->SetFont('helvetica', 'B', 7.5);

        $kpiWidth = 69.25; // 277 / 4

        // Box 1: Sinkron
        $pdf->SetFillColor(240, 253, 244);
        $pdf->SetTextColor(22, 101, 52);
        $pdf->Cell($kpiWidth, 5.5, "Kroscek Sinkron: {$stats['sinkron']}/{$stats['total_hari']} Hari ({$stats['pct_sinkron']}%)", 1, 0, 'C', true);

        // Box 2: Selisih
        $pdf->SetFillColor($stats['selisih'] > 0 ? 254 : 248, $stats['selisih'] > 0 ? 242 : 250, $stats['selisih'] > 0 ? 242 : 252);
        $pdf->SetTextColor($stats['selisih'] > 0 ? 185 : 100, $stats['selisih'] > 0 ? 28 : 116, $stats['selisih'] > 0 ? 28 : 139);
        $pdf->Cell($kpiWidth, 5.5, "Kroscek Selisih: {$stats['selisih']} Hari", 1, 0, 'C', true);

        // Box 3: Lengkap 4/4
        $pdf->SetFillColor(239, 246, 255);
        $pdf->SetTextColor(29, 78, 216);
        $pdf->Cell($kpiWidth, 5.5, "4/4 Modul Lengkap: {$stats['lengkap_4']}/{$stats['total_hari']} Hari ({$stats['pct_lengkap']}%)", 1, 0, 'C', true);

        // Box 4: Belum Lengkap
        $pdf->SetFillColor($stats['belum_lengkap'] > 0 ? 254 : 248, $stats['belum_lengkap'] > 0 ? 249 : 250, $stats['belum_lengkap'] > 0 ? 195 : 252);
        $pdf->SetTextColor($stats['belum_lengkap'] > 0 ? 180 : 100, $stats['belum_lengkap'] > 0 ? 83 : 116, $stats['belum_lengkap'] > 0 ? 9 : 139);
        $pdf->Cell($kpiWidth, 5.5, "Belum Lengkap: {$stats['belum_lengkap']} Hari", 1, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);

        // 5. Header Tabel Rincian Harian
        $renderTableHeader = function () use ($pdf) {
            $yTop = $pdf->GetY();
            $pdf->SetFont('helvetica', 'B', 7.5);
            $pdf->SetFillColor(52, 73, 94);
            $pdf->SetTextColor(255, 255, 255);

            // Rowspan columns (tinggi 10mm)
            $pdf->SetXY(10, $yTop);
            $pdf->Cell(8, 10, 'NO', 1, 0, 'C', true);
            $pdf->Cell(34, 10, 'TANGGAL & HARI', 1, 0, 'C', true);

            // Group 1: Kroscek Tahanan (tinggi 5mm)
            $pdf->Cell(60, 5, 'KROSCEK TAHANAN', 1, 0, 'C', true);

            // Group 2: Status 4 Modul (tinggi 5mm)
            $pdf->Cell(104, 5, 'STATUS & JAM INPUT 4 MODUL LAPORAN', 1, 0, 'C', true);

            // Rowspan columns (tinggi 10mm)
            $pdf->Cell(45, 10, 'DINAMIKA POPULASI', 1, 0, 'C', true);
            $pdf->Cell(26, 10, 'STATUS MODUL', 1, 1, 'C', true);

            // Sub-headers (tinggi 5mm di bawah baris atas group)
            $pdf->SetXY(52, $yTop + 5);
            $pdf->SetFillColor(68, 92, 115);
            $pdf->Cell(18, 5, 'Penghuni', 1, 0, 'C', true);
            $pdf->Cell(18, 5, 'Overstay', 1, 0, 'C', true);
            $pdf->Cell(24, 5, 'Status', 1, 0, 'C', true);
            $pdf->Cell(26, 5, '1. Penghuni', 1, 0, 'C', true);
            $pdf->Cell(26, 5, '2. Overstay', 1, 0, 'C', true);
            $pdf->Cell(26, 5, '3. Integrasi', 1, 0, 'C', true);
            $pdf->Cell(26, 5, '4. Bebas', 1, 1, 'C', true);

            $pdf->SetY($yTop + 10);
            $pdf->SetTextColor(0, 0, 0);
        };

        $renderTableHeader();

        // 6. Data Rows
        $no = 1;
        $pdf->SetFont('helvetica', '', 7);

        foreach ($data['list'] as $r) {
            if ($pdf->GetY() > 175) {
                $pdf->AddPage();
                $renderTableHeader();
                $pdf->SetFont('helvetica', '', 7);
            }

            $isOdd = ($no % 2 === 1);
            $bgR = $isOdd ? 255 : 248;
            $bgG = $isOdd ? 255 : 250;
            $bgB = $isOdd ? 255 : 252;
            $pdf->SetFillColor($bgR, $bgG, $bgB);
            $pdf->SetTextColor(0, 0, 0);

            $rowH = 5.5;

            // 1. No
            $pdf->Cell(8, $rowH, $no++, 1, 0, 'C', true);

            // 2. Tanggal & Hari
            $tglHariText = $r['hari'].', '.$r['tanggal_formatted'];
            $pdf->Cell(34, $rowH, $tglHariText, 1, 0, 'L', true);

            // 3. Kroscek Tahanan Penghuni
            $tPenghuniStr = $r['tahanan_penghuni'] !== null ? number_format($r['tahanan_penghuni']) : '-';
            $pdf->Cell(18, $rowH, $tPenghuniStr, 1, 0, 'C', true);

            // 4. Kroscek Tahanan Overstaying
            $tOverstayStr = $r['tahanan_overstaying'] !== null ? number_format($r['tahanan_overstaying']) : '-';
            $pdf->Cell(18, $rowH, $tOverstayStr, 1, 0, 'C', true);

            // 5. Status Kroscek
            if ($r['status_kroscek'] === 'sinkron') {
                $pdf->SetTextColor(22, 101, 52);
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->Cell(24, $rowH, 'Sinkron', 1, 0, 'C', true);
            } elseif ($r['status_kroscek'] === 'selisih') {
                $pdf->SetTextColor(185, 28, 28);
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->Cell(24, $rowH, 'Selisih '.$r['selisih'], 1, 0, 'C', true);
            } else {
                $pdf->SetTextColor(100, 116, 139);
                $pdf->SetFont('helvetica', '', 7);
                $pdf->Cell(24, $rowH, 'Belum Lengkap', 1, 0, 'C', true);
            }
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 7);

            // 6. Modul 1: Penghuni
            $pdf->SetFont('helvetica', '', 6.5);
            if ($r['penghuni']['exists']) {
                $hTag = ! empty($r['penghuni']['h_tag']) ? ' • '.$r['penghuni']['h_tag'] : '';
                $txt = $r['penghuni']['jam_str'].($r['penghuni']['is_tepat'] ? " (Tepat{$hTag})" : " (Terlambat{$hTag})");
                $pdf->SetTextColor($r['penghuni']['is_tepat'] ? 22 : 180, $r['penghuni']['is_tepat'] ? 101 : 83, $r['penghuni']['is_tepat'] ? 52 : 9);
            } else {
                $txt = 'Belum';
                $pdf->SetTextColor(185, 28, 28);
            }
            $pdf->Cell(26, $rowH, $txt, 1, 0, 'C', true);
            $pdf->SetTextColor(0, 0, 0);

            // 7. Modul 2: Overstaying
            if ($r['overstaying']['exists']) {
                $hTag = ! empty($r['overstaying']['h_tag']) ? ' • '.$r['overstaying']['h_tag'] : '';
                $txt = $r['overstaying']['jam_str'].($r['overstaying']['is_tepat'] ? " (Tepat{$hTag})" : " (Terlambat{$hTag})");
                $pdf->SetTextColor($r['overstaying']['is_tepat'] ? 22 : 180, $r['overstaying']['is_tepat'] ? 101 : 83, $r['overstaying']['is_tepat'] ? 52 : 9);
            } else {
                $txt = 'Belum';
                $pdf->SetTextColor(185, 28, 28);
            }
            $pdf->Cell(26, $rowH, $txt, 1, 0, 'C', true);
            $pdf->SetTextColor(0, 0, 0);

            // 8. Modul 3: Integrasi
            if ($r['integrasi']['exists']) {
                $hTag = ! empty($r['integrasi']['h_tag']) ? ' • '.$r['integrasi']['h_tag'] : '';
                $txt = $r['integrasi']['jam_str'].($r['integrasi']['is_tepat'] ? " (Tepat{$hTag})" : " (Terlambat{$hTag})");
                $pdf->SetTextColor($r['integrasi']['is_tepat'] ? 22 : 180, $r['integrasi']['is_tepat'] ? 101 : 83, $r['integrasi']['is_tepat'] ? 52 : 9);
            } else {
                $txt = 'Belum';
                $pdf->SetTextColor(185, 28, 28);
            }
            $pdf->Cell(26, $rowH, $txt, 1, 0, 'C', true);
            $pdf->SetTextColor(0, 0, 0);

            // 9. Modul 4: Bebas Harian
            if ($r['bebas']['exists']) {
                $hTag = ! empty($r['bebas']['h_tag']) ? ' • '.$r['bebas']['h_tag'] : '';
                $txt = $r['bebas']['jam_str'].($r['bebas']['is_tepat'] ? " (Tepat{$hTag})" : " (Terlambat{$hTag})");
                $pdf->SetTextColor($r['bebas']['is_tepat'] ? 22 : 180, $r['bebas']['is_tepat'] ? 101 : 83, $r['bebas']['is_tepat'] ? 52 : 9);
            } else {
                $txt = 'Belum';
                $pdf->SetTextColor(185, 28, 28);
            }
            $pdf->Cell(26, $rowH, $txt, 1, 0, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 7);

            // 10. Dinamika Populasi
            $popStr = number_format($r['penghuni']['total_penghuni']).' WBP (Bbs:'.$r['bebas']['total_bebas'].', Ost:'.$r['overstaying']['total_overstaying'].')';
            $pdf->Cell(45, $rowH, $popStr, 1, 0, 'C', true);

            // 11. Kelengkapan
            if ($r['is_all_completed']) {
                $pdf->SetTextColor(22, 101, 52);
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->Cell(26, $rowH, '4/4 Lengkap', 1, 1, 'C', true);
            } else {
                $pdf->SetTextColor(180, 83, 9);
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->Cell(26, $rowH, $r['completed_count'].'/4 Terisi', 1, 1, 'C', true);
            }
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 7);
        }

        // 7. Signature Block
        // Mode Kanwil: sertakan tanda tangan lengkap. Mode UPT: dinonaktifkan
        if (! $isUpt && ! empty($signData['show_ttd'])) {
            if ($pdf->GetY() > 145) {
                $pdf->AddPage();
            }
            self::renderSignature($pdf, $instansi, $signData, 'L');
        }

        // Output PDF
        $cleanUpt = Str::slug($namaUpt);
        $filename = "Laporan_Kroscek_Detail_{$cleanUpt}_".date('Ymd_His').'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Generate PDF Penetapan Peringkat Kedisiplinan Pelaporan UPT (A4)
     * Lengkap dengan Rekapitulasi 12 UPT, Executive Summary KPI, Tanda Tangan & Stempel Kanwil,
     * serta Halaman 2 Lampiran Pedoman Penilaian & Metodologi Penentuan Juara.
     *
     * @param  array  $rankingData  Output dari UptRankingService::calculateRanking(...)
     * @param  array  $signData  Pengaturan tanda tangan & stempel
     * @param  string  $orientation  'P' (Portrait) atau 'L' (Landscape)
     * @return Response
     */
    public static function generateRankingReport(array $rankingData, array $signData = [], string $orientation = 'P')
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $instansi = Instansi::getFirst();
        $user = auth()->user();
        $orientation = strtoupper($orientation) === 'L' ? 'L' : 'P';

        $pdf = new class($orientation, 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public $showQrCode = true;

            public function Header() {}

            public function Footer()
            {
                if ($this->showQrCode) {
                    $this->SetY(-18);
                    $y = $this->GetY();
                    $qrText = "✅ Dokumen Sah & Valid\nPenetapan Peringkat Kepatuhan UPT\nSULTAN BANTEN @Ditjenpas Banten";
                    $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
                    $this->write2DBarcode($qrText, 'QRCODE,L', 12, $y, 14, 14, $style, 'N');
                }

                $this->SetY(-14);
                $this->SetFont('helvetica', 'I', 7);
                $this->SetTextColor(100, 116, 139);
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages(), 0, 0, 'C');
                $this->SetTextColor(0, 0, 0);
            }
        };

        $pdf->showQrCode = ! empty($signData['show_qrcode'] ?? true);
        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor($user->nama_lengkap ?? 'Kanwil Ditjenpas Banten');
        $pdf->SetTitle('Laporan Penetapan Peringkat Kedisiplinan UPT');
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();

        $pageWidth = ($orientation === 'P') ? 186 : 273;

        // 1. Kop Surat Kanwil
        $logoKop = null;
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoKop = public_path($instansi->logo_kop);
        } elseif (! empty($instansi?->logo) && file_exists(public_path($instansi->logo))) {
            $logoKop = public_path($instansi->logo);
        }

        if ($logoKop) {
            $pdf->Image($logoKop, 14, 10, ($orientation === 'P' ? 22 : 20));
        }

        $wKop = ($orientation === 'P') ? 150 : 235;
        $xKop = ($orientation === 'P') ? 38 : 42;

        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->SetX($xKop);
        $pdf->Cell($wKop, 4.5, 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', 0, 1, 'C');
        $pdf->SetX($xKop);
        $pdf->Cell($wKop, 4.5, 'DIREKTORAT JENDERAL PEMASYARAKATAN', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetX($xKop);
        $pdf->Cell($wKop, 5, 'KANTOR WILAYAH BANTEN', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetX($xKop);
        $pdf->Cell($wKop, 4, "Jl. KH. Sam'un No. 44 Serang, Banten • Pos-el: djpasbanten@gmail.com", 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(12, $pdf->GetY(), 12 + $pageWidth, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Line(12, $pdf->GetY() + 0.8, 12 + $pageWidth, $pdf->GetY() + 0.8);
        $pdf->Ln(4);

        // 2. Judul & Periode
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 5, 'KEPUTUSAN PENETAPAN PERINGKAT KEDISIPLINAN PELAPORAN HARIAN UPT', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 9.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 4.5, 'DIVISI PEMASYARAKATAN KANTOR WILAYAH BANTEN', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $period = $rankingData['period'] ?? [];
        $summary = $rankingData['summary'] ?? [];
        $rankings = $rankingData['rankings'] ?? [];

        $tglMulaiIndo = function_exists('formatIndonesianDate') ? formatIndonesianDate($period['start_date'] ?? date('Y-m-01'), false) : ($period['start_date'] ?? '-');
        $tglAkhirIndo = function_exists('formatIndonesianDate') ? formatIndonesianDate($period['end_date'] ?? date('Y-m-t'), false) : ($period['end_date'] ?? '-');

        $pdf->SetFont('helvetica', '', 8);
        $periodeText = "Periode: {$tglMulaiIndo} s/d {$tglAkhirIndo} ({$period['total_days']} Hari Efektif) • Batas Jam Input: ".($period['jam_mulai'] ?? '08:00').' - '.($period['jam_selesai'] ?? '11:00').' WIB (Maks 100 Poin/Hari)';
        $pdf->Cell(0, 4.5, $periodeText, 0, 1, 'C');
        $pdf->Ln(2);

        // 3. Executive KPI Summary Box
        $kpiBoxWidth = $pageWidth / 4;
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(71, 85, 105);

        $topUptName = ! empty($summary['top_upt']['nama_upt']) ? $summary['top_upt']['nama_upt'] : '-';
        if (strlen($topUptName) > 22) {
            $topUptName = substr($topUptName, 0, 20).'..';
        }
        $topScore = ! empty($summary['top_upt']['total_points']) ? number_format($summary['top_upt']['total_points']).' Pts' : '0 Pts';

        $pdf->Cell($kpiBoxWidth, 4.5, 'TOTAL SATKER DIEVALUASI', 1, 0, 'C', true);
        $pdf->Cell($kpiBoxWidth, 4.5, 'RATA-RATA KEPATUHAN', 1, 0, 'C', true);
        $pdf->Cell($kpiBoxWidth, 4.5, 'JUARA 1 (TERBAIK)', 1, 0, 'C', true);
        $pdf->Cell($kpiBoxWidth, 4.5, 'TOTAL KROSCEK SINKRON', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell($kpiBoxWidth, 6, ($summary['total_upt'] ?? count($rankings)).' UPT', 1, 0, 'C', true);
        $pdf->Cell($kpiBoxWidth, 6, ($summary['avg_compliance'] ?? 0).'%', 1, 0, 'C', true);
        $pdf->Cell($kpiBoxWidth, 6, "{$topUptName} ({$topScore})", 1, 0, 'C', true);
        $pdf->Cell($kpiBoxWidth, 6, ($summary['total_sync_kroscek'] ?? 0).'x Valid', 1, 1, 'C', true);

        $pdf->Ln(3);

        // 4. Header Tabel Rekapitulasi 12 UPT
        $colWidths = ($orientation === 'P')
            ? [10, 44, 18, 16, 14, 14, 15, 19, 19, 17] // Sum = 186
            : [14, 60, 24, 22, 20, 20, 22, 30, 31, 30]; // Sum = 273

        $pdf->SetFillColor(30, 41, 59);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', ($orientation === 'P' ? 6.5 : 7.5));

        $pdf->Cell($colWidths[0], 6.5, 'RANK', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 6.5, 'NAMA UPT PEMASYARAKATAN', 1, 0, 'L', true);
        $pdf->Cell($colWidths[2], 6.5, 'POIN', 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], 6.5, 'KEPATUHAN', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 6.5, 'GOLD', 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 6.5, 'SILVER', 1, 0, 'C', true);
        $pdf->Cell($colWidths[6], 6.5, '100 POIN', 1, 0, 'C', true);
        $pdf->Cell($colWidths[7], 6.5, 'MODUL (T/L/K)', 1, 0, 'C', true);
        $pdf->Cell($colWidths[8], 6.5, 'KROSCEK (S/P/X)', 1, 0, 'C', true);
        $pdf->Cell($colWidths[9], 6.5, 'RATA2 JAM', 1, 1, 'C', true);

        // 5. Data Rows
        $rowHeight = ($orientation === 'P' ? 5.2 : 5.8);

        foreach ($rankings as $r) {
            $rankNum = $r['rank'] ?? 0;
            $isWorst = ! empty($r['is_worst']);

            if ($rankNum === 1) {
                $pdf->SetFillColor(254, 243, 199);
                $rankLabel = '#1 🥇';
            } elseif ($rankNum === 2) {
                $pdf->SetFillColor(241, 245, 249);
                $rankLabel = '#2 🥈';
            } elseif ($rankNum === 3) {
                $pdf->SetFillColor(255, 237, 213);
                $rankLabel = '#3 🥉';
            } elseif ($isWorst) {
                $pdf->SetFillColor(254, 226, 226);
                $rankLabel = "#{$rankNum} [Eval]";
            } else {
                $isOdd = ($rankNum % 2 === 1);
                $pdf->SetFillColor($isOdd ? 255 : 248, $isOdd ? 255 : 250, $isOdd ? 255 : 252);
                $rankLabel = "#{$rankNum}";
            }

            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('helvetica', ($rankNum <= 3 ? 'B' : ''), ($orientation === 'P' ? 6.5 : 7.5));

            $pdf->Cell($colWidths[0], $rowHeight, $rankLabel, 1, 0, 'C', true);
            $pdf->Cell($colWidths[1], $rowHeight, ' '.$r['nama_upt'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[2], $rowHeight, number_format($r['total_points']), 1, 0, 'C', true);
            $pdf->Cell($colWidths[3], $rowHeight, $r['persentase_kepatuhan'].'%', 1, 0, 'C', true);
            $pdf->Cell($colWidths[4], $rowHeight, ($r['count_gold'] ?? 0).'x', 1, 0, 'C', true);
            $pdf->Cell($colWidths[5], $rowHeight, ($r['count_silver'] ?? 0).'x', 1, 0, 'C', true);
            $pdf->Cell($colWidths[6], $rowHeight, ($r['count_hari_sempurna'] ?? 0).' Hari', 1, 0, 'C', true);

            $modulStr = "{$r['count_modul_tepat']} / {$r['count_modul_terlambat']} / {$r['count_modul_kosong']}";
            $pdf->Cell($colWidths[7], $rowHeight, $modulStr, 1, 0, 'C', true);

            $kroscekStr = "{$r['count_kroscek_sesuai']} / ".($r['count_kroscek_perbaikan'] ?? 0)." / {$r['count_kroscek_selisih']}";
            $pdf->Cell($colWidths[8], $rowHeight, $kroscekStr, 1, 0, 'C', true);

            $pdf->Cell($colWidths[9], $rowHeight, $r['rata_rata_jam_input'] ?? '-', 1, 1, 'C', true);
        }

        // Catatan singkat di bawah tabel
        $pdf->Ln(1.5);
        $pdf->SetFont('helvetica', 'I', 6.2);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 3.5, '* Keterangan Kolom: Modul (T=Tepat, L=Terlambat, K=Kosong) • Kroscek (S=Sesuai, P=Perbaikan, X=Selisih) • Gold: Input < 08.00 WIB • Silver: 08.00 - 11.00 WIB', 0, 1, 'L');
        $pdf->Ln(2);

        // 6. Penutup & Signature Block
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->Cell(0, 4, 'Demikian penetapan peringkat ini disampaikan secara transparan dan akuntabel sebagai bahan evaluasi dan pembinaan kinerja UPT.', 0, 1, 'L');
        $pdf->Ln(2);

        $tempatTtd = $signData['tempat_ttd'] ?? 'Serang';
        $jabatanTtd = $signData['jabatan_ttd'] ?? 'Kepala Divisi Pemasyarakatan';
        $namaTtd = $signData['nama_ttd'] ?? 'Surya Dharma';
        $nipTtd = $signData['nip_ttd'] ?? '';
        $tglTtd = function_exists('formatIndonesianDate') ? formatIndonesianDate(date('Y-m-d'), false) : date('d/m/Y');

        $sigX = ($orientation === 'P') ? 120 : 205;
        $sigW = ($orientation === 'P') ? 66 : 68;

        $pdf->SetX($sigX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($sigW, 4, $tempatTtd.', '.$tglTtd, 0, 1, 'C');
        $pdf->SetX($sigX);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->Cell($sigW, 4, $jabatanTtd, 0, 1, 'C');

        $ySigPos = $pdf->GetY();
        $showTtdImage = ! isset($signData['show_ttd_image']) || (bool) $signData['show_ttd_image'];
        $showStempel = ! isset($signData['show_stempel']) || (bool) $signData['show_stempel'];

        if ($showTtdImage && ! empty($instansi?->ttd_image) && file_exists(public_path($instansi->ttd_image))) {
            $pdf->Image(public_path($instansi->ttd_image), $sigX + 14, $ySigPos - 1, 36, 0);
        }
        if ($showStempel && ! empty($instansi?->stempel_image) && file_exists(public_path($instansi->stempel_image))) {
            $pdf->Image(public_path($instansi->stempel_image), $sigX - 8, $ySigPos - 3, 30, 0);
        }

        $pdf->SetXY($sigX, $ySigPos + 16);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($sigW, 4.5, $namaTtd, 0, 1, 'C');
        if (! empty($nipTtd)) {
            $pdf->SetX($sigX);
            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->Cell($sigW, 3.5, 'NIP. '.$nipTtd, 0, 1, 'C');
        }

        // ========================================================
        // HALAMAN 2: LAMPIRAN PEDOMAN & SISTEM PENILAIAN POIN
        // ========================================================
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(0, 5, 'LAMPIRAN KEPUTUSAN PENETAPAN PERINGKAT KEDISIPLINAN UPT', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 4.5, 'PEDOMAN SISTEM PERHITUNGAN POIN & METODOLOGI PENENTUAN JUARA', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Ln(1);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(12, $pdf->GetY(), 12 + $pageWidth, $pdf->GetY());
        $pdf->Ln(3);

        // Section 1: Komposisi Poin Harian (100 Poin/Hari)
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 4.5, '1. KOMPOSISI NILAI HARIAN (MAKSIMAL 100 POIN PER HARI)', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->MultiCell(0, 4, 'Setiap hari kalender (7 hari seminggu), sistem SULTAN BANTEN mengevaluasi 5 komponen pelaporan dengan bobot masing-masing 20 poin (Total Poin Maksimal = 100 Poin/Hari):', 0, 'L');
        $pdf->Ln(1);

        $boxW = ($pageWidth - 8) / 5;
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(30, 41, 59);

        $pdf->Cell($boxW, 5, 'Modul 1: Penghuni', 1, 0, 'C', true);
        $pdf->Cell($boxW, 5, 'Modul 2: Overstaying', 1, 0, 'C', true);
        $pdf->Cell($boxW, 5, 'Modul 3: Integrasi', 1, 0, 'C', true);
        $pdf->Cell($boxW, 5, 'Modul 4: Bebas Harian', 1, 0, 'C', true);
        $pdf->Cell($boxW, 5, 'Kroscek: Sinkron Tahanan', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 6.8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell($boxW, 7, "Populasi WBP\n(Maks 20 Poin)", 1, 0, 'C', true);
        $pdf->Cell($boxW, 7, "Tahanan Lewat Batas\n(Maks 20 Poin)", 1, 0, 'C', true);
        $pdf->Cell($boxW, 7, "PB, CB, CMB, Asimilasi\n(Maks 20 Poin)", 1, 0, 'C', true);
        $pdf->Cell($boxW, 7, "Pengeluaran Harian\n(Maks 20 Poin)", 1, 0, 'C', true);
        $pdf->Cell($boxW, 7, "Penghuni vs Overstaying\n(Maks 20 Poin)", 1, 1, 'C', true);

        $pdf->Ln(3);

        // Section 2: Matriks Ketepatan Waktu 4 Modul Laporan
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 4.5, '2. MATRIKS KETEPATAN WAKTU & KONVERSI POIN 4 MODUL LAPORAN', 0, 1, 'L');

        $cW2 = ($orientation === 'P') ? [32, 40, 26, 88] : [45, 55, 35, 138];
        $pdf->SetFillColor(51, 65, 85);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 7);

        $pdf->Cell($cW2[0], 5, 'Kategori Waktu', 1, 0, 'C', true);
        $pdf->Cell($cW2[1], 5, 'Rentang Jam Input', 1, 0, 'C', true);
        $pdf->Cell($cW2[2], 5, 'Poin Modul', 1, 0, 'C', true);
        $pdf->Cell($cW2[3], 5, 'Status, Predikat & Medali', 1, 1, 'L', true);

        $rulesModul = [
            ['Golden Hours (Pagi)', '< 08.00 WIB Hari H', '20 Poin (100%)', 'Tepat Waktu • Mendapat Medali Emas 🥇 (Disiplin Tinggi)', [254, 243, 199]],
            ['Jam Kerja Standar', '08.00 - 11.00 WIB Hari H', '20 Poin (100%)', 'Tepat Waktu • Mendapat Medali Perak 🥈 (Sesuai Jam Dinas)', [241, 245, 249]],
            ['Lapor Lebih Awal', 'H-1 / H-2 (Malam Sebelumnya)', '20 Poin (100%)', 'Tepat Waktu • Lapor Lebih Cepat / Early Submission', [255, 255, 255]],
            ['Terlambat Hari H', '11.01 - 23.59 WIB Hari H', '10 Poin (50%)', 'Terlambat Hari H • Pengurangan Bobot 50%', [255, 247, 237]],
            ['Terlambat Lewat Hari', 'H+1 dst (Susulan)', '5 Poin (25%)', 'Terlambat Susulan • Pengurangan Bobot 75%', [254, 242, 242]],
            ['Tidak Mengisi', 'Tidak Ada Data Terinput', '0 Poin (0%)', 'Kosong / Nihil • Tidak Memperoleh Nilai', [254, 226, 226]],
        ];

        $pdf->SetFont('helvetica', '', 6.8);
        foreach ($rulesModul as $row) {
            $pdf->SetFillColor($row[4][0], $row[4][1], $row[4][2]);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell($cW2[0], 4.8, ' '.$row[0], 1, 0, 'L', true);
            $pdf->Cell($cW2[1], 4.8, $row[1], 1, 0, 'C', true);
            $pdf->Cell($cW2[2], 4.8, $row[2], 1, 0, 'C', true);
            $pdf->Cell($cW2[3], 4.8, ' '.$row[3], 1, 1, 'L', true);
        }

        $pdf->Ln(3);

        // Section 3: Ketentuan Kroscek Data Tahanan & Perbaikan Data
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 4.5, '3. KETENTUAN KROSCEK SINKRONISASI TAHANAN & PERBAIKAN DATA (MODUL 5)', 0, 1, 'L');

        $cW3 = ($orientation === 'P') ? [42, 54, 26, 64] : [55, 70, 35, 113];
        $pdf->SetFillColor(51, 65, 85);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 7);

        $pdf->Cell($cW3[0], 5, 'Kondisi Validasi', 1, 0, 'C', true);
        $pdf->Cell($cW3[1], 5, 'Waktu / Keterangan Penyelesaian', 1, 0, 'C', true);
        $pdf->Cell($cW3[2], 5, 'Poin Kroscek', 1, 0, 'C', true);
        $pdf->Cell($cW3[3], 5, 'Status Evaluasi', 1, 1, 'L', true);

        $rulesKroscek = [
            ['Sinkron Langsung', 'Penghuni = Overstaying saat pertama input', '20 Poin (Maks)', 'Data Valid & Akurat Sejak Awal', [240, 253, 244]],
            ['Sinkron Pasca Perbaikan', 'Diperbaiki dalam jam kerja (< 11.00 WIB)', '18 Poin (90%)', 'Sesuai Hasil Perbaikan Cepat', [254, 249, 195]],
            ['Sinkron Pasca Perbaikan', 'Diperbaiki di luar jam kerja (11.01-23.59)', '12 Poin (60%)', 'Sesuai Hasil Perbaikan Hari H', [255, 247, 237]],
            ['Sinkron Pasca Perbaikan', 'Diperbaiki lewat hari (H+1 atau lebih)', '8 Poin (40%)', 'Sesuai Hasil Perbaikan Susulan', [248, 250, 252]],
            ['Selisih Tahanan', 'Jumlah tahanan tidak sama & belum diperbaiki', '0 Poin (0%)', 'Data Tidak Sinkron / Tidak Akurat', [254, 226, 226]],
            ['Belum Lengkap', 'Salah satu atau kedua modul belum diisi', '0 Poin (0%)', 'Belum Memenuhi Syarat Evaluasi', [241, 245, 249]],
        ];

        $pdf->SetFont('helvetica', '', 6.8);
        foreach ($rulesKroscek as $row) {
            $pdf->SetFillColor($row[4][0], $row[4][1], $row[4][2]);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell($cW3[0], 4.8, ' '.$row[0], 1, 0, 'L', true);
            $pdf->Cell($cW3[1], 4.8, ' '.$row[1], 1, 0, 'L', true);
            $pdf->Cell($cW3[2], 4.8, $row[2], 1, 0, 'C', true);
            $pdf->Cell($cW3[3], 4.8, ' '.$row[3], 1, 1, 'L', true);
        }

        $pdf->Ln(3);

        // Section 4: Hierarki Penentuan Juara & Pemecah Seri (Tie-Breaker)
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 4.5, '4. HIERARKI PENENTUAN JUARA & PEMECAH SERI (TIE-BREAKER HIERARCHY)', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 7.3);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->MultiCell(0, 3.8, 'Apabila terdapat UPT dengan perolehan akumulasi poin yang sama, sistem menentukan peringkat secara otomatis melalui 7 tingkatan hierarki kriteria objektif berikut secara berurutan:', 0, 'L');
        $pdf->Ln(1);

        $tieBreakers = [
            '1. Total Akumulasi Poin Tertinggi (Nilai total dari seluruh modul dan kroscek).',
            '2. Jumlah Medali Emas Terbanyak (Input pada Golden Hours sebelum jam 08.00 WIB).',
            '3. Jumlah Medali Perak Terbanyak (Input pada Jam Kerja Standar 08.00 - 11.00 WIB).',
            '4. Persentase Tingkat Kepatuhan Pelaporan (%) terhadap total hari efektif.',
            '5. Jumlah Hari Sempurna Terbanyak (Mencapai skor maksimal 100 poin penuh dalam 1 hari).',
            '6. Jumlah Kroscek Tahanan Sinkron Terbanyak (Akurasi data populasi tahanan).',
            '7. Rata-rata Waktu Selesai Input Paling Pagi (Earliest Average Input Completion Time).',
        ];

        $pdf->SetFont('helvetica', 'B', 7);
        foreach ($tieBreakers as $tb) {
            $pdf->Cell(0, 4, '   '.$tb, 0, 1, 'L');
        }

        // Footer penutup lampiran
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'I', 6.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 3.5, 'Pedoman ini disahkan oleh Kantor Wilayah Ditjenpas Banten dan berlaku secara terpadu pada sistem aplikasi SULTAN BANTEN.', 0, 1, 'C');

        // Output PDF
        $filename = "Laporan_Penetapan_Peringkat_Kedisiplinan_UPT_{$period['start_date']}_sd_{$period['end_date']}.pdf";

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Generate PDF Piagam Penghargaan Tunggal Juara 3 Besar Leaderboard UPT (A4 Landscape)
     *
     * @param  int  $rank  (1 = Emas, 2 = Perak, 3 = Perunggu)
     * @return Response
     */
    public static function generateCertificate(array $rankingData, int $rank = 1, array $signData = [])
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $instansi = Instansi::getFirst();
        $rankings = $rankingData['rankings'] ?? [];
        $period = $rankingData['period'] ?? [];

        // Ambil data UPT sesuai peringkat
        $targetUpt = null;
        foreach ($rankings as $r) {
            if (($r['rank'] ?? 0) === $rank) {
                $targetUpt = $r;
                break;
            }
        }

        if (! $targetUpt && ! empty($rankings)) {
            $targetUpt = $rankings[0];
            $rank = 1;
        }

        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public function Header() {}

            public function Footer() {}
        };

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor('Kanwil Ditjenpas Banten');
        $pdf->SetTitle("Piagam Penghargaan Juara {$rank} - ".($targetUpt['nama_upt'] ?? 'UPT'));
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(false);

        $pdf->AddPage();
        self::renderSingleCertificatePage($pdf, $targetUpt ?? [], $rank, $period, $instansi, $signData);

        $cleanUpt = Str::slug($targetUpt['nama_upt'] ?? "UPT_Juara_{$rank}");
        $filename = "Piagam_Penghargaan_Juara_{$rank}_{$cleanUpt}_{$period['start_date']}_sd_{$period['end_date']}.pdf";

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Generate PDF Paket Sertifikat 3 Besar (Juara 1, 2, dan 3) dalam Satu Dokumen Multi-Halaman (A4 Landscape)
     *
     * @return Response
     */
    public static function generateTop3Certificates(array $rankingData, array $signData = [])
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (! file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $instansi = Instansi::getFirst();
        $rankings = $rankingData['rankings'] ?? [];
        $period = $rankingData['period'] ?? [];

        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF
        {
            public function Header() {}

            public function Footer() {}
        };

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor('Kanwil Ditjenpas Banten');
        $pdf->SetTitle('Paket Piagam Penghargaan 3 Besar Kedisiplinan UPT');
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(false);

        $topWinners = array_slice($rankings, 0, 3);
        if (empty($topWinners)) {
            $topWinners = [[]];
        }

        foreach ($topWinners as $idx => $uptItem) {
            $rankNum = $idx + 1;
            $pdf->AddPage();
            self::renderSingleCertificatePage($pdf, $uptItem, $rankNum, $period, $instansi, $signData);
        }

        $filename = "Paket_Piagam_Penghargaan_Top3_UPT_{$period['start_date']}_sd_{$period['end_date']}.pdf";

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Render Halaman Piagam Penghargaan Tunggal pada Kanvas Landscape A4 (297 x 210 mm)
     */
    private static function renderSingleCertificatePage($pdf, array $upt, int $rank, array $period, $instansi, array $signData)
    {
        // Palet Warna & Ornamen sesuai Medali
        if ($rank === 1) {
            $primaryColor = [180, 83, 9];      // Rich Gold
            $accentColor = [217, 119, 6];      // Amber
            $badgeLabel = '🥇 JUARA I';
            $romanRank = 'I';
        } elseif ($rank === 2) {
            $primaryColor = [51, 65, 85];      // Slate 700
            $accentColor = [100, 116, 139];    // Slate 500
            $badgeLabel = '🥈 JUARA II';
            $romanRank = 'II';
        } else {
            $primaryColor = [154, 52, 18];     // Warm Bronze
            $accentColor = [194, 65, 12];     // Orange-700
            $badgeLabel = '🥉 JUARA III';
            $romanRank = 'III';
        }

        // 1. Double Ornamental Borders
        $pdf->SetDrawColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->SetLineWidth(1.8);
        $pdf->Rect(10, 10, 277, 190);

        $pdf->SetDrawColor($accentColor[0], $accentColor[1], $accentColor[2]);
        $pdf->SetLineWidth(0.6);
        $pdf->Rect(13, 13, 271, 184);

        // Ornamen Pojok Bingkai (4 Sudut Mewah)
        $pdf->SetFillColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Rect(10, 10, 8, 8, 'F');
        $pdf->Rect(279, 10, 8, 8, 'F');
        $pdf->Rect(10, 192, 8, 8, 'F');
        $pdf->Rect(279, 192, 8, 8, 'F');

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(12, 12, 4, 4, 'F');
        $pdf->Rect(281, 12, 4, 4, 'F');
        $pdf->Rect(12, 194, 4, 4, 'F');
        $pdf->Rect(281, 194, 4, 4, 'F');

        // 2. Logo & Kop Instansi di Bagian Atas
        $logoKop = null;
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoKop = public_path($instansi->logo_kop);
        } elseif (! empty($instansi?->logo) && file_exists(public_path($instansi->logo))) {
            $logoKop = public_path($instansi->logo);
        }

        if ($logoKop) {
            $pdf->Image($logoKop, 138.5, 17, 20);
            $pdf->SetY(39);
        } else {
            $pdf->SetY(22);
        }

        $pdf->SetFont('helvetica', 'B', 9.5);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(0, 4.5, 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(0, 5, 'KANTOR WILAYAH BANTEN • DIREKTORAT JENDERAL PEMASYARAKATAN', 0, 1, 'C');

        // Garis Pembatas Halus di Bawah Kop
        $pdf->SetDrawColor($accentColor[0], $accentColor[1], $accentColor[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(60, $pdf->GetY() + 1.5, 237, $pdf->GetY() + 1.5);
        $pdf->Ln(4.5);

        // 3. Judul Sertifikat & Nomor Piagam
        $pdf->SetFont('times', 'B', 24);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(0, 9, 'PIAGAM PENGHARGAAN', 0, 1, 'C');

        $certYear = date('Y');
        $certNum = "Nomor: W.12.PAS/UM.01.01/{$certYear}/PIAGAM-".str_pad($rank, 3, '0', STR_PAD_LEFT);
        $pdf->SetFont('helvetica', 'I', 8.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 4.5, $certNum, 0, 1, 'C');
        $pdf->Ln(3);

        // 4. Bagian Penerima Piagam
        $pdf->SetFont('times', 'I', 11.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 5, 'Diberikan Kepada :', 0, 1, 'C');
        $pdf->Ln(1);

        $uptName = strtoupper($upt['nama_upt'] ?? 'UPT PEMASYARAKATAN');
        $pdf->SetFont('times', 'B', 18);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 7.5, $uptName, 0, 1, 'C');

        // Garis Dekoratif di Bawah Nama UPT
        $nameWidth = $pdf->GetStringWidth($uptName);
        $lineStart = max(50, 148.5 - ($nameWidth / 2) - 10);
        $lineEnd = min(247, 148.5 + ($nameWidth / 2) + 10);
        $pdf->SetDrawColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->SetLineWidth(0.8);
        $pdf->Line($lineStart, $pdf->GetY() + 1, $lineEnd, $pdf->GetY() + 1);
        $pdf->Ln(4);

        // 5. Predikat & Narasi Prestasi
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 4.5, 'SEBAGAI', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 12.5);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(0, 6, "{$badgeLabel} — PERINGKAT KEDISIPLINAN & KEPATUHAN PELAPORAN HARIAN", 0, 1, 'C');
        $pdf->Ln(2);

        $tglMulaiIndo = function_exists('formatIndonesianDate') ? formatIndonesianDate($period['start_date'] ?? date('Y-m-01'), false) : ($period['start_date'] ?? '-');
        $tglAkhirIndo = function_exists('formatIndonesianDate') ? formatIndonesianDate($period['end_date'] ?? date('Y-m-t'), false) : ($period['end_date'] ?? '-');
        $periodeIndo = "{$tglMulaiIndo} s/d {$tglAkhirIndo}";

        $pointsFormatted = number_format($upt['total_points'] ?? 0);
        $pctFormatted = $upt['persentase_kepatuhan'] ?? 0;
        $goldCount = $upt['count_gold'] ?? 0;
        $kroscekCount = $upt['count_kroscek_sesuai'] ?? 0;

        $narrative = "Atas dedikasi, integritas, dan keteladanan kedisiplinan pelaporan data harian pemasyarakatan secara tepat waktu, akurat, dan sinkron melalui aplikasi SULTAN BANTEN pada Periode {$periodeIndo} dengan capaian Total Skor {$pointsFormatted} Poin (Tingkat Kepatuhan {$pctFormatted}%), {$goldCount}x Golden Hours, serta {$kroscekCount}x Validasi Kroscek Sinkron.";

        $pdf->SetFont('times', '', 9.5);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetX(35);
        $pdf->MultiCell(227, 4.8, $narrative, 0, 'C');

        // 6. Tanda Tangan Kanwil & QR Code Keabsahan (Bagian Bawah)
        $yBottom = 153;

        // QR Code Validasi Dokumen (Kiri Bawah)
        $qrText = "✅ Dokumen Sah & Valid\nPIAGAM PENGHARGAAN {$badgeLabel}\nUPT: {$uptName}\nSkor: {$pointsFormatted} Poin ({$pctFormatted}%)\nPeriode: {$periodeIndo}\nSULTAN BANTEN @Ditjenpas Banten";
        $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
        $pdf->write2DBarcode($qrText, 'QRCODE,L', 26, $yBottom, 21, 21, $style, 'N');

        $pdf->SetXY(20, $yBottom + 22);
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(33, 3.2, 'Verifikasi Sertifikat Digital', 0, 2, 'C');
        $pdf->Cell(33, 3.2, 'SULTAN BANTEN Ditjenpas', 0, 0, 'C');

        // Bagian Tanda Tangan Pejabat (Kanan Bawah)
        $sigX = 185;
        $pdf->SetXY($sigX, $yBottom - 3);
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $tglTtd = function_exists('formatIndonesianDate') ? formatIndonesianDate(date('Y-m-d'), false) : date('d/m/Y');
        $tempatTtd = $signData['tempat_ttd'] ?? 'Serang';
        $jabatanTtd = $signData['jabatan_ttd'] ?? 'Kepala Kantor Wilayah';
        $namaTtd = $signData['nama_ttd'] ?? 'DODOT ADIKOESWANTO';
        $nipTtd = $signData['nip_ttd'] ?? '';

        $pdf->Cell(85, 4.5, $tempatTtd.', '.$tglTtd, 0, 1, 'C');
        $pdf->SetX($sigX);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->Cell(85, 4.5, $jabatanTtd, 0, 1, 'C');

        $ySigImg = $pdf->GetY();
        $showTtdImage = ! isset($signData['show_ttd_image']) || (bool) $signData['show_ttd_image'];
        $showStempel = ! isset($signData['show_stempel']) || (bool) $signData['show_stempel'];

        if ($showTtdImage && ! empty($instansi?->ttd_image) && file_exists(public_path($instansi->ttd_image))) {
            $pdf->Image(public_path($instansi->ttd_image), $sigX + 22, $ySigImg - 2, 40, 0);
        }
        if ($showStempel && ! empty($instansi?->stempel_image) && file_exists(public_path($instansi->stempel_image))) {
            $pdf->Image(public_path($instansi->stempel_image), $sigX - 6, $ySigImg - 4, 34, 0);
        }

        $pdf->SetXY($sigX, $ySigImg + 18);
        $pdf->SetFont('helvetica', 'B', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(85, 4.5, $namaTtd, 0, 1, 'C');
        if (! empty($nipTtd)) {
            $pdf->SetX($sigX);
            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->Cell(85, 3.5, 'NIP. '.$nipTtd, 0, 1, 'C');
        }
    }
}
