<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\Upt;
use App\Services\PdfReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KroscekController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $userUptFilter = $user ? $user->getUserUptFilter() : null;
        $isOperator = ($userUptFilter !== null);

        $rawTanggal = $request->query('tanggal', date('Y-m-d'));
        try {
            $filterTanggal = Carbon::parse($rawTanggal)->format('Y-m-d');
        } catch (\Exception) {
            $filterTanggal = date('Y-m-d');
        }

        $instansi = Instansi::getFirst();
        $jamSelesaiStr = substr($instansi->jam_selesai ?? '11:00:00', 0, 8);
        $deadline = Carbon::parse($filterTanggal.' '.$jamSelesaiStr);

        $uptQuery = Upt::orderBy('nama_upt', 'asc');
        if ($isOperator) {
            $uptQuery->where('id', $userUptFilter);
        }
        $uptList = $uptQuery->get();
        $uptIds = $uptList->pluck('id')->toArray();

        // 1. Data Penghuni
        $penghuniRaw = DB::table('data_penghuni')
            ->select(
                'upt_id',
                DB::raw('MIN(created_at) as first_created'),
                DB::raw("SUM(CASE WHEN klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') THEN (tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) ELSE 0 END) as total_tahanan"),
                DB::raw('SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total_napi')
            )
            ->where('tanggal', $filterTanggal)
            ->whereIn('upt_id', $uptIds)
            ->groupBy('upt_id')
            ->get()
            ->keyBy('upt_id');

        // 2. Data Overstaying
        $overstayingRaw = DB::table('data_overstaying')
            ->select(
                'id', 'upt_id', 'created_at', 'updated_at',
                'tahanan_ai', 'tahanan_aii', 'tahanan_aiii', 'tahanan_aiv', 'tahanan_av',
                'overstaying_ai', 'overstaying_aii', 'overstaying_aiii', 'overstaying_aiv', 'overstaying_av',
                DB::raw('(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) as total_tahanan'),
                DB::raw('(overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av) as total_overstaying')
            )
            ->where('tanggal', $filterTanggal)
            ->whereIn('upt_id', $uptIds)
            ->get()
            ->keyBy('upt_id');

        // 3. Data Integrasi
        $integrasiRaw = DB::table('data_integrasi')
            ->select(
                'id', 'upt_id', 'created_at', 'updated_at',
                'usulan_pb', 'usulan_cb', 'usulan_cmb', 'usulan_cmk', 'usulan_asimilasi',
                'sk_terbit_pb', 'sk_terbit_cb', 'sk_terbit_cmb', 'sk_terbit_cmk', 'sk_terbit_asimilasi',
                DB::raw('(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi) as total_usulan'),
                DB::raw('(sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi) as total_sk')
            )
            ->where('tanggal', $filterTanggal)
            ->whereIn('upt_id', $uptIds)
            ->get()
            ->keyBy('upt_id');

        // 4. Data Bebas Harian
        $bebasRaw = DB::table('data_bebas_harian')
            ->select(
                'upt_id',
                DB::raw('MIN(created_at) as first_created'),
                DB::raw('SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya) as total_bebas'),
                DB::raw('SUM(bebas_murni) as bebas_murni'),
                DB::raw('SUM(pb) as pb'),
                DB::raw('SUM(cb) as cb'),
                DB::raw('SUM(cmb) as cmb'),
                DB::raw('SUM(asimilasi) as asimilasi'),
                DB::raw('SUM(bdh) as bdh'),
                DB::raw('SUM(lainnya) as lainnya')
            )
            ->where('tanggal', $filterTanggal)
            ->whereIn('upt_id', $uptIds)
            ->groupBy('upt_id')
            ->get()
            ->keyBy('upt_id');

        $kroscekList = [];
        $totalSinkron = 0;
        $totalSelisih = 0;
        $totalLengkap4 = 0;
        $totalBelumLengkap = 0;
        $totalPopulasiSeBanten = 0;
        $totalTahananSeBanten = 0;
        $totalNapiSeBanten = 0;
        $totalOverstayingSeBanten = 0;
        $totalBebasSeBanten = 0;
        $totalUsulanSeBanten = 0;

        foreach ($uptList as $upt) {
            $uId = $upt->id;

            // Modul 1: Penghuni
            $p = $penghuniRaw->get($uId);
            $pExists = (bool) $p;
            $pTahanan = $pExists ? (int) $p->total_tahanan : null;
            $pNapi = $pExists ? (int) $p->total_napi : 0;
            $pTotal = $pExists ? ($pTahanan + $pNapi) : 0;
            $pCreatedAt = ($pExists && ! empty($p->first_created)) ? Carbon::parse($p->first_created) : null;
            $pIsTepat = $pCreatedAt ? $pCreatedAt->lte($deadline) : false;

            // Modul 2: Overstaying
            $o = $overstayingRaw->get($uId);
            $oExists = (bool) $o;
            $oTahanan = $oExists ? (int) $o->total_tahanan : null;
            $oOverstaying = $oExists ? (int) $o->total_overstaying : 0;
            $oCreatedAt = ($oExists && ! empty($o->created_at)) ? Carbon::parse($o->created_at) : null;
            $oIsTepat = $oCreatedAt ? $oCreatedAt->lte($deadline) : false;

            // Modul 3: Integrasi
            $i = $integrasiRaw->get($uId);
            $iExists = (bool) $i;
            $iUsulan = $iExists ? (int) $i->total_usulan : 0;
            $iSk = $iExists ? (int) $i->total_sk : 0;
            $iCreatedAt = ($iExists && ! empty($i->created_at)) ? Carbon::parse($i->created_at) : null;
            $iIsTepat = $iCreatedAt ? $iCreatedAt->lte($deadline) : false;

            // Modul 4: Bebas Harian
            $b = $bebasRaw->get($uId);
            $bExists = (bool) $b;
            $bTotal = $bExists ? (int) $b->total_bebas : 0;
            $bCreatedAt = ($bExists && ! empty($b->first_created)) ? Carbon::parse($b->first_created) : null;
            $bIsTepat = $bCreatedAt ? $bCreatedAt->lte($deadline) : false;

            // Kroscek Tahanan (Penghuni vs Overstaying)
            $statusKroscek = 'belum_lengkap';
            $selisihTahanan = 0;

            if ($pTahanan !== null && $oTahanan !== null) {
                if ($pTahanan === $oTahanan) {
                    $statusKroscek = 'sinkron';
                    $totalSinkron++;
                } else {
                    $statusKroscek = 'selisih';
                    $selisihTahanan = abs($pTahanan - $oTahanan);
                    $totalSelisih++;
                }
            }

            // Hitung Kelengkapan 4 Modul
            $completedCount = ($pExists ? 1 : 0) + ($oExists ? 1 : 0) + ($iExists ? 1 : 0) + ($bExists ? 1 : 0);
            $isAllCompleted = ($completedCount === 4);

            if ($isAllCompleted) {
                $totalLengkap4++;
            } else {
                $totalBelumLengkap++;
            }

            // Agregat Se-Banten
            $totalPopulasiSeBanten += $pTotal;
            $totalTahananSeBanten += ($pTahanan ?? 0);
            $totalNapiSeBanten += $pNapi;
            $totalOverstayingSeBanten += $oOverstaying;
            $totalBebasSeBanten += $bTotal;
            $totalUsulanSeBanten += $iUsulan;

            $missingModules = [];
            if (! $pExists) {
                $missingModules[] = 'Penghuni';
            }
            if (! $oExists) {
                $missingModules[] = 'Overstaying';
            }
            if (! $iExists) {
                $missingModules[] = 'Integrasi';
            }
            if (! $bExists) {
                $missingModules[] = 'Bebas Harian';
            }

            $kroscekList[] = [
                'tanggal' => $filterTanggal,
                'tanggal_label' => Carbon::parse($filterTanggal)->translatedFormat('l, d F Y'),
                'upt_id' => $uId,
                'nama_upt' => $upt->nama_upt,
                'is_my_upt' => ($user && $user->upt_id == $uId),

                // Kroscek Tahanan
                'status_kroscek' => $statusKroscek,
                'tahanan_penghuni' => $pTahanan,
                'tahanan_overstaying' => $oTahanan,
                'selisih' => $selisihTahanan,

                // Kelengkapan Modul
                'completed_count' => $completedCount,
                'is_all_completed' => $isAllCompleted,
                'missing_modules' => $missingModules,

                // Detail Modul 1: Penghuni
                'penghuni' => [
                    'exists' => $pExists,
                    'total_tahanan' => $pTahanan,
                    'total_napi' => $pNapi,
                    'total_penghuni' => $pTotal,
                    'jam_str' => $pCreatedAt ? $pCreatedAt->format('H:i') : null,
                    'is_tepat' => $pIsTepat,
                ],

                // Detail Modul 2: Overstaying
                'overstaying' => [
                    'exists' => $oExists,
                    'total_tahanan' => $oTahanan,
                    'total_overstaying' => $oOverstaying,
                    'tahanan_ai' => $oExists ? (int) $o->tahanan_ai : 0,
                    'tahanan_aii' => $oExists ? (int) $o->tahanan_aii : 0,
                    'tahanan_aiii' => $oExists ? (int) $o->tahanan_aiii : 0,
                    'tahanan_aiv' => $oExists ? (int) $o->tahanan_aiv : 0,
                    'tahanan_av' => $oExists ? (int) $o->tahanan_av : 0,
                    'overstaying_ai' => $oExists ? (int) $o->overstaying_ai : 0,
                    'overstaying_aii' => $oExists ? (int) $o->overstaying_aii : 0,
                    'overstaying_aiii' => $oExists ? (int) $o->overstaying_aiii : 0,
                    'overstaying_aiv' => $oExists ? (int) $o->overstaying_aiv : 0,
                    'overstaying_av' => $oExists ? (int) $o->overstaying_av : 0,
                    'jam_str' => $oCreatedAt ? $oCreatedAt->format('H:i') : null,
                    'is_tepat' => $oIsTepat,
                ],

                // Detail Modul 3: Integrasi
                'integrasi' => [
                    'exists' => $iExists,
                    'total_usulan' => $iUsulan,
                    'total_sk' => $iSk,
                    'usulan_pb' => $iExists ? (int) $i->usulan_pb : 0,
                    'usulan_cb' => $iExists ? (int) $i->usulan_cb : 0,
                    'usulan_cmb' => $iExists ? (int) $i->usulan_cmb : 0,
                    'usulan_cmk' => $iExists ? (int) $i->usulan_cmk : 0,
                    'usulan_asimilasi' => $iExists ? (int) $i->usulan_asimilasi : 0,
                    'sk_pb' => $iExists ? (int) $i->sk_terbit_pb : 0,
                    'sk_cb' => $iExists ? (int) $i->sk_terbit_cb : 0,
                    'sk_cmb' => $iExists ? (int) $i->sk_terbit_cmb : 0,
                    'sk_cmk' => $iExists ? (int) $i->sk_terbit_cmk : 0,
                    'sk_asimilasi' => $iExists ? (int) $i->sk_terbit_asimilasi : 0,
                    'jam_str' => $iCreatedAt ? $iCreatedAt->format('H:i') : null,
                    'is_tepat' => $iIsTepat,
                ],

                // Detail Modul 4: Bebas Harian
                'bebas' => [
                    'exists' => $bExists,
                    'total_bebas' => $bTotal,
                    'bebas_murni' => $bExists ? (int) $b->bebas_murni : 0,
                    'pb' => $bExists ? (int) $b->pb : 0,
                    'cb' => $bExists ? (int) $b->cb : 0,
                    'cmb' => $bExists ? (int) $b->cmb : 0,
                    'asimilasi' => $bExists ? (int) $b->asimilasi : 0,
                    'bdh' => $bExists ? (int) $b->bdh : 0,
                    'lainnya' => $bExists ? (int) $b->lainnya : 0,
                    'jam_str' => $bCreatedAt ? $bCreatedAt->format('H:i') : null,
                    'is_tepat' => $bIsTepat,
                ],
            ];
        }

        $totalUpt = count($kroscekList);
        $myUptData = $isOperator ? ($kroscekList[0] ?? null) : null;

        // =========================================================================
        // DATA TABEL 2: DETAIL KELENGKAPAN PELAPORAN HARIAN PER TANGGAL (TIAP UPT)
        // =========================================================================
        if ($isOperator) {
            $selectedDetailUptId = $userUptFilter;
            $allUptList = Upt::where('id', $userUptFilter)->get();
            $selectedDetailUpt = $allUptList->first();
        } else {
            $allUptList = Upt::orderBy('nama_upt', 'asc')->get();
            $selectedDetailUptId = $request->query('detail_upt_id');
            if (! $selectedDetailUptId && $allUptList->isNotEmpty()) {
                $selectedDetailUptId = $allUptList->first()->id;
            }
            $selectedDetailUpt = $allUptList->firstWhere('id', $selectedDetailUptId) ?? $allUptList->first();
            $selectedDetailUptId = $selectedDetailUpt ? $selectedDetailUpt->id : null;
        }

        $rawTglMulai = $request->query('tanggal_mulai', date('Y-m-01'));
        $rawTglAkhir = $request->query('tanggal_akhir', date('Y-m-d'));
        try {
            $detailTanggalMulai = Carbon::parse($rawTglMulai)->format('Y-m-d');
        } catch (\Exception) {
            $detailTanggalMulai = date('Y-m-01');
        }
        try {
            $detailTanggalAkhir = Carbon::parse($rawTglAkhir)->format('Y-m-d');
        } catch (\Exception) {
            $detailTanggalAkhir = date('Y-m-d');
        }

        if ($detailTanggalMulai > $detailTanggalAkhir) {
            $temp = $detailTanggalMulai;
            $detailTanggalMulai = $detailTanggalAkhir;
            $detailTanggalAkhir = $temp;
        }

        $detailSort = strtolower($request->query('sort', 'desc')) === 'asc' ? 'asc' : 'desc';

        $activeTab = $request->query('tab', ($request->has('detail_upt_id') || $request->has('tanggal_mulai') || $request->has('tanggal_akhir') || $request->has('sort')) ? 'detail' : 'harian');

        $detailData = self::getDetailKroscekData((int) $selectedDetailUptId, $detailTanggalMulai, $detailTanggalAkhir, $detailSort);
        $detailKroscekList = $detailData['list'];
        $detailStats = $detailData['stats'];

        return view('kroscek.index', compact(
            'kroscekList',
            'filterTanggal',
            'isOperator',
            'myUptData',
            'totalUpt',
            'totalSinkron',
            'totalSelisih',
            'totalLengkap4',
            'totalBelumLengkap',
            'totalPopulasiSeBanten',
            'totalTahananSeBanten',
            'totalNapiSeBanten',
            'totalOverstayingSeBanten',
            'totalBebasSeBanten',
            'totalUsulanSeBanten',
            'jamSelesaiStr',
            'allUptList',
            'selectedDetailUptId',
            'selectedDetailUpt',
            'detailTanggalMulai',
            'detailTanggalAkhir',
            'detailSort',
            'detailKroscekList',
            'detailStats',
            'activeTab'
        ));
    }

    /**
     * Cetak atau Simpan Dokumen PDF Kelengkapan Pelaporan & Kroscek Harian per Tanggal (Detil Tiap UPT)
     */
    public function detailPdf(Request $request)
    {
        $user = auth()->user();
        $userUptFilter = $user ? $user->getUserUptFilter() : null;
        $isOperator = ($userUptFilter !== null);

        if ($isOperator) {
            $selectedDetailUptId = (int) $userUptFilter;
        } else {
            $selectedDetailUptId = (int) $request->query('detail_upt_id');
            if (! $selectedDetailUptId) {
                $firstUpt = Upt::orderBy('nama_upt', 'asc')->first();
                $selectedDetailUptId = $firstUpt ? $firstUpt->id : 0;
            }
        }

        $rawTglMulai = $request->query('tanggal_mulai', date('Y-m-01'));
        $rawTglAkhir = $request->query('tanggal_akhir', date('Y-m-d'));
        try {
            $detailTanggalMulai = Carbon::parse($rawTglMulai)->format('Y-m-d');
        } catch (\Exception) {
            $detailTanggalMulai = date('Y-m-01');
        }
        try {
            $detailTanggalAkhir = Carbon::parse($rawTglAkhir)->format('Y-m-d');
        } catch (\Exception) {
            $detailTanggalAkhir = date('Y-m-d');
        }

        if ($detailTanggalMulai > $detailTanggalAkhir) {
            $temp = $detailTanggalMulai;
            $detailTanggalMulai = $detailTanggalAkhir;
            $detailTanggalAkhir = $temp;
        }

        $sort = strtolower($request->query('sort', 'desc')) === 'asc' ? 'asc' : 'desc';

        $data = self::getDetailKroscekData($selectedDetailUptId, $detailTanggalMulai, $detailTanggalAkhir, $sort);

        $signData = [
            'is_upt' => true,
            'tempat_ttd' => $request->query('tempat_ttd', 'Serang'),
            'jabatan_ttd' => $request->query('jabatan_ttd', 'Kepala Bidang Pelayanan dan Pembinaan'),
            'nama_ttd' => $request->query('nama_ttd', 'Surya Dharma'),
            'show_ttd' => false,
            'show_ttd_image' => false,
            'show_stempel' => false,
            'show_qrcode' => $request->query('show_qrcode', '1') === '1',
        ];

        return PdfReportService::generateKroscekDetail($data, $signData);
    }

    /**
     * Ambil data lengkap kelengkapan pelaporan & kroscek harian untuk UPT dan rentang tanggal tertentu.
     * Digunakan bersama oleh tampilan web Tab 2 dan Cetak PDF.
     */
    public static function getDetailKroscekData(
        int $selectedDetailUptId,
        string $detailTanggalMulai,
        string $detailTanggalAkhir,
        string $sort = 'desc'
    ): array {
        $selectedDetailUpt = Upt::find($selectedDetailUptId);
        $instansi = Instansi::getFirst();
        $jamSelesaiStr = substr($instansi->jam_selesai ?? '11:00:00', 0, 8);

        $detailKroscekList = [];
        $detailSinkronCount = 0;
        $detailSelisihCount = 0;
        $detailLengkap4Count = 0;
        $detailBelumLengkapCount = 0;

        if ($selectedDetailUpt) {
            // 1. Data Penghuni per Tanggal untuk UPT Terpilih
            $detailPenghuniRaw = DB::table('data_penghuni')
                ->select(
                    'tanggal',
                    DB::raw('MIN(created_at) as first_created'),
                    DB::raw("SUM(CASE WHEN klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') THEN (tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) ELSE 0 END) as total_tahanan"),
                    DB::raw('SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total_napi')
                )
                ->where('upt_id', $selectedDetailUptId)
                ->whereBetween('tanggal', [$detailTanggalMulai, $detailTanggalAkhir])
                ->groupBy('tanggal')
                ->get()
                ->keyBy('tanggal');

            // 2. Data Overstaying per Tanggal untuk UPT Terpilih
            $detailOverstayingRaw = DB::table('data_overstaying')
                ->select(
                    'id', 'tanggal', 'created_at', 'updated_at',
                    'tahanan_ai', 'tahanan_aii', 'tahanan_aiii', 'tahanan_aiv', 'tahanan_av',
                    'overstaying_ai', 'overstaying_aii', 'overstaying_aiii', 'overstaying_aiv', 'overstaying_av',
                    DB::raw('(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) as total_tahanan'),
                    DB::raw('(overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av) as total_overstaying')
                )
                ->where('upt_id', $selectedDetailUptId)
                ->whereBetween('tanggal', [$detailTanggalMulai, $detailTanggalAkhir])
                ->get()
                ->keyBy('tanggal');

            // 3. Data Integrasi per Tanggal untuk UPT Terpilih
            $detailIntegrasiRaw = DB::table('data_integrasi')
                ->select(
                    'id', 'tanggal', 'created_at', 'updated_at',
                    'usulan_pb', 'usulan_cb', 'usulan_cmb', 'usulan_cmk', 'usulan_asimilasi',
                    'sk_terbit_pb', 'sk_terbit_cb', 'sk_terbit_cmb', 'sk_terbit_cmk', 'sk_terbit_asimilasi',
                    DB::raw('(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi) as total_usulan'),
                    DB::raw('(sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi) as total_sk')
                )
                ->where('upt_id', $selectedDetailUptId)
                ->whereBetween('tanggal', [$detailTanggalMulai, $detailTanggalAkhir])
                ->get()
                ->keyBy('tanggal');

            // 4. Data Bebas Harian per Tanggal untuk UPT Terpilih
            $detailBebasRaw = DB::table('data_bebas_harian')
                ->select(
                    'tanggal',
                    DB::raw('MIN(created_at) as first_created'),
                    DB::raw('SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya) as total_bebas'),
                    DB::raw('SUM(bebas_murni) as bebas_murni'),
                    DB::raw('SUM(pb) as pb'),
                    DB::raw('SUM(cb) as cb'),
                    DB::raw('SUM(cmb) as cmb'),
                    DB::raw('SUM(asimilasi) as asimilasi'),
                    DB::raw('SUM(bdh) as bdh'),
                    DB::raw('SUM(lainnya) as lainnya')
                )
                ->where('upt_id', $selectedDetailUptId)
                ->whereBetween('tanggal', [$detailTanggalMulai, $detailTanggalAkhir])
                ->groupBy('tanggal')
                ->get()
                ->keyBy('tanggal');

            $startDate = Carbon::parse($detailTanggalMulai)->startOfDay();
            $endDate = Carbon::parse($detailTanggalAkhir)->startOfDay();

            if ($sort === 'asc') {
                $loopCondition = fn ($date) => $date->lte($endDate);
                $stepAction = fn ($date) => $date->addDay();
                $initialDate = $startDate->copy();
            } else {
                $loopCondition = fn ($date) => $date->gte($startDate);
                $stepAction = fn ($date) => $date->subDay();
                $initialDate = $endDate->copy();
            }

            for ($date = $initialDate; $loopCondition($date); $stepAction($date)) {
                $tgl = $date->format('Y-m-d');
                $tglDeadline = Carbon::parse($tgl.' '.$jamSelesaiStr);

                // Modul 1: Penghuni
                $p = $detailPenghuniRaw->get($tgl);
                $pExists = (bool) $p;
                $pTahanan = $pExists ? (int) $p->total_tahanan : null;
                $pNapi = $pExists ? (int) $p->total_napi : 0;
                $pTotal = $pExists ? ($pTahanan + $pNapi) : 0;
                $pCreatedAt = ($pExists && ! empty($p->first_created)) ? Carbon::parse($p->first_created) : null;
                $pIsTepat = $pCreatedAt ? $pCreatedAt->lte($tglDeadline) : false;

                // Modul 2: Overstaying
                $o = $detailOverstayingRaw->get($tgl);
                $oExists = (bool) $o;
                $oTahanan = $oExists ? (int) $o->total_tahanan : null;
                $oOverstaying = $oExists ? (int) $o->total_overstaying : 0;
                $oCreatedAt = ($oExists && ! empty($o->created_at)) ? Carbon::parse($o->created_at) : null;
                $oIsTepat = $oCreatedAt ? $oCreatedAt->lte($tglDeadline) : false;

                // Modul 3: Integrasi
                $i = $detailIntegrasiRaw->get($tgl);
                $iExists = (bool) $i;
                $iUsulan = $iExists ? (int) $i->total_usulan : 0;
                $iSk = $iExists ? (int) $i->total_sk : 0;
                $iCreatedAt = ($iExists && ! empty($i->created_at)) ? Carbon::parse($i->created_at) : null;
                $iIsTepat = $iCreatedAt ? $iCreatedAt->lte($tglDeadline) : false;

                // Modul 4: Bebas Harian
                $b = $detailBebasRaw->get($tgl);
                $bExists = (bool) $b;
                $bTotal = $bExists ? (int) $b->total_bebas : 0;
                $bCreatedAt = ($bExists && ! empty($b->first_created)) ? Carbon::parse($b->first_created) : null;
                $bIsTepat = $bCreatedAt ? $bCreatedAt->lte($tglDeadline) : false;

                // Kroscek Tahanan
                $statusKroscek = 'belum_lengkap';
                $selisihTahanan = 0;

                if ($pTahanan !== null && $oTahanan !== null) {
                    if ($pTahanan === $oTahanan) {
                        $statusKroscek = 'sinkron';
                        $detailSinkronCount++;
                    } else {
                        $statusKroscek = 'selisih';
                        $selisihTahanan = abs($pTahanan - $oTahanan);
                        $detailSelisihCount++;
                    }
                }

                $completedCount = ($pExists ? 1 : 0) + ($oExists ? 1 : 0) + ($iExists ? 1 : 0) + ($bExists ? 1 : 0);
                $isAllCompleted = ($completedCount === 4);

                if ($isAllCompleted) {
                    $detailLengkap4Count++;
                } else {
                    $detailBelumLengkapCount++;
                }

                $missingModules = [];
                if (! $pExists) {
                    $missingModules[] = 'Penghuni';
                }
                if (! $oExists) {
                    $missingModules[] = 'Overstaying';
                }
                if (! $iExists) {
                    $missingModules[] = 'Integrasi';
                }
                if (! $bExists) {
                    $missingModules[] = 'Bebas Harian';
                }

                $detailKroscekList[] = [
                    'tanggal' => $tgl,
                    'tanggal_formatted' => $date->translatedFormat('d/m/Y'),
                    'hari' => $date->translatedFormat('l'),
                    'tanggal_label' => $date->translatedFormat('l, d F Y'),
                    'upt_id' => $selectedDetailUptId,
                    'nama_upt' => $selectedDetailUpt->nama_upt,

                    // Kroscek Tahanan
                    'status_kroscek' => $statusKroscek,
                    'tahanan_penghuni' => $pTahanan,
                    'tahanan_overstaying' => $oTahanan,
                    'selisih' => $selisihTahanan,

                    // Kelengkapan Modul
                    'completed_count' => $completedCount,
                    'is_all_completed' => $isAllCompleted,
                    'missing_modules' => $missingModules,

                    // Detail Modul 1: Penghuni
                    'penghuni' => [
                        'exists' => $pExists,
                        'total_tahanan' => $pTahanan,
                        'total_napi' => $pNapi,
                        'total_penghuni' => $pTotal,
                        'jam_str' => $pCreatedAt ? $pCreatedAt->format('H:i') : null,
                        'is_tepat' => $pIsTepat,
                    ],

                    // Detail Modul 2: Overstaying
                    'overstaying' => [
                        'exists' => $oExists,
                        'total_tahanan' => $oTahanan,
                        'total_overstaying' => $oOverstaying,
                        'tahanan_ai' => $oExists ? (int) $o->tahanan_ai : 0,
                        'tahanan_aii' => $oExists ? (int) $o->tahanan_aii : 0,
                        'tahanan_aiii' => $oExists ? (int) $o->tahanan_aiii : 0,
                        'tahanan_aiv' => $oExists ? (int) $o->tahanan_aiv : 0,
                        'tahanan_av' => $oExists ? (int) $o->tahanan_av : 0,
                        'overstaying_ai' => $oExists ? (int) $o->overstaying_ai : 0,
                        'overstaying_aii' => $oExists ? (int) $o->overstaying_aii : 0,
                        'overstaying_aiii' => $oExists ? (int) $o->overstaying_aiii : 0,
                        'overstaying_aiv' => $oExists ? (int) $o->overstaying_aiv : 0,
                        'overstaying_av' => $oExists ? (int) $o->overstaying_av : 0,
                        'jam_str' => $oCreatedAt ? $oCreatedAt->format('H:i') : null,
                        'is_tepat' => $oIsTepat,
                    ],

                    // Detail Modul 3: Integrasi
                    'integrasi' => [
                        'exists' => $iExists,
                        'total_usulan' => $iUsulan,
                        'total_sk' => $iSk,
                        'usulan_pb' => $iExists ? (int) $i->usulan_pb : 0,
                        'usulan_cb' => $iExists ? (int) $i->usulan_cb : 0,
                        'usulan_cmb' => $iExists ? (int) $i->usulan_cmb : 0,
                        'usulan_cmk' => $iExists ? (int) $i->usulan_cmk : 0,
                        'usulan_asimilasi' => $iExists ? (int) $i->usulan_asimilasi : 0,
                        'sk_pb' => $iExists ? (int) $i->sk_terbit_pb : 0,
                        'sk_cb' => $iExists ? (int) $i->sk_terbit_cb : 0,
                        'sk_cmb' => $iExists ? (int) $i->sk_terbit_cmb : 0,
                        'sk_cmk' => $iExists ? (int) $i->sk_terbit_cmk : 0,
                        'sk_asimilasi' => $iExists ? (int) $i->sk_terbit_asimilasi : 0,
                        'jam_str' => $iCreatedAt ? $iCreatedAt->format('H:i') : null,
                        'is_tepat' => $iIsTepat,
                    ],

                    // Detail Modul 4: Bebas Harian
                    'bebas' => [
                        'exists' => $bExists,
                        'total_bebas' => $bTotal,
                        'bebas_murni' => $bExists ? (int) $b->bebas_murni : 0,
                        'pb' => $bExists ? (int) $b->pb : 0,
                        'cb' => $bExists ? (int) $b->cb : 0,
                        'cmb' => $bExists ? (int) $b->cmb : 0,
                        'asimilasi' => $bExists ? (int) $b->asimilasi : 0,
                        'bdh' => $bExists ? (int) $b->bdh : 0,
                        'lainnya' => $bExists ? (int) $b->lainnya : 0,
                        'jam_str' => $bCreatedAt ? $bCreatedAt->format('H:i') : null,
                        'is_tepat' => $bIsTepat,
                    ],
                ];
            }
        }

        $detailTotalDays = count($detailKroscekList);
        $detailStats = [
            'total_hari' => $detailTotalDays,
            'sinkron' => $detailSinkronCount,
            'selisih' => $detailSelisihCount,
            'lengkap_4' => $detailLengkap4Count,
            'belum_lengkap' => $detailBelumLengkapCount,
            'pct_sinkron' => $detailTotalDays > 0 ? round(($detailSinkronCount / $detailTotalDays) * 100) : 0,
            'pct_lengkap' => $detailTotalDays > 0 ? round(($detailLengkap4Count / $detailTotalDays) * 100) : 0,
        ];

        return [
            'upt' => $selectedDetailUpt,
            'upt_id' => $selectedDetailUptId,
            'tanggal_mulai' => $detailTanggalMulai,
            'tanggal_akhir' => $detailTanggalAkhir,
            'sort' => $sort,
            'jam_selesai_str' => $jamSelesaiStr,
            'list' => $detailKroscekList,
            'stats' => $detailStats,
        ];
    }
}
