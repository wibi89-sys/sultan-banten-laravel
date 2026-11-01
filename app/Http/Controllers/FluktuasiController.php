<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\Upt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FluktuasiController extends Controller
{
    /**
     * Tampilkan Halaman Utama Modul Dinamika / Fluktuasi Tambah Kurang Penghuni
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Tanggal Acuan (Hari Ini / Terpilih) dan Tanggal Pembanding (Kemarin)
        $tanggal = $request->query('tanggal', date('Y-m-d'));
        $defaultKemarin = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
        $tanggalPembanding = $request->query('tanggal_pembanding', $defaultKemarin);

        // Filter UPT jika user adalah operator
        $userUptFilter = (! $user->isAdmin() && ! empty($user->upt_id)) ? (int) $user->upt_id : 0;
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        // Ambil Daftar UPT
        $uptQuery = Upt::orderBy('nama_upt');
        if ($filterUpt > 0) {
            $uptQuery->where('id', $filterUpt);
        }
        $uptList = $uptQuery->get();
        $uptIds = $uptList->pluck('id')->toArray();

        // 1. Data Penghuni Hari Ini ($tanggal)
        $penghuniCurrent = DB::table('data_penghuni')
            ->select(
                'upt_id',
                DB::raw("SUM(CASE WHEN klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') THEN (tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) ELSE 0 END) as total_tahanan"),
                DB::raw("SUM(CASE WHEN klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') THEN (narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) ELSE 0 END) as total_napi")
            )
            ->where('tanggal', $tanggal)
            ->whereIn('upt_id', $uptIds)
            ->groupBy('upt_id')
            ->get()
            ->keyBy('upt_id');

        // 2. Data Penghuni Hari Kemarin / Pembanding ($tanggalPembanding)
        $penghuniPrev = DB::table('data_penghuni')
            ->select(
                'upt_id',
                DB::raw("SUM(CASE WHEN klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') THEN (tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) ELSE 0 END) as total_tahanan"),
                DB::raw("SUM(CASE WHEN klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') THEN (narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) ELSE 0 END) as total_napi")
            )
            ->where('tanggal', $tanggalPembanding)
            ->whereIn('upt_id', $uptIds)
            ->groupBy('upt_id')
            ->get()
            ->keyBy('upt_id');

        // 3. Data Overstaying A.I s/d A.V Hari Ini
        $overstayingCurrent = DB::table('data_overstaying')
            ->select(
                'upt_id',
                'tahanan_ai', 'tahanan_aii', 'tahanan_aiii', 'tahanan_aiv', 'tahanan_av',
                DB::raw('(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) as total_tahanan'),
                'overstaying_ai', 'overstaying_aii', 'overstaying_aiii', 'overstaying_aiv', 'overstaying_av',
                DB::raw('(overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av) as total_overstaying')
            )
            ->where('tanggal', $tanggal)
            ->whereIn('upt_id', $uptIds)
            ->get()
            ->keyBy('upt_id');

        // 4. Data Overstaying A.I s/d A.V Hari Pembanding
        $overstayingPrev = DB::table('data_overstaying')
            ->select(
                'upt_id',
                'tahanan_ai', 'tahanan_aii', 'tahanan_aiii', 'tahanan_aiv', 'tahanan_av',
                DB::raw('(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) as total_tahanan'),
                'overstaying_ai', 'overstaying_aii', 'overstaying_aiii', 'overstaying_aiv', 'overstaying_av',
                DB::raw('(overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av) as total_overstaying')
            )
            ->where('tanggal', $tanggalPembanding)
            ->whereIn('upt_id', $uptIds)
            ->get()
            ->keyBy('upt_id');

        // 5. Data Bebas Harian pada Tanggal Acuan ($tanggal)
        $bebasCurrent = DB::table('data_bebas_harian')
            ->select(
                'upt_id',
                DB::raw('SUM(bebas_murni) as bebas_murni'),
                DB::raw('SUM(pb) as pb'),
                DB::raw('SUM(cb) as cb'),
                DB::raw('SUM(cmb) as cmb'),
                DB::raw('SUM(asimilasi) as asimilasi'),
                DB::raw('SUM(bdh) as bdh'),
                DB::raw('SUM(lainnya) as lainnya'),
                DB::raw('SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya) as total_bebas')
            )
            ->where('tanggal', $tanggal)
            ->whereIn('upt_id', $uptIds)
            ->groupBy('upt_id')
            ->get()
            ->keyBy('upt_id');

        // Kompilasi Matriks Fluktuasi Per UPT & Hitung Akumulasi
        $rows = [];
        $summary = [
            'total_tahanan_prev' => 0,
            'total_tahanan_curr' => 0,
            'delta_tahanan' => 0,
            'total_napi_prev' => 0,
            'total_napi_curr' => 0,
            'delta_napi' => 0,
            'total_penghuni_prev' => 0,
            'total_penghuni_curr' => 0,
            'delta_penghuni' => 0,
            'total_bebas_murni' => 0,
            'total_pb' => 0,
            'total_cb' => 0,
            'total_cmb' => 0,
            'total_asimilasi' => 0,
            'total_bdh' => 0,
            'total_bebas' => 0,
            'estimasi_alih_status' => 0,
            'estimasi_tahanan_baru' => 0,
        ];

        foreach ($uptList as $upt) {
            $uptId = $upt->id;

            $pCurr = $penghuniCurrent->get($uptId);
            $pPrev = $penghuniPrev->get($uptId);

            $oCurr = $overstayingCurrent->get($uptId);
            $oPrev = $overstayingPrev->get($uptId);

            $bCurr = $bebasCurrent->get($uptId);

            $tahananCurr = (int) ($pCurr?->total_tahanan ?? 0);
            $tahananPrev = (int) ($pPrev?->total_tahanan ?? 0);
            $deltaTahanan = $tahananCurr - $tahananPrev;

            $napiCurr = (int) ($pCurr?->total_napi ?? 0);
            $napiPrev = (int) ($pPrev?->total_napi ?? 0);
            $deltaNapi = $napiCurr - $napiPrev;

            $penghuniTotalCurr = $tahananCurr + $napiCurr;
            $penghuniTotalPrev = $tahananPrev + $napiPrev;
            $deltaPenghuni = $penghuniTotalCurr - $penghuniTotalPrev;

            // Rincian Bebas Harian
            $bebasMurni = (int) ($bCurr?->bebas_murni ?? 0);
            $pb = (int) ($bCurr?->pb ?? 0);
            $cb = (int) ($bCurr?->cb ?? 0);
            $cmb = (int) ($bCurr?->cmb ?? 0);
            $asimilasi = (int) ($bCurr?->asimilasi ?? 0);
            $bdh = (int) ($bCurr?->bdh ?? 0);
            $lainnya = (int) ($bCurr?->lainnya ?? 0);
            $totalBebas = (int) ($bCurr?->total_bebas ?? 0);
            $bebasNapi = $bebasMurni + $pb + $cb + $cmb + $asimilasi + $lainnya;

            // Rincian Overstaying & Fluktuasi AI s/d AV
            $aiCurr = (int) ($oCurr?->tahanan_ai ?? 0);
            $aiPrev = (int) ($oPrev?->tahanan_ai ?? 0);
            $deltaAi = $aiCurr - $aiPrev;

            $aiiCurr = (int) ($oCurr?->tahanan_aii ?? 0);
            $aiiPrev = (int) ($oPrev?->tahanan_aii ?? 0);
            $deltaAii = $aiiCurr - $aiiPrev;

            $aiiiCurr = (int) ($oCurr?->tahanan_aiii ?? 0);
            $aiiiPrev = (int) ($oPrev?->tahanan_aiii ?? 0);
            $deltaAiii = $aiiiCurr - $aiiiPrev;

            $aivCurr = (int) ($oCurr?->tahanan_aiv ?? 0);
            $aivPrev = (int) ($oPrev?->tahanan_aiv ?? 0);
            $deltaAiv = $aivCurr - $aivPrev;

            $avCurr = (int) ($oCurr?->tahanan_av ?? 0);
            $avPrev = (int) ($oPrev?->tahanan_av ?? 0);
            $deltaAv = $avCurr - $avPrev;

            // Analisis Otomatis (Narasi Dinamika Operasional)
            $analisisList = [];

            // 1. Bebas Demi Hukum (BDH) pada Tahanan
            if ($bdh > 0) {
                $analisisList[] = "<span class=\"text-danger fw-bold\"><i class=\"fas fa-balance-scale me-1\"></i>{$bdh} Tahanan Bebas Demi Hukum (BDH)</span>";
            }

            // 2. Alih status vonis inkrah (Tahanan berkurang, Napi bertambah)
            $alihStatus = 0;
            if ($deltaTahanan < 0 && $deltaNapi > 0) {
                $alihStatus = min(abs($deltaTahanan), $deltaNapi);
                $analisisList[] = "<span class=\"text-primary fw-semibold\"><i class=\"fas fa-sync-alt me-1\"></i>{$alihStatus} Tahanan Vonis Inkrah (Alih Status ke Narapidana)</span>";
            }

            // 3. Tahanan Baru Masuk (Penerimaan A.I/A.II/A.III)
            $tahananBaruMasuk = 0;
            if ($deltaTahanan > 0) {
                $tahananBaruMasuk = $deltaTahanan;
                $tahapMasuk = [];
                if ($deltaAi > 0) {
                    $tahapMasuk[] = "+{$deltaAi} Polisi (A.I)";
                }
                if ($deltaAii > 0) {
                    $tahapMasuk[] = "+{$deltaAii} Jaksa (A.II)";
                }
                if ($deltaAiii > 0) {
                    $tahapMasuk[] = "+{$deltaAiii} PN (A.III)";
                }
                $rincianMasuk = ! empty($tahapMasuk) ? ' ('.implode(', ', $tahapMasuk).')' : '';
                $analisisList[] = "<span class=\"text-success fw-semibold\"><i class=\"fas fa-user-plus me-1\"></i>+{$deltaTahanan} Tahanan Baru Masuk{$rincianMasuk}</span>";
            }

            // 4. Narapidana Bebas Resmi
            if ($bebasNapi > 0) {
                $rincianBebas = [];
                if ($pb > 0) {
                    $rincianBebas[] = "{$pb} PB";
                }
                if ($cb > 0) {
                    $rincianBebas[] = "{$cb} CB";
                }
                if ($cmb > 0) {
                    $rincianBebas[] = "{$cmb} CMB";
                }
                if ($asimilasi > 0) {
                    $rincianBebas[] = "{$asimilasi} Asimilasi";
                }
                if ($bebasMurni > 0) {
                    $rincianBebas[] = "{$bebasMurni} Bebas Murni";
                }
                $teksBebas = ! empty($rincianBebas) ? ' ('.implode(', ', $rincianBebas).')' : '';
                $analisisList[] = "<span class=\"text-danger fw-semibold\"><i class=\"fas fa-door-open me-1\"></i>-{$bebasNapi} Narapidana Bebas{$teksBebas}</span>";
            }

            // 5. Jika total penghuni tetap sama
            if ($deltaPenghuni === 0 && $deltaTahanan === 0 && $deltaNapi === 0) {
                $analisisList[] = '<span class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Penghuni Stabil (Nihil Tambah Kurang)</span>';
            } elseif ($deltaPenghuni === 0 && ($deltaTahanan !== 0 || $deltaNapi !== 0)) {
                $analisisList[] = '<span class="text-info fw-semibold"><i class="fas fa-exchange-alt me-1"></i>Total Fisik Tetap (Pergeseran Status Internal)</span>';
            }

            $rows[] = [
                'upt_id' => $uptId,
                'nama_upt' => $upt->nama_upt,
                'tahanan_prev' => $tahananPrev,
                'tahanan_curr' => $tahananCurr,
                'delta_tahanan' => $deltaTahanan,
                'napi_prev' => $napiPrev,
                'napi_curr' => $napiCurr,
                'delta_napi' => $deltaNapi,
                'penghuni_prev' => $penghuniTotalPrev,
                'penghuni_curr' => $penghuniTotalCurr,
                'delta_penghuni' => $deltaPenghuni,
                'bebas_murni' => $bebasMurni,
                'pb' => $pb,
                'cb' => $cb,
                'cmb' => $cmb,
                'asimilasi' => $asimilasi,
                'bdh' => $bdh,
                'lainnya' => $lainnya,
                'total_bebas' => $totalBebas,
                'bebas_napi' => $bebasNapi,
                'ai_curr' => $aiCurr,
                'ai_prev' => $aiPrev,
                'delta_ai' => $deltaAi,
                'aii_curr' => $aiiCurr,
                'aii_prev' => $aiiPrev,
                'delta_aii' => $deltaAii,
                'aiii_curr' => $aiiiCurr,
                'aiii_prev' => $aiiiPrev,
                'delta_aiii' => $deltaAiii,
                'aiv_curr' => $aivCurr,
                'aiv_prev' => $aivPrev,
                'delta_aiv' => $deltaAiv,
                'av_curr' => $avCurr,
                'av_prev' => $avPrev,
                'delta_av' => $deltaAv,
                'alih_status' => $alihStatus,
                'tahanan_baru' => $tahananBaruMasuk,
                'analisis' => $analisisList,
            ];

            // Akumulasi ringkasan wilayah
            $summary['total_tahanan_prev'] += $tahananPrev;
            $summary['total_tahanan_curr'] += $tahananCurr;
            $summary['delta_tahanan'] += $deltaTahanan;
            $summary['total_napi_prev'] += $napiPrev;
            $summary['total_napi_curr'] += $napiCurr;
            $summary['delta_napi'] += $deltaNapi;
            $summary['total_penghuni_prev'] += $penghuniTotalPrev;
            $summary['total_penghuni_curr'] += $penghuniTotalCurr;
            $summary['delta_penghuni'] += $deltaPenghuni;
            $summary['total_bebas_murni'] += $bebasMurni;
            $summary['total_pb'] += $pb;
            $summary['total_cb'] += $cb;
            $summary['total_cmb'] += $cmb;
            $summary['total_asimilasi'] += $asimilasi;
            $summary['total_bdh'] += $bdh;
            $summary['total_bebas'] += $totalBebas;
            $summary['estimasi_alih_status'] += $alihStatus;
            $summary['estimasi_tahanan_baru'] += $tahananBaruMasuk;
        }

        $allUptList = Upt::orderBy('nama_upt')->get();
        $instansi = Instansi::first();

        return view('fluktuasi.index', compact(
            'rows',
            'summary',
            'tanggal',
            'tanggalPembanding',
            'filterUpt',
            'userUptFilter',
            'allUptList',
            'instansi'
        ));
    }
}
