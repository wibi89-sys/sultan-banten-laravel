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
        $userUptFilter = ($user && ! $user->isAdmin() && ! empty($user->upt_id)) ? (int) $user->upt_id : 0;
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

        // 1b. Rincian Narapidana & Tahanan Per Klasifikasi Hari Ini
        $penghuniKlasifikasiCurr = DB::table('data_penghuni')
            ->select(
                'upt_id',
                'klasifikasi_pidana',
                DB::raw('(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) as tahanan'),
                DB::raw('(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as narapidana')
            )
            ->where('tanggal', $tanggal)
            ->whereIn('upt_id', $uptIds)
            ->get()
            ->groupBy('upt_id');

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

        // 2b. Rincian Narapidana & Tahanan Per Klasifikasi Kemarin
        $penghuniKlasifikasiPrev = DB::table('data_penghuni')
            ->select(
                'upt_id',
                'klasifikasi_pidana',
                DB::raw('(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) as tahanan'),
                DB::raw('(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as narapidana')
            )
            ->where('tanggal', $tanggalPembanding)
            ->whereIn('upt_id', $uptIds)
            ->get()
            ->groupBy('upt_id');

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

        // 5. Data Bebas Harian Faktor Pengurang Jumlah Penghuni
        // Perubahan jumlah penghuni antara $tanggalPembanding (H-1) dan $tanggal (H) disebabkan oleh peristiwa bebas
        // yang terjadi pada hari kemarin ($tanggalPembanding) atau rentang sebelum apel pagi $tanggal
        $tglBebasMulai = $tanggalPembanding;
        $tglBebasSelesai = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
        if ($tglBebasSelesai < $tglBebasMulai) {
            $tglBebasSelesai = $tglBebasMulai;
        }

        $bebasCurrent = DB::table('data_bebas_harian')
            ->select(
                'upt_id',
                DB::raw('SUM(bebas_murni) as bebas_murni'),
                DB::raw('SUM(pb) as pb'),
                DB::raw('SUM(cb) as cb'),
                DB::raw('SUM(cmb) as cmb'),
                DB::raw('SUM(asimilasi) as asimilasi'),
                DB::raw('SUM(bdh) as bdh'),
                DB::raw('SUM(mutasi_keluar) as mutasi_keluar'),
                DB::raw('SUM(lainnya) as lainnya'),
                DB::raw('SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + mutasi_keluar + lainnya) as total_pengurang'),
                DB::raw('SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya) as total_bebas')
            )
            ->whereBetween('tanggal', [$tglBebasMulai, $tglBebasSelesai])
            ->whereIn('upt_id', $uptIds)
            ->groupBy('upt_id')
            ->get()
            ->keyBy('upt_id');

        // Query rincian mutasi keluar untuk keperluan narasi analisis & modal detail
        $mutasiDetail = DB::table('data_bebas_harian')
            ->select('upt_id', 'klasifikasi_pidana', 'mutasi_keluar', 'ket_mutasi')
            ->whereBetween('tanggal', [$tglBebasMulai, $tglBebasSelesai])
            ->whereIn('upt_id', $uptIds)
            ->where('mutasi_keluar', '>', 0)
            ->get()
            ->groupBy('upt_id');

        // Kompilasi Matriks Fluktuasi Per UPT & Hitung Akumulasi
        $rows = [];
        $summary = [
            'total_kapasitas' => 0,
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
            'total_mutasi_keluar' => 0,
            'total_bebas' => 0,
            'total_pengurang' => 0,
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

            $kapasitasUpt = (int) ($upt->kapasitas ?? 0);
            $overcrowdingUpt = getOvercrowdingStatus($penghuniTotalCurr, $kapasitasUpt);

            // Rincian Komparasi Narapidana & Tahanan Per Klasifikasi Tindak Pidana
            $klasCurrList = ($penghuniKlasifikasiCurr->get($uptId) ?? collect())->keyBy('klasifikasi_pidana');
            $klasPrevList = ($penghuniKlasifikasiPrev->get($uptId) ?? collect())->keyBy('klasifikasi_pidana');

            $daftarKlasifikasi = [
                'Pidana Umum', 'Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna',
                'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering',
                'Genosida', 'UU ITE',
            ];

            $komparasiNapi = [];
            $napiBerkurangList = [];
            $napiBertambahList = [];

            foreach ($daftarKlasifikasi as $klas) {
                $nPrev = (int) ($klasPrevList->get($klas)?->narapidana ?? 0);
                $nCurr = (int) ($klasCurrList->get($klas)?->narapidana ?? 0);
                $deltaK = $nCurr - $nPrev;

                $tPrev = (int) ($klasPrevList->get($klas)?->tahanan ?? 0);
                $tCurr = (int) ($klasCurrList->get($klas)?->tahanan ?? 0);
                $deltaT = $tCurr - $tPrev;

                $komparasiNapi[] = [
                    'klasifikasi' => $klas,
                    'prev' => $nPrev,
                    'curr' => $nCurr,
                    'delta' => $deltaK,
                    'tahanan_prev' => $tPrev,
                    'tahanan_curr' => $tCurr,
                    'tahanan_delta' => $deltaT,
                ];

                if ($deltaK < 0) {
                    $napiBerkurangList[] = "{$klas}: {$deltaK}";
                } elseif ($deltaK > 0) {
                    $napiBertambahList[] = "{$klas}: +{$deltaK}";
                }
            }

            // Rincian Bebas Harian & Mutasi Keluar
            $bebasMurni = (int) ($bCurr?->bebas_murni ?? 0);
            $pb = (int) ($bCurr?->pb ?? 0);
            $cb = (int) ($bCurr?->cb ?? 0);
            $cmb = (int) ($bCurr?->cmb ?? 0);
            $asimilasi = (int) ($bCurr?->asimilasi ?? 0);
            $bdh = (int) ($bCurr?->bdh ?? 0);
            $mutasiKeluar = (int) ($bCurr?->mutasi_keluar ?? 0);
            $lainnya = (int) ($bCurr?->lainnya ?? 0);
            $totalBebas = (int) ($bCurr?->total_bebas ?? 0);
            $totalPengurang = (int) ($bCurr?->total_pengurang ?? 0);
            $bebasNapi = $bebasMurni + $pb + $cb + $cmb + $asimilasi + $lainnya;
            $pengurangNapi = $bebasNapi + $mutasiKeluar;

            // Rincian Keterangan Mutasi Keluar
            $daftarMutasiUpt = $mutasiDetail->get($uptId) ?? collect();
            $rincianMutasiList = [];
            foreach ($daftarMutasiUpt as $dm) {
                $ket = ! empty($dm->ket_mutasi) ? " ({$dm->ket_mutasi})" : '';
                $rincianMutasiList[] = "{$dm->klasifikasi_pidana}: {$dm->mutasi_keluar} Orang{$ket}";
            }

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

            // 2. Perpindahan Tahap Upaya Hukum & Alih Status Vonis Inkrah (A.III, A.IV, A.V)

            // a. Perpindahan Kasasi ke MA (A.IV -> A.V)
            $kasasi = 0;
            if ($deltaAiv < 0 && $deltaAv > 0) {
                $kasasi = min(abs($deltaAiv), $deltaAv);
            }
            $sisaTurunAiv = ($deltaAiv < 0) ? (abs($deltaAiv) - $kasasi) : 0;
            $sisaTurunAv = ($deltaAv < 0) ? abs($deltaAv) : 0;

            // b. Perpindahan Banding ke PT (A.III -> A.IV)
            $banding = 0;
            if ($deltaAiii < 0 && $deltaAiv > 0) {
                $banding = min(abs($deltaAiii), $deltaAiv);
            }
            $sisaTurunAiii = ($deltaAiii < 0) ? (abs($deltaAiii) - $banding) : 0;

            // c. Pelimpahan ke Sidang PN (A.II -> A.III)
            $pelimpahanPn = 0;
            if ($deltaAii < 0 && $deltaAiii > 0) {
                $pelimpahanPn = min(abs($deltaAii), $deltaAiii);
            }

            // d. Tahap II (A.I -> A.II)
            $tahap2 = 0;
            if ($deltaAi < 0 && $deltaAii > 0) {
                $tahap2 = min(abs($deltaAi), $deltaAii);
            }

            // e. Alih Status Vonis Inkrah (Pengurangan A.III, A.IV, A.V yang terserap menjadi Narapidana)
            $alihStatus = 0;
            $rincianInkrah = [];

            if ($deltaNapi > 0) {
                $sisaNapiBertambah = $deltaNapi;

                // 1. Serap dari putusan Kasasi Mahkamah Agung (A.V)
                if ($sisaTurunAv > 0 && $sisaNapiBertambah > 0) {
                    $ambilAv = min($sisaTurunAv, $sisaNapiBertambah);
                    $alihStatus += $ambilAv;
                    $sisaNapiBertambah -= $ambilAv;
                    $rincianInkrah[] = "{$ambilAv} Kasasi MA (A.V)";
                }

                // 2. Serap dari putusan Banding Pengadilan Tinggi (A.IV)
                if ($sisaTurunAiv > 0 && $sisaNapiBertambah > 0) {
                    $ambilAiv = min($sisaTurunAiv, $sisaNapiBertambah);
                    $alihStatus += $ambilAiv;
                    $sisaNapiBertambah -= $ambilAiv;
                    $rincianInkrah[] = "{$ambilAiv} Banding PT (A.IV)";
                }

                // 3. Serap dari vonis Pengadilan Negeri (A.III)
                if ($sisaTurunAiii > 0 && $sisaNapiBertambah > 0) {
                    $ambilAiii = min($sisaTurunAiii, $sisaNapiBertambah);
                    $alihStatus += $ambilAiii;
                    $sisaNapiBertambah -= $ambilAiii;
                    $rincianInkrah[] = "{$ambilAiii} PN (A.III)";
                }

                // Fallback umum jika rincian A.I-A.V tidak lengkap tapi deltaTahanan negatif
                if ($alihStatus === 0 && $deltaTahanan < 0) {
                    $alihStatus = min(abs($deltaTahanan), $deltaNapi);
                }
            }

            // Narasi Alih Status Vonis Inkrah
            if ($alihStatus > 0) {
                $teksRincianInkrah = ! empty($rincianInkrah) ? ' ('.implode(', ', $rincianInkrah).')' : '';
                $analisisList[] = "<span class=\"text-primary fw-semibold\"><i class=\"fas fa-gavel me-1\"></i>{$alihStatus} Tahanan Vonis Inkrah (Alih Status &rarr; Narapidana){$teksRincianInkrah}</span>";
            }

            // Narasi Perpindahan Tahapan Peradilan
            if ($kasasi > 0) {
                $analisisList[] = "<span class=\"text-secondary fw-semibold\"><i class=\"fas fa-university me-1\"></i>{$kasasi} Tahanan Upaya Hukum Kasasi (PT A.IV &rarr; MA A.V)</span>";
            }
            if ($banding > 0) {
                $analisisList[] = "<span class=\"text-secondary fw-semibold\"><i class=\"fas fa-landmark me-1\"></i>{$banding} Tahanan Upaya Hukum Banding (PN A.III &rarr; PT A.IV)</span>";
            }
            if ($pelimpahanPn > 0) {
                $analisisList[] = "<span class=\"text-info fw-semibold\"><i class=\"fas fa-balance-scale-right me-1\"></i>{$pelimpahanPn} Tahanan Masuk Sidang (Jaksa A.II &rarr; PN A.III)</span>";
            }
            if ($tahap2 > 0) {
                $analisisList[] = "<span class=\"text-secondary fw-semibold\"><i class=\"fas fa-share me-1\"></i>{$tahap2} Tahanan Tahap II (Polisi A.I &rarr; Jaksa A.II)</span>";
            }

            // 3. Tahanan Baru Masuk (Penerimaan A.I / A.II / A.III)
            $tahananBaruMasuk = 0;
            if ($deltaTahanan > 0) {
                $tahananBaruMasuk = $deltaTahanan;
                $tahapMasuk = [];
                if ($deltaAi > 0) {
                    $tahapMasuk[] = "+{$deltaAi} Polisi (A.I)";
                }
                if ($deltaAii > 0 && ($deltaAi >= 0 || $deltaAii > abs($deltaAi))) {
                    $netAii = ($deltaAi < 0) ? ($deltaAii - abs($deltaAi)) : $deltaAii;
                    if ($netAii > 0) {
                        $tahapMasuk[] = "+{$netAii} Jaksa (A.II)";
                    }
                }
                if ($deltaAiii > 0 && ($deltaAii >= 0 || $deltaAiii > abs($deltaAii))) {
                    $netAiii = ($deltaAii < 0) ? ($deltaAiii - abs($deltaAii)) : $deltaAiii;
                    if ($netAiii > 0) {
                        $tahapMasuk[] = "+{$netAiii} PN (A.III)";
                    }
                }
                $rincianMasuk = ! empty($tahapMasuk) ? ' ('.implode(', ', $tahapMasuk).')' : '';
                $analisisList[] = "<span class=\"text-success fw-semibold\"><i class=\"fas fa-user-plus me-1\"></i>+{$deltaTahanan} Tahanan Baru Masuk{$rincianMasuk}</span>";
            }

            // 4. Narapidana Baru Masuk (Penerimaan / Mutasi Masuk di luar alih status)
            if ($deltaNapi > 0 && $deltaNapi > $alihStatus) {
                $napiBaru = $deltaNapi - $alihStatus;
                $analisisList[] = "<span class=\"text-success fw-semibold\"><i class=\"fas fa-user-plus me-1\"></i>+{$napiBaru} Narapidana Baru Masuk (Penerimaan / Mutasi Masuk)</span>";
            }

            // 5. Narapidana Mutasi Keluar
            if ($mutasiKeluar > 0) {
                $teksMutasi = ! empty($rincianMutasiList) ? ' ('.implode(', ', $rincianMutasiList).')' : '';
                $analisisList[] = "<span class=\"text-secondary fw-semibold\"><i class=\"fas fa-truck-moving me-1\"></i>-{$mutasiKeluar} Narapidana Mutasi Keluar{$teksMutasi}</span>";
            }

            // 6. Narapidana Bebas (Faktor Pengurang dari hari kemarin)
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

            // 7. Narapidana Berkurang
            if ($deltaNapi < 0) {
                $totalBerkurang = abs($deltaNapi);
                $rincianBerkurang = ! empty($napiBerkurangList) ? ' ('.implode(', ', $napiBerkurangList).')' : '';

                if ($pengurangNapi > 0) {
                    if ($totalBerkurang > $pengurangNapi) {
                        $selisihTidakTercatat = $totalBerkurang - $pengurangNapi;
                        $analisisList[] = "<span class=\"text-secondary small d-block\"><i class=\"fas fa-info-circle me-1\"></i>Selisih -{$selisihTidakTercatat} Narapidana di luar Laporan Bebas/Mutasi{$rincianBerkurang} (Potensi Ekspirasi atau Mutasi belum terinput)</span>";
                    }
                } else {
                    $analisisList[] = "<span class=\"text-danger fw-semibold\"><i class=\"fas fa-user-minus me-1\"></i>-{$totalBerkurang} Narapidana Berkurang{$rincianBerkurang}</span>";
                    $analisisList[] = '<span class="text-warning small d-block"><i class="fas fa-question-circle me-1"></i>Nihil di Laporan Bebas &amp; Mutasi (Potensi Mutasi Keluar ke Lapas lain atau Ekspirasi belum diinput operator)</span>';
                }
            }

            // 8. Jika total penghuni tetap sama atau nihil perubahan
            if ($deltaPenghuni === 0 && $deltaTahanan === 0 && $deltaNapi === 0) {
                if ($totalPengurang > 0) {
                    $analisisList[] = "<span class=\"text-warning fw-semibold\"><i class=\"fas fa-exclamation-triangle me-1\"></i>Perlu Kroscek: Tercatat {$totalPengurang} Pengeluaran (Bebas/Mutasi) kemarin tapi Jumlah Penghuni Tetap Sama (Potensi Belum Terupdate di Laporan Penghuni)</span>";
                } else {
                    $analisisList[] = '<span class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Penghuni Stabil (Nihil Tambah Kurang)</span>';
                }
            } elseif ($deltaPenghuni === 0 && ($deltaTahanan !== 0 || $deltaNapi !== 0)) {
                $analisisList[] = '<span class="text-info fw-semibold"><i class="fas fa-exchange-alt me-1"></i>Total Penghuni Tetap (Pergeseran Status Internal)</span>';
            }

            $rows[] = [
                'upt_id' => $uptId,
                'nama_upt' => $upt->nama_upt,
                'kapasitas' => $kapasitasUpt,
                'overcrowding' => $overcrowdingUpt,
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
                'mutasi_keluar' => $mutasiKeluar,
                'rincian_mutasi' => $rincianMutasiList,
                'lainnya' => $lainnya,
                'total_bebas' => $totalBebas,
                'total_pengurang' => $totalPengurang,
                'bebas_napi' => $bebasNapi,
                'pengurang_napi' => $pengurangNapi,
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
                'komparasi_napi' => $komparasiNapi,
                'napi_berkurang_list' => $napiBerkurangList,
                'napi_bertambah_list' => $napiBertambahList,
                'napi_berkurang_text' => implode(', ', $napiBerkurangList),
                'napi_unexplained' => ($deltaNapi < 0) ? max(0, abs($deltaNapi) - $pengurangNapi) : 0,
            ];

            // Akumulasi ringkasan wilayah
            $summary['total_kapasitas'] += $kapasitasUpt;
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
            $summary['total_mutasi_keluar'] += $mutasiKeluar;
            $summary['total_bebas'] += $totalBebas;
            $summary['total_pengurang'] += $totalPengurang;
            $summary['estimasi_alih_status'] += $alihStatus;
            $summary['estimasi_tahanan_baru'] += $tahananBaruMasuk;
        }

        $summary['overcrowding'] = getOvercrowdingStatus(
            (int) $summary['total_penghuni_curr'],
            (int) $summary['total_kapasitas']
        );

        $allUptList = Upt::orderBy('nama_upt')->get();
        $instansi = Instansi::first();

        return view('fluktuasi.index', compact(
            'rows',
            'summary',
            'tanggal',
            'tanggalPembanding',
            'tglBebasMulai',
            'tglBebasSelesai',
            'filterUpt',
            'userUptFilter',
            'allUptList',
            'instansi'
        ));
    }
}
