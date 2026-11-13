<?php

namespace App\Services;

use App\Models\Instansi;
use Carbon\Carbon;
use InvalidArgumentException;

class TelramCalculatorService
{
    /**
     * Hitung Telram Mode Maju (Cari Tanggal Ekspirasi dari Tanggal Penahanan & Lama Vonis)
     *
     * @return array<string, mixed>
     */
    public function hitungMaju(
        string $tglAwalStr,
        int $tahun,
        int $bulan,
        int $hari,
        int $subTahun = 0,
        int $subBulan = 0,
        int $subHari = 0
    ): array {
        $tglAwal = Carbon::parse($tglAwalStr)->startOfDay();

        if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
            throw new InvalidArgumentException('Lama pidana vonis harus lebih dari 0.');
        }

        // Aturan Perhitungan Ekspirasi & Pentahapan Masa Pidana:
        // 1. Dimulai dari tahun: jika ada tahun, tambah tahun di tanggal & bulan yang sama (addYearsNoOverflow)
        // 2. Kemudian geser bulan dan hari: 1 bulan = 30 hari kalender aktual
        $tglEkspirasi = self::tambahDurasiVonis($tglAwal, $tahun, $bulan, $hari);

        // Pentahapan Masa Pidana Murni: Proporsional dari pidana vonis (Tahun, Bulan, Hari)
        // Dihitung dari tanggal awal penahanan dengan kaidah hitung vonis yang sama
        [$y13, $m13, $d13] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 3);
        [$y12, $m12, $d12] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 2);
        [$y23, $m23, $d23] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 2, 3);

        $tgl13 = self::tambahDurasiVonis($tglAwal, $y13, $m13, $d13);
        $tgl12 = self::tambahDurasiVonis($tglAwal, $y12, $m12, $d12);
        $tgl23 = self::tambahDurasiVonis($tglAwal, $y23, $m23, $d23);

        // Total hari baku: 1 Tahun = 360 hari (12 bln x 30 hari), 1 Bulan = 30 hari
        $totalHariVonis = ($tahun * 360) + ($bulan * 30) + $hari;

        $formatVonis = [];
        if ($tahun > 0) {
            $formatVonis[] = "{$tahun} Tahun";
        }
        if ($bulan > 0) {
            $formatVonis[] = "{$bulan} Bulan";
        }
        if ($hari > 0) {
            $formatVonis[] = "{$hari} Hari";
        }

        $subTahun = max(0, $subTahun);
        $subBulan = max(0, $subBulan);
        $subHari = max(0, $subHari);
        $hasSubsider = ($subTahun > 0 || $subBulan > 0 || $subHari > 0);
        $subsiderTeks = self::formatDurasiTeks($subTahun, $subBulan, $subHari);
        $totalHariSubsider = ($subTahun * 360) + ($subBulan * 30) + $subHari;
        $tglEkspirasiSubsider = $hasSubsider
            ? self::tambahDurasiVonis($tglEkspirasi, $subTahun, $subBulan, $subHari)
            : null;

        return [
            'mode' => 'maju',
            'tgl_awal' => $tglAwal->toDateString(),
            'tgl_ekspirasi' => $tglEkspirasi->toDateString(),
            'tgl_1_3' => $tgl13->toDateString(),
            'tgl_1_2' => $tgl12->toDateString(),
            'tgl_2_3' => $tgl23->toDateString(),
            'lama_pidana_teks' => implode(', ', $formatVonis) ?: '0 Hari',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'hari' => $hari,
            'total_hari_vonis' => $totalHariVonis,
            'has_subsider' => $hasSubsider,
            'sub_tahun' => $subTahun,
            'sub_bulan' => $subBulan,
            'sub_hari' => $subHari,
            'subsider_teks' => $subsiderTeks,
            'total_hari_subsider' => $totalHariSubsider,
            'tgl_ekspirasi_subsider' => $tglEkspirasiSubsider?->toDateString(),
            'formatted' => [
                'tgl_awal' => self::formatTanggalIndonesia($tglAwal),
                'tgl_ekspirasi' => self::formatTanggalIndonesia($tglEkspirasi),
                'tgl_ekspirasi_subsider' => $tglEkspirasiSubsider ? self::formatTanggalIndonesia($tglEkspirasiSubsider) : '-',
                'subsider_teks' => $subsiderTeks,
                'tgl_1_3' => self::formatTanggalIndonesia($tgl13),
                'tgl_1_2' => self::formatTanggalIndonesia($tgl12),
                'tgl_2_3' => self::formatTanggalIndonesia($tgl23),
            ],
        ];
    }

    /**
     * Hitung Telram Mode Mundur (Cari Lama Pidana dari Tanggal Penahanan & Tanggal Ekspirasi)
     *
     * @return array<string, mixed>
     */
    public function hitungMundur(string $tglAwalStr, string $tglEkspirasiStr): array
    {
        $tglAwal = Carbon::parse($tglAwalStr)->startOfDay();
        $tglEkspirasi = Carbon::parse($tglEkspirasiStr)->startOfDay();

        if ($tglEkspirasi->lt($tglAwal)) {
            throw new InvalidArgumentException('Tanggal ekspirasi tidak boleh mendahului tanggal penahanan.');
        }

        // 1. Tahun: Cari berapa tahun penuh yang bisa ditambahkan dari tglAwal di tanggal & bulan yang sama
        $vThn = 0;
        while ($tglAwal->copy()->addYearsNoOverflow($vThn + 1)->lte($tglEkspirasi)) {
            $vThn++;
        }
        $curr = $tglAwal->copy()->addYearsNoOverflow($vThn);

        // 2. Sisa Hari dikonversi ke bulan (1 bulan = 30 hari) dan hari
        $diffDays = (int) $curr->diffInDays($tglEkspirasi);
        $vBln = (int) floor($diffDays / 30);
        $vHri = (int) ($diffDays % 30);

        // Pentahapan Masa Pidana Murni: Proporsional dari pidana vonis (Tahun, Bulan, Hari)
        [$y13, $m13, $d13] = self::hitungDurasiFraksi($vThn, $vBln, $vHri, 1, 3);
        [$y12, $m12, $d12] = self::hitungDurasiFraksi($vThn, $vBln, $vHri, 1, 2);
        [$y23, $m23, $d23] = self::hitungDurasiFraksi($vThn, $vBln, $vHri, 2, 3);

        $tgl13 = self::tambahDurasiVonis($tglAwal, $y13, $m13, $d13);
        $tgl12 = self::tambahDurasiVonis($tglAwal, $y12, $m12, $d12);
        $tgl23 = self::tambahDurasiVonis($tglAwal, $y23, $m23, $d23);

        $totalHariVonis = ($vThn * 360) + ($vBln * 30) + $vHri;

        $formatVonis = [];
        if ($vThn > 0) {
            $formatVonis[] = "{$vThn} Tahun";
        }
        if ($vBln > 0) {
            $formatVonis[] = "{$vBln} Bulan";
        }
        if ($vHri > 0) {
            $formatVonis[] = "{$vHri} Hari";
        }

        return [
            'mode' => 'mundur',
            'tgl_awal' => $tglAwal->toDateString(),
            'tgl_ekspirasi' => $tglEkspirasi->toDateString(),
            'tgl_1_3' => $tgl13->toDateString(),
            'tgl_1_2' => $tgl12->toDateString(),
            'tgl_2_3' => $tgl23->toDateString(),
            'lama_pidana_teks' => implode(', ', $formatVonis) ?: '0 Hari',
            'tahun' => $vThn,
            'bulan' => $vBln,
            'hari' => $vHri,
            'total_hari_vonis' => $totalHariVonis,
            'formatted' => [
                'tgl_awal' => self::formatTanggalIndonesia($tglAwal),
                'tgl_ekspirasi' => self::formatTanggalIndonesia($tglEkspirasi),
                'tgl_1_3' => self::formatTanggalIndonesia($tgl13),
                'tgl_1_2' => self::formatTanggalIndonesia($tgl12),
                'tgl_2_3' => self::formatTanggalIndonesia($tgl23),
            ],
        ];
    }

    /**
     * Hitung Sisa Pidana (Cari Sisa Durasi dari Tanggal Perhitungan ke Tanggal Ekspirasi)
     *
     * @return array<string, mixed>
     */
    public function hitungSisaPidana(string $tglPerhitunganStr, string $tglEkspirasiStr): array
    {
        $tglPerhitungan = Carbon::parse($tglPerhitunganStr)->startOfDay();
        $tglEkspirasi = Carbon::parse($tglEkspirasiStr)->startOfDay();

        if ($tglEkspirasi->lt($tglPerhitungan)) {
            throw new InvalidArgumentException('Tanggal ekspirasi tidak boleh mendahului tanggal perhitungan.');
        }

        // 1. Tahun: Cari berapa tahun penuh yang bisa ditambahkan dari tglPerhitungan
        $vThn = 0;
        while ($tglPerhitungan->copy()->addYearsNoOverflow($vThn + 1)->lte($tglEkspirasi)) {
            $vThn++;
        }
        $curr = $tglPerhitungan->copy()->addYearsNoOverflow($vThn);

        // 2. Sisa Hari dikonversi ke bulan (1 bulan = 30 hari) dan hari
        $diffDays = (int) $curr->diffInDays($tglEkspirasi);
        $vBln = (int) floor($diffDays / 30);
        $vHri = (int) ($diffDays % 30);

        $totalHariSisa = (int) $tglPerhitungan->diffInDays($tglEkspirasi);

        $formatSisa = [];
        if ($vThn > 0) {
            $formatSisa[] = "{$vThn} Tahun";
        }
        if ($vBln > 0) {
            $formatSisa[] = "{$vBln} Bulan";
        }
        if ($vHri > 0) {
            $formatSisa[] = "{$vHri} Hari";
        }

        return [
            'mode' => 'sisa',
            'tgl_perhitungan' => $tglPerhitungan->toDateString(),
            'tgl_ekspirasi' => $tglEkspirasi->toDateString(),
            'sisa_pidana_teks' => implode(', ', $formatSisa) ?: '0 Hari (Bebas Hari Ini)',
            'tahun' => $vThn,
            'bulan' => $vBln,
            'hari' => $vHri,
            'total_hari_sisa' => $totalHariSisa,
            'formatted' => [
                'tgl_perhitungan' => self::formatTanggalIndonesia($tglPerhitungan),
                'tgl_ekspirasi' => self::formatTanggalIndonesia($tglEkspirasi),
            ],
        ];
    }

    /**
     * Format Carbon date ke Bahasa Indonesia baku (contoh: 17 Agustus 2026)
     */
    public static function formatTanggalIndonesia(Carbon $date): string
    {
        $bulanIndo = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return sprintf('%02d %s %d', $date->day, $bulanIndo[$date->month] ?? '', $date->year);
    }

    /**
     * Format tanggal numerik baku untuk rumus matematika (dd/mm/yyyy)
     */
    public static function formatTanggalNumeric(mixed $date): string
    {
        if (empty($date)) {
            return '-';
        }

        if ($date instanceof Carbon) {
            return $date->format('d/m/Y');
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    }

    /**
     * Hitung riwayat penahanan multi-periode & hari di luar UPT (Rutan, Rumah, Kota, Penangguhan, Pembantaran).
     * Sesuai Pasal 22 & 31 KUHAP serta SEMA No. 1/1989.
     * - Untuk segmen PENAHANAN (Lapas/Rutan, Rumah, Kota, Masuk Kembali): menggunakan rumus INKLUSIF ((T2 - T1) + 1)
     *   dan asas yang lebih menguntungkan tahanan (favor reo / ceil) untuk pecahan 1/3 dan 1/5.
     * - Untuk segmen HARI DI LUAR UPT (Penangguhan, Pembantaran): menggunakan rumus EKSKLUSIF Opsi A (T2 - T1, tanpa +1)
     *   serta mendeteksi otomatis apakah terjadi Pra-Putusan (jeda 0 potah) atau Pasca-Putusan (menggeser ekspirasi).
     *
     * @param  array<int, array<string, mixed>>  $riwayat
     * @return array<string, mixed>
     */
    public function hitungRiwayatPenahanan(array $riwayat, ?Carbon $tglPutusan = null): array
    {
        $items = [];
        $totalKalender = 0;
        $totalKalenderPenahanan = 0;
        $totalPotah = 0;
        $totalJeda = 0;
        $totalLuarUptPra = 0;
        $totalLuarUptPasca = 0;
        $luarUptPascaIntervals = [];

        $cleanRiwayat = [];
        foreach ($riwayat as $r) {
            if (! is_array($r) || empty($r['tgl_mulai']) || empty($r['tgl_selesai'])) {
                continue;
            }
            $cleanRiwayat[] = $r;
        }

        if (empty($cleanRiwayat)) {
            return [
                'items' => [],
                'total_hari_kalender' => 0,
                'total_hari_penahanan_kalender' => 0,
                'total_hari_potah' => 0,
                'total_hari_jeda' => 0,
                'total_hari_luar_upt' => 0,
                'total_hari_luar_upt_pra' => 0,
                'total_hari_luar_upt_pasca' => 0,
                'luar_upt_pasca_intervals' => [],
                'tgl_awal_fisik' => Carbon::today(),
                'tgl_awal_efektif' => Carbon::today(),
                'tgl_putusan' => $tglPutusan,
            ];
        }

        usort($cleanRiwayat, static function ($a, $b) {
            return strcmp((string) $a['tgl_mulai'], (string) $b['tgl_mulai']);
        });

        $earliestStart = null;
        $latestEnd = null;
        $latestPenahananEnd = null;

        // Pass 1 untuk menentukan batas tanggal penahanan bila tglPutusan tidak diisi eksplisit
        foreach ($cleanRiwayat as $row) {
            $m = Carbon::parse($row['tgl_mulai'])->startOfDay();
            $s = Carbon::parse($row['tgl_selesai'])->startOfDay();
            if ($s->lt($m)) {
                [$m, $s] = [$s, $m];
            }
            if ($earliestStart === null || $m->lt($earliestStart)) {
                $earliestStart = $m->copy();
            }
            if ($latestEnd === null || $s->gt($latestEnd)) {
                $latestEnd = $s->copy();
            }
            $j = strtolower(trim((string) ($row['jenis'] ?? 'rutan')));
            if (! in_array($j, ['tangguh', 'bantar', 'luar_upt'], true)) {
                if ($latestPenahananEnd === null || $s->gt($latestPenahananEnd)) {
                    $latestPenahananEnd = $s->copy();
                }
            }
        }

        if ($tglPutusan === null) {
            $tglPutusan = $latestPenahananEnd ? $latestPenahananEnd->copy() : ($latestEnd?->copy());
        }

        foreach ($cleanRiwayat as $idx => $row) {
            $mulai = Carbon::parse($row['tgl_mulai'])->startOfDay();
            $selesai = Carbon::parse($row['tgl_selesai'])->startOfDay();

            if ($selesai->lt($mulai)) {
                $temp = $mulai;
                $mulai = $selesai;
                $selesai = $temp;
            }

            $jenis = strtolower(trim((string) ($row['jenis'] ?? 'rutan')));
            $keterangan = trim((string) ($row['keterangan'] ?? ''));

            $isLuarUpt = in_array($jenis, ['tangguh', 'bantar', 'luar_upt'], true);

            // Penahanan dihitung Inklusif ((T2 - T1) + 1), sedangkan Penangguhan/Pembantaran/Hari di Luar UPT
            // dihitung Eksklusif Opsi A (T2 - T1, tanpa +1).
            $hariKalender = $isLuarUpt
                ? (int) $mulai->diffInDays($selesai)
                : (int) $mulai->diffInDays($selesai) + 1;
            $metodeHitung = $isLuarUpt ? 'Eksklusif (T2-T1)' : 'Inklusif (T2-T1+1)';

            switch ($jenis) {
                case 'rumah':
                    $labelJenis = 'Tahanan Rumah';
                    $rasio = '1/3';
                    $dasarHukum = 'Ps. 22 ayat (5) huruf a KUHAP';
                    // Asas favor reo: pembulatan ke atas (ceil)
                    $hariPotah = (int) ceil($hariKalender / 3);
                    $isJeda = false;
                    break;

                case 'kota':
                    $labelJenis = 'Tahanan Kota';
                    $rasio = '1/5';
                    $dasarHukum = 'Ps. 22 ayat (5) huruf b KUHAP';
                    // Asas favor reo: pembulatan ke atas (ceil)
                    $hariPotah = (int) ceil($hariKalender / 5);
                    $isJeda = false;
                    break;

                case 'tangguh':
                    $labelJenis = 'Penangguhan';
                    $rasio = '0';
                    $dasarHukum = 'Ps. 31 KUHAP';
                    $hariPotah = 0;
                    $isJeda = true;
                    break;

                case 'bantar':
                    $labelJenis = 'Pembantaran';
                    $rasio = '0';
                    $dasarHukum = 'SEMA No. 1/1989';
                    $hariPotah = 0;
                    $isJeda = true;
                    break;

                case 'luar_upt':
                    $labelJenis = 'Hari di Luar UPT';
                    $rasio = '0';
                    $dasarHukum = 'Di Luar Masa Pidana UPT';
                    $hariPotah = 0;
                    $isJeda = true;
                    break;

                case 'masuk_kembali':
                    $labelJenis = 'Masuk Kembali';
                    $rasio = '1/1';
                    $dasarHukum = 'Ps. 22 ayat (4) KUHAP';
                    $hariPotah = $hariKalender;
                    $isJeda = false;
                    break;

                case 'rutan':
                default:
                    $jenis = 'rutan';
                    $labelJenis = 'Tahanan Lapas/Rutan';
                    $rasio = '1/1';
                    $dasarHukum = 'Ps. 22 ayat (4) KUHAP';
                    $hariPotah = $hariKalender;
                    $isJeda = false;
                    break;
            }

            $fase = 'pra_putusan';
            $hariLuarUptPra = 0;
            $hariLuarUptPasca = 0;

            if ($isLuarUpt && $hariKalender > 0) {
                if ($tglPutusan !== null && $mulai->gte($tglPutusan)) {
                    $fase = 'pasca_putusan';
                    $hariLuarUptPasca = $hariKalender;
                    $luarUptPascaIntervals[] = [
                        'mulai' => $mulai->copy(),
                        'selesai' => $selesai->copy(),
                        'hari' => $hariLuarUptPasca,
                    ];
                } elseif ($tglPutusan !== null && $mulai->lt($tglPutusan) && $selesai->gt($tglPutusan)) {
                    $fase = 'lintas_putusan';
                    $hariLuarUptPra = (int) $mulai->diffInDays($tglPutusan);
                    $hariLuarUptPasca = max(0, $hariKalender - $hariLuarUptPra);
                    if ($hariLuarUptPasca > 0) {
                        $luarUptPascaIntervals[] = [
                            'mulai' => $tglPutusan->copy(),
                            'selesai' => $selesai->copy(),
                            'hari' => $hariLuarUptPasca,
                        ];
                    }
                } else {
                    $fase = 'pra_putusan';
                    $hariLuarUptPra = $hariKalender;
                }
            }

            $totalKalender += $hariKalender;
            $totalPotah += $hariPotah;
            if ($isJeda) {
                $totalJeda += $hariKalender;
                $totalLuarUptPra += $hariLuarUptPra;
                $totalLuarUptPasca += $hariLuarUptPasca;
            } else {
                $totalKalenderPenahanan += $hariKalender;
            }

            $items[] = [
                'no' => $idx + 1,
                'jenis' => $jenis,
                'label_jenis' => $labelJenis,
                'rasio' => $rasio,
                'dasar_hukum' => $dasarHukum,
                'metode_hitung' => $metodeHitung,
                'is_luar_upt' => $isLuarUpt,
                'fase' => $fase,
                'hari_luar_upt_pra' => $hariLuarUptPra,
                'hari_luar_upt_pasca' => $hariLuarUptPasca,
                'tgl_mulai' => $mulai->toDateString(),
                'tgl_selesai' => $selesai->toDateString(),
                'tgl_mulai_indo' => self::formatTanggalIndonesia($mulai),
                'tgl_selesai_indo' => self::formatTanggalIndonesia($selesai),
                'tgl_mulai_num' => self::formatTanggalNumeric($mulai),
                'tgl_selesai_num' => self::formatTanggalNumeric($selesai),
                'periode_teks' => self::formatTanggalNumeric($mulai).' - '.self::formatTanggalNumeric($selesai),
                'hari_kalender' => $hariKalender,
                'hari_potah' => $hariPotah,
                'is_jeda' => $isJeda,
                'keterangan' => $keterangan ?: '-',
            ];
        }

        $refPutusan = $tglPutusan ?? ($latestEnd ?? Carbon::today());
        $tglAwalEfektif = $refPutusan->copy()->subDays($totalPotah);

        return [
            'items' => $items,
            'total_hari_kalender' => $totalKalender,
            'total_hari_penahanan_kalender' => $totalKalenderPenahanan,
            'total_hari_potah' => $totalPotah,
            'total_hari_jeda' => $totalJeda,
            'total_hari_luar_upt' => $totalJeda,
            'total_hari_luar_upt_pra' => $totalLuarUptPra,
            'total_hari_luar_upt_pasca' => $totalLuarUptPasca,
            'luar_upt_pasca_intervals' => $luarUptPascaIntervals,
            'tgl_awal_fisik' => $earliestStart ?? Carbon::today(),
            'tgl_awal_efektif' => $tglAwalEfektif,
            'tgl_putusan' => $tglPutusan,
        ];
    }

    /**
     * Hitung Proyeksi Remisi dan Hak Integrasi (PB, CB, Asimilasi)
     * Berdasarkan UU No. 22 Tahun 2022, Keppres No. 174 Tahun 1999 (RU, RK, Remisi Tambahan),
     * Keppres No. 120/1955 s.d. Keppres 120/2015 (Remisi Dasawarsa tiap 10 tahun RI),
     * Serta Metode Standar SDP Ditjenpas (2/3 Murni dikurangi Full Remisi).
     *
     * @param  array<int, array<string, mixed>>  $riwayatPenahanan
     * @param  array<int, int|string>  $pemukaTahunList
     * @param  array<int, int|string>  $donorTahunList
     * @return array<string, mixed>
     */
    public function hitungProyeksiRemisiDanIntegrasi(
        string $tglAwalStr,
        int $tahun,
        int $bulan,
        int $hari,
        string $agama = 'islam',
        int $remisiAwalHari = 0,
        string $namaWbp = '',
        string $noRegister = '',
        bool $includeDasawarsa = true,
        string $kategoriTambahan = 'none',
        string $targetBasis = '2_3',
        int $tahunMulaiTambahan = 1,
        string $perkaraPasal = '',
        ?string $tglPutusanStr = null,
        int $subTahun = 0,
        int $subBulan = 0,
        int $subHari = 0,
        float|int|string $dendaNominal = 0,
        string $subsiderStatus = 'bb',
        array $riwayatPenahanan = [],
        array $pemukaTahunList = [],
        array $donorTahunList = []
    ): array {
        $tglAwal = Carbon::parse($tglAwalStr)->startOfDay();
        $tglAwalFisik = $tglAwal;

        if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
            throw new InvalidArgumentException('Lama pidana vonis harus lebih dari 0.');
        }

        $tglPutusan = null;
        $masaTahananHari = 0;
        $masaTahananTeks = '-';
        $riwayatData = null;
        $hasRiwayatPenahanan = false;
        $totalHariLuarUpt = 0;
        $totalHariLuarUptPra = 0;
        $totalHariLuarUptPasca = 0;
        $luarUptPascaIntervals = [];

        if (! empty($riwayatPenahanan)) {
            $parsedPutusanInitial = ! empty($tglPutusanStr) ? Carbon::parse($tglPutusanStr)->startOfDay() : null;
            $riwayatData = $this->hitungRiwayatPenahanan($riwayatPenahanan, $parsedPutusanInitial);
            if (! empty($riwayatData['items'])) {
                $hasRiwayatPenahanan = true;
                $tglPutusan = $riwayatData['tgl_putusan'];
                $masaTahananHari = $riwayatData['total_hari_potah'];
                $masaTahananTeks = self::formatHariKeTeks($masaTahananHari);
                $tglAwalFisik = $riwayatData['tgl_awal_fisik'];
                $tglAwal = $riwayatData['tgl_awal_efektif'];
                $totalHariLuarUpt = (int) ($riwayatData['total_hari_luar_upt'] ?? 0);
                $totalHariLuarUptPra = (int) ($riwayatData['total_hari_luar_upt_pra'] ?? 0);
                $totalHariLuarUptPasca = (int) ($riwayatData['total_hari_luar_upt_pasca'] ?? 0);
                $luarUptPascaIntervals = (array) ($riwayatData['luar_upt_pasca_intervals'] ?? []);
            }
        }

        if (! $hasRiwayatPenahanan && ! empty($tglPutusanStr)) {
            $parsedPutusan = Carbon::parse($tglPutusanStr)->startOfDay();
            if ($parsedPutusan->lt($tglAwal)) {
                throw new InvalidArgumentException('Tanggal putusan pengadilan tidak boleh mendahului tanggal mulai ditahan.');
            }
            $tglPutusan = $parsedPutusan;
            $masaTahananHari = (int) $tglAwal->diffInDays($tglPutusan);
            $masaTahananTeks = self::formatHariKeTeks($masaTahananHari);
        }

        $namaWbp = trim($namaWbp) ?: 'Narapidana';
        $noRegister = trim($noRegister) ?: '-';
        $perkaraPasal = trim($perkaraPasal) ?: '-';

        $targetBasis = $targetBasis === 'ekspirasi' ? 'ekspirasi' : '2_3';
        $tahunMulaiTambahan = max(1, $tahunMulaiTambahan);

        $agama = strtolower(trim($agama));
        $validAgama = ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'khonghucu'];
        if (! in_array($agama, $validAgama, true)) {
            $agama = 'islam';
        }

        $normalizeTahunList = static function (array $list): array {
            $clean = [];
            foreach ($list as $item) {
                $val = (int) $item;
                if ($val >= 1 && $val <= 50) {
                    $clean[$val] = $val;
                }
            }
            ksort($clean);

            return array_values($clean);
        };

        $pemukaTahunClean = $normalizeTahunList($pemukaTahunList);
        $donorTahunClean = $normalizeTahunList($donorTahunList);

        $validTambahan = ['none', 'pemuka', 'jasa_negara', 'kombinasi'];
        if (! in_array($kategoriTambahan, $validTambahan, true)) {
            $kategoriTambahan = 'none';
        }

        if (! empty($pemukaTahunClean) && ! empty($donorTahunClean)) {
            $kategoriTambahan = 'kombinasi';
        } elseif (! empty($pemukaTahunClean)) {
            $kategoriTambahan = 'pemuka';
        } elseif (! empty($donorTahunClean)) {
            $kategoriTambahan = 'jasa_negara';
        }

        if ($remisiAwalHari < 0) {
            $remisiAwalHari = 0;
        }

        // 1. Ekspirasi Murni (Sebelum Remisi), memperhitungkan jeda Hari di Luar UPT Pasca-Putusan
        $tglEkspirasiMurni = self::tambahDurasiVonis($tglAwal, $tahun, $bulan, $hari)->addDays($totalHariLuarUptPasca);

        // Pentahapan Masa Pidana Murni: Proporsional dari pidana vonis (Tahun, Bulan, Hari)
        // Dihitung dari tanggal awal penahanan dengan kaidah hitung vonis yang sama (+ jeda pasca-putusan)
        [$y13Murni, $m13Murni, $d13Murni] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 3);
        [$y12Murni, $m12Murni, $d12Murni] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 2);
        [$y23Murni, $m23Murni, $d23Murni] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 2, 3);

        $tgl13Murni = self::tambahDurasiVonis($tglAwal, $y13Murni, $m13Murni, $d13Murni)->addDays($totalHariLuarUptPasca);
        $tgl12Murni = self::tambahDurasiVonis($tglAwal, $y12Murni, $m12Murni, $d12Murni)->addDays($totalHariLuarUptPasca);
        $tgl23Murni = self::tambahDurasiVonis($tglAwal, $y23Murni, $m23Murni, $d23Murni)->addDays($totalHariLuarUptPasca);

        // Total hari baku: 1 Tahun = 360 hari (12 bln x 30 hari), 1 Bulan = 30 hari
        $totalHariVonis = ($tahun * 360) + ($bulan * 30) + $hari;

        // 2. Tentukan Jenis Program Integrasi (PB vs CB)
        // Syarat CB: Pidana paling lama 1 tahun 6 bulan (18 bulan), minimal menjalani 2/3 (min 6 bulan = 180 hari)
        // Syarat PB: Pidana > 1 tahun 6 bulan, minimal menjalani 2/3 (min 9 bulan = 270 hari)
        $totalBulan = ($tahun * 12) + $bulan;
        $isCutiBersyarat = ($totalBulan < 18) || ($totalBulan === 18 && $hari === 0);

        if ($isCutiBersyarat) {
            $jenisProgram = 'CB';
            $namaProgram = 'Cuti Bersyarat (CB)';
            $minHariIntegrasi = 180; // 6 bulan
            $keteranganProgram = 'Vonis pidana s.d. 1 Tahun 6 Bulan memenuhi syarat Cuti Bersyarat (CB).';
        } else {
            $jenisProgram = 'PB';
            $namaProgram = 'Pembebasan Bersyarat (PB)';
            $minHariIntegrasi = 270; // 9 bulan
            $keteranganProgram = 'Vonis pidana di atas 1 Tahun 6 Bulan memenuhi syarat Pembebasan Bersyarat (PB).';
        }

        // 3. Bangun Kalender Event Remisi Kronologis (RU 17 Agustus & RK Hari Raya Keagamaan)
        $startYear = min($tglAwalFisik->year, $tglAwal->year);
        $endYear = $tglEkspirasiMurni->year + 1;
        $rawEvents = $this->generateRemisiEvents($agama, $startYear, $endYear);

        // Filter event yang jatuh setelah tanggal awal ditahan dan urutkan tanggalnya
        $eligibleEvents = [];
        foreach ($rawEvents as $ev) {
            $evDate = Carbon::parse($ev['tanggal'])->startOfDay();
            if ($evDate->gte($tglAwalFisik)) {
                $eligibleEvents[] = [
                    'nama_event' => $ev['nama_event'],
                    'jenis' => $ev['jenis'], // 'RU' atau 'RK'
                    'tahun' => $evDate->year,
                    'tanggal' => $evDate->toDateString(),
                    'carbon' => $evDate,
                ];
            }
        }

        usort($eligibleEvents, static function ($a, $b) {
            return $a['carbon']->timestamp <=> $b['carbon']->timestamp;
        });

        // 4. Simulasi Proyeksi Remisi Sesuai Keppres No. 174 Tahun 1999 & Keppres Dasawarsa
        // Batas Minimal Berhak Integrasi UU No. 22 Tahun 2022 (+ jeda pasca-putusan)
        $tglMin13 = $tglAwal->copy()->addDays($totalHariLuarUptPasca);
        $tglMin12Base = $tglAwal->copy()->addDays(180);
        $tglMinIntegrasiBase = $tglAwal->copy()->addDays($minHariIntegrasi);
        $tglMin12 = $tglMin12Base->copy()->addDays($totalHariLuarUptPasca);
        $tglMinIntegrasi = $tglMinIntegrasiBase->copy()->addDays($totalHariLuarUptPasca);

        // Inisialisasi akumulasi remisi & fungsi penghitung pentahapan dinamis
        $totalHariRemisi = $remisiAwalHari;
        $cumBulanRemisi = (int) floor($remisiAwalHari / 30);
        $cumHariRemisi = $remisiAwalHari % 30;

        $calcCurrentMilestones = function () use (
            $tglAwal,
            $tahun,
            $bulan,
            $hari,
            $tglEkspirasiMurni,
            $tglMinIntegrasiBase,
            $tglMin12Base,
            $totalHariLuarUptPasca,
            &$totalHariRemisi
        ) {
            $curEkspirasi = $tglEkspirasiMurni->copy()->subDays($totalHariRemisi);

            $res13 = self::hitungPentahapanSetelahRemisi($tglAwal, $tahun, $bulan, $hari, 1, 3, $totalHariRemisi, $tglAwal);
            $res12 = self::hitungPentahapanSetelahRemisi($tglAwal, $tahun, $bulan, $hari, 1, 2, $totalHariRemisi, $tglMin12Base);
            $res23 = self::hitungPentahapanSetelahRemisi($tglAwal, $tahun, $bulan, $hari, 2, 3, $totalHariRemisi, $tglMinIntegrasiBase);

            return [
                $curEkspirasi,
                $res13['tanggal']->copy()->addDays($totalHariLuarUptPasca),
                $res12['tanggal']->copy()->addDays($totalHariLuarUptPasca),
                $res23['tanggal']->copy()->addDays($totalHariLuarUptPasca),
            ];
        };

        [$currentEkspirasi, $current13Date, $current12Date, $current23Date] = $calcCurrentMilestones();

        $milestonesSebelumRemisiTerakhir = [
            'ekspirasi' => $currentEkspirasi->copy(),
            'tgl_1_3' => $current13Date->copy(),
            'tgl_1_2' => $current12Date->copy(),
            'tgl_2_3' => $current23Date->copy(),
        ];
        $remisiTerakhirInfo = null;

        $keRu = 0;
        $keRk = 0;
        $ledger = [];

        foreach ($eligibleEvents as $item) {
            $evDate = $item['carbon'];

            if ($targetBasis === '2_3') {
                // Standar 2/3 (PB/CB): Jika tanggal event sudah melewati tanggal 2/3 definitif terkini,
                // maka WBP sudah bebas bersyarat di luar Lapas, sehingga remisi tahun-tahun berikutnya tidak dihitung di Lapas.
                if ($evDate->gt($current23Date)) {
                    break;
                }
            } else {
                // Jika tanggal event sudah sama atau melewati proyeksi ekspirasi terkini, WBP sudah bebas murni
                if ($evDate->gte($currentEkspirasi)) {
                    break;
                }
            }

            $snapshotMilestoneSebelumEvent = [
                'ekspirasi' => $currentEkspirasi->copy(),
                'tgl_1_3' => $current13Date->copy(),
                'tgl_1_2' => $current12Date->copy(),
                'tgl_2_3' => $current23Date->copy(),
                'total_remisi_hari' => $totalHariRemisi,
            ];
            $remisiHariEventIni = 0;

            $hariLuarUptSampaiEvent = 0;
            foreach ($luarUptPascaIntervals as $seg) {
                if ($evDate->gt($seg['mulai'])) {
                    $batasSelesai = $evDate->lt($seg['selesai']) ? $evDate : $seg['selesai'];
                    $hariLuarUptSampaiEvent += (int) $seg['mulai']->diffInDays($batasSelesai);
                }
            }

            $hariDijalani = $evDate->gte($tglAwal) ? max(0, (int) $tglAwal->diffInDays($evDate) - $hariLuarUptSampaiEvent) : 0;
            $isSebelumPutusan = $tglPutusan && $evDate->lt($tglPutusan);

            // Syarat Minimal Keppres 174/1999: Minimal telah menjalani 6 bulan (180 hari)
            if ($hariDijalani < 180) {
                $alasanBelum6Bln = "Masa pidana baru dijalani {$hariDijalani} hari (kurang dari 6 bulan) saat tanggal event, belum memenuhi syarat substantif masa minimal 6 bulan Keppres No. 174/1999.";
                if ($isSebelumPutusan) {
                    $alasanBelum6Bln .= ' (Pada tanggal event ini WBP juga masih berstatus Tahanan, Putusan: '.self::formatTanggalIndonesia($tglPutusan).').';
                }

                $ledger[] = [
                    'nama_event' => $item['nama_event'],
                    'jenis' => $item['jenis'],
                    'tahun' => $item['tahun'],
                    'tanggal' => $item['tanggal'],
                    'tanggal_indo' => self::formatTanggalIndonesia($evDate),
                    'hari_dijalani' => $hariDijalani,
                    'masa_dijalani_teks' => self::formatHariKeTeks($hariDijalani),
                    'eligible' => false,
                    'keterangan_status' => 'Belum Memenuhi Syarat',
                    'tahap' => '-',
                    'besaran_hari' => 0,
                    'besaran_teks' => '0 Hari',
                    'total_akumulasi_hari' => $totalHariRemisi,
                    'tgl_1_3_baru' => $current13Date->toDateString(),
                    'tgl_1_3_baru_indo' => self::formatTanggalIndonesia($current13Date),
                    'tgl_1_2_baru' => $current12Date->toDateString(),
                    'tgl_1_2_baru_indo' => self::formatTanggalIndonesia($current12Date),
                    'tgl_2_3_baru' => $current23Date->toDateString(),
                    'tgl_2_3_baru_indo' => self::formatTanggalIndonesia($current23Date),
                    'perkiraan_ekspirasi_baru' => $currentEkspirasi->toDateString(),
                    'perkiraan_ekspirasi_baru_indo' => self::formatTanggalIndonesia($currentEkspirasi),
                    'keterangan_alasan' => $alasanBelum6Bln,
                ];

                continue;
            }

            // Narapidana Memenuhi Syarat (Eligible >= 6 bulan)
            $potonganHari = 0;
            $evBln = 0;
            $evHr = 0;
            $besaranTeks = '';
            $tahapTeks = '';
            $keteranganAlasan = '';

            if ($item['jenis'] === 'RU') {
                $keRu++;
                $tahapTeks = 'Tahun ke-'.$keRu;

                if ($keRu === 1) {
                    if ($hariDijalani < 360) {
                        $evBln = 1;
                        $evHr = 0;
                        $potonganHari = 30; // 1 Bulan (6 - 12 bulan)
                        $besaranTeks = '1 Bulan';
                        $keteranganAlasan = 'Tahun I Remisi Umum: Besaran 1 bulan karena telah menjalani masa pidana antara 6 s/d 12 bulan (kurang dari 1 tahun) pada tanggal 17 Agustus.';
                    } else {
                        $evBln = 2;
                        $evHr = 0;
                        $potonganHari = 60; // 2 Bulan (>= 12 bulan)
                        $besaranTeks = '2 Bulan';
                        $keteranganAlasan = 'Tahun I Remisi Umum: Besaran 2 bulan karena telah menjalani masa pidana 12 bulan atau lebih pada tahun pertama.';
                    }
                } elseif ($keRu === 2) {
                    $evBln = 3;
                    $evHr = 0;
                    $potonganHari = 90; // 3 Bulan
                    $besaranTeks = '3 Bulan';
                    $keteranganAlasan = 'Tahun II Remisi Umum: Besaran 3 bulan karena telah menjalani masa pidana pada tahun kedua.';
                } elseif ($keRu === 3) {
                    $evBln = 4;
                    $evHr = 0;
                    $potonganHari = 120; // 4 Bulan
                    $besaranTeks = '4 Bulan';
                    $keteranganAlasan = 'Tahun III Remisi Umum: Besaran 4 bulan karena telah menjalani masa pidana pada tahun ketiga.';
                } elseif ($keRu === 4 || $keRu === 5) {
                    $evBln = 5;
                    $evHr = 0;
                    $potonganHari = 150; // 5 Bulan
                    $besaranTeks = '5 Bulan';
                    $keRomawi = $keRu === 4 ? 'IV' : 'V';
                    $keteranganAlasan = "Tahun {$keRomawi} Remisi Umum: Besaran 5 bulan karena telah menjalani masa pidana pada tahun keempat/kelima.";
                } else {
                    $evBln = 6;
                    $evHr = 0;
                    $potonganHari = 180; // 6 Bulan (Tahun ke-6 dst)
                    $besaranTeks = '6 Bulan';
                    $keteranganAlasan = "Tahun ke-{$keRu} Remisi Umum: Besaran 6 bulan (maksimal reguler tahunan) untuk tahun keenam dan seterusnya.";
                }
            } else {
                // Remisi Khusus (RK)
                $keRk++;
                $tahapTeks = 'Tahun ke-'.$keRk;

                if ($keRk === 1) {
                    if ($hariDijalani < 360) {
                        $evBln = 0;
                        $evHr = 15;
                        $potonganHari = 15; // 15 Hari (6 - 12 bulan)
                        $besaranTeks = '15 Hari';
                        $keteranganAlasan = 'Tahun I Remisi Khusus: Besaran 15 hari karena telah menjalani masa pidana antara 6 s/d 12 bulan pada hari raya keagamaan.';
                    } else {
                        $evBln = 1;
                        $evHr = 0;
                        $potonganHari = 30; // 1 Bulan (>= 12 bulan)
                        $besaranTeks = '1 Bulan';
                        $keteranganAlasan = 'Tahun I Remisi Khusus: Besaran 1 bulan karena telah menjalani masa pidana 12 bulan atau lebih pada tahun pertama.';
                    }
                } elseif ($keRk === 2 || $keRk === 3) {
                    $evBln = 1;
                    $evHr = 0;
                    $potonganHari = 30; // 1 Bulan
                    $besaranTeks = '1 Bulan';
                    $keRomawi = $keRk === 2 ? 'II' : 'III';
                    $keteranganAlasan = "Tahun {$keRomawi} Remisi Khusus: Besaran 1 bulan pada tahun kedua/ketiga.";
                } elseif ($keRk === 4 || $keRk === 5) {
                    $evBln = 1;
                    $evHr = 15;
                    $potonganHari = 45; // 1 Bulan 15 Hari
                    $besaranTeks = '1 Bulan 15 Hari';
                    $keRomawi = $keRk === 4 ? 'IV' : 'V';
                    $keteranganAlasan = "Tahun {$keRomawi} Remisi Khusus: Besaran 1 bulan 15 hari pada tahun keempat/kelima.";
                } else {
                    $evBln = 2;
                    $evHr = 0;
                    $potonganHari = 60; // 2 Bulan (Tahun ke-6 dst)
                    $besaranTeks = '2 Bulan';
                    $keteranganAlasan = "Tahun ke-{$keRk} Remisi Khusus: Besaran 2 bulan (maksimal reguler tahunan) untuk tahun keenam dan seterusnya.";
                }
            }

            // Pastikan potongan remisi tidak melebihi sisa hari pidana aktual menuju ekspirasi
            $sisaHariSebelumEkspirasi = (int) $evDate->diffInDays($currentEkspirasi);
            if ($potonganHari > $sisaHariSebelumEkspirasi) {
                $potonganHari = max(0, $sisaHariSebelumEkspirasi);
                $evBln = (int) floor($potonganHari / 30);
                $evHr = $potonganHari % 30;
                $besaranTeks = "{$potonganHari} Hari (Sisa Pidana)";
            }

            $cumBulanRemisi += $evBln;
            $cumHariRemisi += $evHr;
            if ($cumHariRemisi >= 30) {
                $cumBulanRemisi += (int) floor($cumHariRemisi / 30);
                $cumHariRemisi = $cumHariRemisi % 30;
            }
            $totalHariRemisi += $potonganHari;
            $remisiHariEventIni += $potonganHari;
            [$currentEkspirasi, $current13Date, $current12Date, $current23Date] = $calcCurrentMilestones();

            $statusEvent = $isSebelumPutusan ? 'Memenuhi Syarat (Remisi Susulan)' : 'Memenuhi Syarat';
            if ($isSebelumPutusan) {
                $keteranganAlasan .= ' (Catatan: Saat event ini WBP masih berstatus Tahanan karena putusan pengadilan dijatuhkan pada '.self::formatTanggalIndonesia($tglPutusan).'. Diproses melalui Remisi Susulan di SDP).';
            }

            $ledger[] = [
                'nama_event' => $item['nama_event'],
                'jenis' => $item['jenis'],
                'tahun' => $item['tahun'],
                'tanggal' => $item['tanggal'],
                'tanggal_indo' => self::formatTanggalIndonesia($evDate),
                'hari_dijalani' => $hariDijalani,
                'masa_dijalani_teks' => self::formatHariKeTeks($hariDijalani),
                'eligible' => true,
                'keterangan_status' => $statusEvent,
                'tahap' => $tahapTeks,
                'besaran_hari' => $potonganHari,
                'besaran_teks' => $besaranTeks,
                'total_akumulasi_hari' => $totalHariRemisi,
                'tgl_1_3_baru' => $current13Date->toDateString(),
                'tgl_1_3_baru_indo' => self::formatTanggalIndonesia($current13Date),
                'tgl_1_2_baru' => $current12Date->toDateString(),
                'tgl_1_2_baru_indo' => self::formatTanggalIndonesia($current12Date),
                'tgl_2_3_baru' => $current23Date->toDateString(),
                'tgl_2_3_baru_indo' => self::formatTanggalIndonesia($current23Date),
                'perkiraan_ekspirasi_baru' => $currentEkspirasi->toDateString(),
                'perkiraan_ekspirasi_baru_indo' => self::formatTanggalIndonesia($currentEkspirasi),
                'keterangan_alasan' => $keteranganAlasan,
            ];

            // 4a. REMISI TAMBAHAN (Keppres 174/1999 & Permenkumham 7/2022) - Melekat pada Remisi Umum
            // Evaluasi Pemuka (+1/3 RU) & Donor Darah / Jasa Negara (+1/2 RU) secara independen agar dapat aktif sekaligus
            if ($item['jenis'] === 'RU' && $potonganHari > 0) {
                $hasExplicitArrays = ! empty($pemukaTahunClean) || ! empty($donorTahunClean);

                $isPemukaAktif = ! empty($pemukaTahunClean)
                    ? in_array($keRu, $pemukaTahunClean, true)
                    : (! $hasExplicitArrays && $kategoriTambahan === 'pemuka' && $keRu >= $tahunMulaiTambahan);

                $isDonorAktif = ! empty($donorTahunClean)
                    ? in_array($keRu, $donorTahunClean, true)
                    : (! $hasExplicitArrays && $kategoriTambahan === 'jasa_negara' && $keRu >= $tahunMulaiTambahan);

                $tambahanQueue = [];

                if ($isPemukaAktif) {
                    // 1/3 dari Remisi Umum
                    $rtBln = (int) floor($evBln / 3);
                    $rtHr = (int) floor((($evBln % 3) * 30) / 3);
                    $tambahanQueue[] = [
                        'rt_bln' => $rtBln,
                        'rt_hr' => $rtHr,
                        'potongan' => ($rtBln * 30) + $rtHr,
                        'nama' => "Remisi Tambahan Pemuka ({$item['tahun']})",
                        'keterangan' => "Bonus 1/3 dari Remisi Umum ({$besaranTeks}) bagi narapidana pemuka pembinaan di Lapas (Keppres No. 174/1999).",
                    ];
                }

                if ($isDonorAktif) {
                    // 1/2 dari Remisi Umum
                    $rtBln = (int) floor($evBln / 2);
                    $rtHr = ($evBln % 2 === 1) ? 15 : 0;
                    $tambahanQueue[] = [
                        'rt_bln' => $rtBln,
                        'rt_hr' => $rtHr,
                        'potongan' => ($rtBln * 30) + $rtHr,
                        'nama' => ! empty($donorTahunClean)
                            ? "Remisi Tambahan Donor Darah / Jasa Negara ({$item['tahun']})"
                            : "Remisi Tambahan Jasa Negara/Kemanusiaan ({$item['tahun']})",
                        'keterangan' => "Bonus 1/2 dari Remisi Umum ({$besaranTeks}) atas perbuatan kemanusiaan/donor darah atau jasa bagi negara (Keppres No. 174/1999).",
                    ];
                }

                foreach ($tambahanQueue as $tItem) {
                    $potonganTambahan = $tItem['potongan'];
                    $rtBln = $tItem['rt_bln'];
                    $rtHr = $tItem['rt_hr'];
                    $namaEventTambahan = $tItem['nama'];
                    $keteranganTambahan = $tItem['keterangan'];

                    if ($potonganTambahan <= 0) {
                        continue;
                    }

                    $sisaHariTambahan = (int) $evDate->diffInDays($currentEkspirasi);
                    if ($potonganTambahan > $sisaHariTambahan) {
                        $potonganTambahan = max(0, $sisaHariTambahan);
                        $rtBln = (int) floor($potonganTambahan / 30);
                        $rtHr = $potonganTambahan % 30;
                    }

                    if ($potonganTambahan <= 0) {
                        continue;
                    }

                    $cumBulanRemisi += $rtBln;
                    $cumHariRemisi += $rtHr;
                    if ($cumHariRemisi >= 30) {
                        $cumBulanRemisi += (int) floor($cumHariRemisi / 30);
                        $cumHariRemisi = $cumHariRemisi % 30;
                    }
                    $totalHariRemisi += $potonganTambahan;
                    $remisiHariEventIni += $potonganTambahan;
                    [$currentEkspirasi, $current13Date, $current12Date, $current23Date] = $calcCurrentMilestones();

                    $statusTambahan = $isSebelumPutusan ? 'Memenuhi Syarat (Tambahan - Susulan)' : 'Memenuhi Syarat (Tambahan)';
                    if ($isSebelumPutusan) {
                        $keteranganTambahan .= ' (Remisi susulan pasca-putusan '.self::formatTanggalIndonesia($tglPutusan).').';
                    }

                    $ledger[] = [
                        'nama_event' => $namaEventTambahan,
                        'jenis' => 'RT',
                        'tahun' => $item['tahun'],
                        'tanggal' => $item['tanggal'],
                        'tanggal_indo' => self::formatTanggalIndonesia($evDate),
                        'hari_dijalani' => $hariDijalani,
                        'masa_dijalani_teks' => self::formatHariKeTeks($hariDijalani),
                        'eligible' => true,
                        'keterangan_status' => $statusTambahan,
                        'tahap' => 'Tambahan',
                        'besaran_hari' => $potonganTambahan,
                        'besaran_teks' => self::formatHariKeTeks($potonganTambahan),
                        'total_akumulasi_hari' => $totalHariRemisi,
                        'tgl_1_3_baru' => $current13Date->toDateString(),
                        'tgl_1_3_baru_indo' => self::formatTanggalIndonesia($current13Date),
                        'tgl_1_2_baru' => $current12Date->toDateString(),
                        'tgl_1_2_baru_indo' => self::formatTanggalIndonesia($current12Date),
                        'tgl_2_3_baru' => $current23Date->toDateString(),
                        'tgl_2_3_baru_indo' => self::formatTanggalIndonesia($current23Date),
                        'perkiraan_ekspirasi_baru' => $currentEkspirasi->toDateString(),
                        'perkiraan_ekspirasi_baru_indo' => self::formatTanggalIndonesia($currentEkspirasi),
                        'keterangan_alasan' => $keteranganTambahan,
                    ];
                }
            }

            // 4b. REMISI DASAWARSA (Keppres No. 120/1955 jo. Keppres No. 120/2015)
            // Diberikan setiap kelipatan 10 tahun Kemerdekaan RI (17 Agustus: 2025, 2035, 2045)
            if ($item['jenis'] === 'RU' && $includeDasawarsa && (($item['tahun'] - 1945) % 10 === 0)) {
                $hutKe = $item['tahun'] - 1945;
                // Rumus Keppres Dasawarsa: 1/12 vonis pokok, maksimal 3 bulan (90 hari)
                $potonganDasawarsaMaks = min(90, (int) floor($totalHariVonis / 12));

                $sisaHariDasawarsa = (int) $evDate->diffInDays($currentEkspirasi);
                $potonganDasawarsa = min($potonganDasawarsaMaks, max(0, $sisaHariDasawarsa));

                if ($potonganDasawarsa > 0) {
                    $rdBln = (int) floor($potonganDasawarsa / 30);
                    $rdHr = $potonganDasawarsa % 30;

                    $cumBulanRemisi += $rdBln;
                    $cumHariRemisi += $rdHr;
                    if ($cumHariRemisi >= 30) {
                        $cumBulanRemisi += (int) floor($cumHariRemisi / 30);
                        $cumHariRemisi = $cumHariRemisi % 30;
                    }
                    $totalHariRemisi += $potonganDasawarsa;
                    $remisiHariEventIni += $potonganDasawarsa;
                    [$currentEkspirasi, $current13Date, $current12Date, $current23Date] = $calcCurrentMilestones();

                    $statusDasawarsa = $isSebelumPutusan ? 'Memenuhi Syarat (Dasawarsa - Susulan)' : 'Memenuhi Syarat (Dasawarsa)';
                    $keteranganAlasanDasawarsa = "Pengurangan istimewa peringatan HUT ke-{$hutKe} Kemerdekaan RI: 1/12 dari vonis pokok (".self::formatHariKeTeks($potonganDasawarsaMaks).'), dibatasi maksimal 3 bulan / 90 hari.';
                    if ($isSebelumPutusan) {
                        $keteranganAlasanDasawarsa .= ' (Remisi susulan pasca-putusan '.self::formatTanggalIndonesia($tglPutusan).').';
                    }

                    $ledger[] = [
                        'nama_event' => "Remisi Dasawarsa HUT ke-{$hutKe} RI ({$item['tahun']})",
                        'jenis' => 'RD',
                        'tahun' => $item['tahun'],
                        'tanggal' => $item['tanggal'],
                        'tanggal_indo' => self::formatTanggalIndonesia($evDate),
                        'hari_dijalani' => $hariDijalani,
                        'masa_dijalani_teks' => self::formatHariKeTeks($hariDijalani),
                        'eligible' => true,
                        'keterangan_status' => $statusDasawarsa,
                        'tahap' => "Dasawarsa {$hutKe} Thn",
                        'besaran_hari' => $potonganDasawarsa,
                        'besaran_teks' => self::formatHariKeTeks($potonganDasawarsa),
                        'total_akumulasi_hari' => $totalHariRemisi,
                        'tgl_1_3_baru' => $current13Date->toDateString(),
                        'tgl_1_3_baru_indo' => self::formatTanggalIndonesia($current13Date),
                        'tgl_1_2_baru' => $current12Date->toDateString(),
                        'tgl_1_2_baru_indo' => self::formatTanggalIndonesia($current12Date),
                        'tgl_2_3_baru' => $current23Date->toDateString(),
                        'tgl_2_3_baru_indo' => self::formatTanggalIndonesia($current23Date),
                        'perkiraan_ekspirasi_baru' => $currentEkspirasi->toDateString(),
                        'perkiraan_ekspirasi_baru_indo' => self::formatTanggalIndonesia($currentEkspirasi),
                        'keterangan_alasan' => $keteranganAlasanDasawarsa,
                    ];
                }
            }

            if ($remisiHariEventIni > 0) {
                $milestonesSebelumRemisiTerakhir = $snapshotMilestoneSebelumEvent;
                $remisiTerakhirInfo = [
                    'nama_event' => $item['nama_event'],
                    'jenis' => $item['jenis'],
                    'tahun' => $item['tahun'],
                    'tanggal' => $item['tanggal'],
                    'tanggal_carbon' => $evDate->copy(),
                    'besaran_hari' => $remisiHariEventIni,
                    'besaran_teks' => self::formatHariKeTeks($remisiHariEventIni),
                ];
            }
        }

        // 5. Penetapan Pentahapan Definitif Akhir Hasil Simulasi
        $tgl13Definitif = $current13Date->copy();
        $tgl12Definitif = $current12Date->copy();
        $tgl23Definitif = $current23Date->copy();
        $tglEkspirasiDefinitif = $currentEkspirasi->copy();

        // 6. Tanggal Mulai Pengusulan PB/CB (H-2 Bulan / 60 Hari sebelum 2/3 tercapai) & Evaluasi 2 Opsi Realistis SDP
        $tglUsulMurni = $tgl23Definitif->copy()->subDays(60);
        $hasBenturanRemisiTerakhir = false;
        $tglUsulOpsi1 = $tglUsulMurni->copy();
        $tglUsulOpsi2 = $tglUsulMurni->copy();
        $tgl23PraRemisi = $tgl23Definitif->copy();
        $tglRemisiTerakhir = null;

        if ($remisiTerakhirInfo) {
            $tglRemisiTerakhir = $remisiTerakhirInfo['tanggal_carbon'];
            $tgl23PraRemisi = $milestonesSebelumRemisiTerakhir['tgl_2_3']->copy();
            $tglUsulOpsi1 = $tgl23PraRemisi->copy()->subDays(60);

            if ($tglUsulMurni->lt($tglRemisiTerakhir)) {
                $hasBenturanRemisiTerakhir = true;
                $tglUsulOpsi2 = $tglRemisiTerakhir->copy();
            } else {
                $tglUsulOpsi2 = $tglUsulMurni->copy();
            }
        }

        // Tanggal mulai pengusulan default: jika terdapat benturan di SDP, gunakan Opsi 2 (Pasca-Remisi Terakhir)
        // karena di SDP pengusulan tidak mungkin kurang dari tanggal pemberian remisi
        $tglMulaiPengusulan = $hasBenturanRemisiTerakhir ? $tglUsulOpsi2->copy() : $tglUsulMurni->copy();

        // Status Pengusulan terhadap hari ini
        $today = Carbon::today();
        if ($today->lt($tglMulaiPengusulan)) {
            $sisaHariMenujuUsul = (int) $today->diffInDays($tglMulaiPengusulan);
            $pesanPengusulan = "Pengusulan di SDP dapat dimulai pada {$this->formatTanggalIndonesia($tglMulaiPengusulan)} (tersisa {$sisaHariMenujuUsul} hari lagi).";
            if ($hasBenturanRemisiTerakhir) {
                $pesanPengusulan .= " (Opsi 2: Mengikuti terbitnya SK {$remisiTerakhirInfo['nama_event']} pada {$this->formatTanggalIndonesia($tglRemisiTerakhir)}. Untuk pengusulan lebih awal, UPT dapat menggunakan Opsi 1 sejak {$this->formatTanggalIndonesia($tglUsulOpsi1)}).";
            }
            $statusPengusulan = [
                'kode' => 'menunggu',
                'label' => "Belum Waktunya ({$sisaHariMenujuUsul} hari lagi)",
                'badge_class' => 'bg-secondary',
                'sisa_hari' => $sisaHariMenujuUsul,
                'pesan' => $pesanPengusulan,
            ];
        } elseif ($today->lte($tgl23Definitif)) {
            $sisaHari23 = (int) $today->diffInDays($tgl23Definitif);
            $pesanPengusulan = "Waktunya Pengusulan! Pengusulan di SDP dapat diproses (H-2 bulan sebelum 2/3). Tanggal 2/3 masa pidana tercapai dalam {$sisaHari23} hari lagi ({$this->formatTanggalIndonesia($tgl23Definitif)}).";
            if ($hasBenturanRemisiTerakhir && $today->gte($tglRemisiTerakhir)) {
                $pesanPengusulan = "Waktunya Pengusulan! SK {$remisiTerakhirInfo['nama_event']} telah terbit ({$this->formatTanggalIndonesia($tglRemisiTerakhir)}), pengusulan integrasi di SDP dapat segera diproses.";
            }
            $statusPengusulan = [
                'kode' => 'waktunya_usul',
                'label' => 'Waktunya Pengusulan!',
                'badge_class' => 'bg-warning text-dark',
                'sisa_hari' => $sisaHari23,
                'pesan' => $pesanPengusulan,
            ];
        } else {
            $hariLewat = (int) $tgl23Definitif->diffInDays($today);
            $statusPengusulan = [
                'kode' => 'lewat_23',
                'label' => 'Sudah Melewati 2/3',
                'badge_class' => 'bg-success',
                'sisa_hari' => -$hariLewat,
                'pesan' => "Masa pidana telah melewati tanggal 2/3 sejak {$this->formatTanggalIndonesia($tgl23Definitif)} ({$hariLewat} hari yang lalu). Pastikan SK {$namaProgram} telah terbit atau dalam proses.",
            ];
        }

        $opsiPengusulan = [
            'has_benturan' => $hasBenturanRemisiTerakhir,
            'remisi_terakhir' => $remisiTerakhirInfo ? [
                'nama_event' => $remisiTerakhirInfo['nama_event'],
                'jenis' => $remisiTerakhirInfo['jenis'],
                'tahun' => $remisiTerakhirInfo['tahun'],
                'tanggal' => $remisiTerakhirInfo['tanggal'],
                'tanggal_indo' => self::formatTanggalIndonesia($tglRemisiTerakhir),
                'tanggal_numeric' => self::formatTanggalNumeric($tglRemisiTerakhir),
                'besaran_hari' => $remisiTerakhirInfo['besaran_hari'],
                'besaran_teks' => $remisiTerakhirInfo['besaran_teks'],
            ] : null,
            'tgl_usul_murni' => $tglUsulMurni->toDateString(),
            'tgl_usul_murni_indo' => self::formatTanggalIndonesia($tglUsulMurni),
            'tgl_usul_murni_numeric' => self::formatTanggalNumeric($tglUsulMurni),
            'opsi_1_pra_remisi' => [
                'label' => 'Opsi 1: Usulan Berdasarkan Remisi Berjalan (Sebelum Remisi Terakhir)',
                'tgl_2_3' => $tgl23PraRemisi->toDateString(),
                'tgl_2_3_indo' => self::formatTanggalIndonesia($tgl23PraRemisi),
                'tgl_2_3_numeric' => self::formatTanggalNumeric($tgl23PraRemisi),
                'tgl_usul' => $tglUsulOpsi1->toDateString(),
                'tgl_usul_indo' => self::formatTanggalIndonesia($tglUsulOpsi1),
                'tgl_usul_numeric' => self::formatTanggalNumeric($tglUsulOpsi1),
                'deskripsi' => 'Pengusulan diajukan H-60 sebelum 2/3 berjalan, menggunakan SK remisi yang sudah definitif tanpa menunggu remisi berikutnya.',
            ],
            'opsi_2_pasca_remisi' => [
                'label' => 'Opsi 2: Usulan Pasca-Terbit SK Remisi Terakhir (Hari H SK)',
                'tgl_2_3' => $tgl23Definitif->toDateString(),
                'tgl_2_3_indo' => self::formatTanggalIndonesia($tgl23Definitif),
                'tgl_2_3_numeric' => self::formatTanggalNumeric($tgl23Definitif),
                'tgl_usul' => $tglUsulOpsi2->toDateString(),
                'tgl_usul_indo' => self::formatTanggalIndonesia($tglUsulOpsi2),
                'tgl_usul_numeric' => self::formatTanggalNumeric($tglUsulOpsi2),
                'is_disesuaikan_ke_tgl_remisi' => $hasBenturanRemisiTerakhir,
                'deskripsi' => $hasBenturanRemisiTerakhir
                    ? "Pengusulan diajukan tepat pada hari H pemberian remisi terakhir ({$this->formatTanggalIndonesia($tglRemisiTerakhir)}) karena SK baru terbit pada tanggal tersebut."
                    : 'Pengusulan diajukan normal H-60 hari sebelum 2/3 definitif.',
            ],
        ];

        $formatVonis = [];
        if ($tahun > 0) {
            $formatVonis[] = "{$tahun} Tahun";
        }
        if ($bulan > 0) {
            $formatVonis[] = "{$bulan} Bulan";
        }
        if ($hari > 0) {
            $formatVonis[] = "{$hari} Hari";
        }

        $labelTambahanParts = [];
        if (! empty($pemukaTahunClean)) {
            $labelTambahanParts[] = 'Pemuka (+1/3 RU: Th ke-'.implode(', ', $pemukaTahunClean).')';
        } elseif ($kategoriTambahan === 'pemuka' && empty($donorTahunClean)) {
            $thnInfo = $tahunMulaiTambahan > 1 ? " (Mulai Tahun ke-{$tahunMulaiTambahan})" : '';
            $labelTambahanParts[] = "Pemuka Pembinaan Lapas (+1/3 RU){$thnInfo}";
        }

        if (! empty($donorTahunClean)) {
            $labelTambahanParts[] = 'Donor Darah / Jasa Negara (+1/2 RU: Th ke-'.implode(', ', $donorTahunClean).')';
        } elseif ($kategoriTambahan === 'jasa_negara' && empty($pemukaTahunClean)) {
            $thnInfo = $tahunMulaiTambahan > 1 ? " (Mulai Tahun ke-{$tahunMulaiTambahan})" : '';
            $labelTambahanParts[] = "Jasa Negara / Kemanusiaan (+1/2 RU){$thnInfo}";
        }

        $labelKategoriTambahan = ! empty($labelTambahanParts)
            ? implode(' & ', $labelTambahanParts)
            : 'Reguler (Tanpa Remisi Tambahan)';

        $sisaPidanaPascaPutusanTeks = '-';
        $sisaHariPascaPutusan = null;
        if ($tglPutusan) {
            $sisaHariPascaPutusan = max(0, $totalHariVonis - $masaTahananHari);
            $sisaPidanaPascaPutusanTeks = self::formatHariKeTeks($sisaHariPascaPutusan);
        }

        $dendaClean = (float) preg_replace('/[^0-9]/', '', (string) $dendaNominal);
        $subsiderStatus = strtolower(trim($subsiderStatus)) === 'sb' ? 'sb' : 'bb';
        $subTahun = max(0, $subTahun);
        $subBulan = max(0, $subBulan);
        $subHari = max(0, $subHari);
        $hasSubsider = ($subTahun > 0 || $subBulan > 0 || $subHari > 0);
        $hasDenda = ($dendaClean > 0);
        $subsiderTeks = self::formatDurasiTeks($subTahun, $subBulan, $subHari);
        $totalHariSubsider = ($subTahun * 360) + ($subBulan * 30) + $subHari;

        $dendaNominalFormatted = $hasDenda ? 'Rp '.number_format($dendaClean, 0, ',', '.').',-' : '-';
        $statusLabel = $subsiderStatus === 'sb' ? 'Sudah Bayar / SB' : 'Belum Bayar / BB';

        if ($hasDenda && $hasSubsider) {
            $subsiderIdentitasTeks = "{$dendaNominalFormatted} Subsidair {$subsiderTeks} Kurungan ({$statusLabel})";
        } elseif ($hasDenda) {
            $subsiderIdentitasTeks = "{$dendaNominalFormatted} ({$statusLabel})";
        } elseif ($hasSubsider) {
            $subsiderIdentitasTeks = "{$subsiderTeks} Kurungan ({$statusLabel})";
        } else {
            $subsiderIdentitasTeks = '-';
        }

        $isSubsiderAktifBb = $hasSubsider && ($subsiderStatus === 'bb');

        $tglEkspirasiSubsiderSebelumRemisi = $isSubsiderAktifBb
            ? self::tambahDurasiVonis($tglEkspirasiMurni, $subTahun, $subBulan, $subHari)
            : null;

        $tglEkspirasiSubsiderDefinitif = $isSubsiderAktifBb
            ? self::tambahDurasiVonis($tglEkspirasiDefinitif, $subTahun, $subBulan, $subHari)
            : null;

        $tgl23SubsiderDefinitif = $isSubsiderAktifBb
            ? self::tambahDurasiVonis($tgl23Definitif, $subTahun, $subBulan, $subHari)
            : null;

        $tglEkspirasiAwal = $tglPutusan
            ? self::tambahDurasiVonis($tglPutusan, $tahun, $bulan, $hari)
            : $tglEkspirasiMurni;

        $totalBulanVonis = ($tahun * 12) + $bulan;
        $isCb = ($totalBulanVonis < 18) || ($totalBulanVonis === 18 && $hari === 0);
        $judulProgram = $isCb ? 'PERHITUNGAN CUTI BERSYARAT (CB)' : 'PERHITUNGAN PEMBEBASAN BERSYARAT (PB)';
        $kodeProgram = $isCb ? 'CB' : 'PB';

        [$thn12, $bln12, $hri12] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 2);
        $durasi12Teks = self::formatDurasiTeks($thn12, $bln12, $hri12);

        [$thn23, $bln23, $hri23] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 2, 3);
        $durasi23Teks = self::formatDurasiTeks($thn23, $bln23, $hri23);

        $detailTahap13 = self::hitungPentahapanSetelahRemisi($tglAwal, $tahun, $bulan, $hari, 1, 3, $totalHariRemisi, $tglAwal);
        $detailTahap12 = self::hitungPentahapanSetelahRemisi($tglAwal, $tahun, $bulan, $hari, 1, 2, $totalHariRemisi, $tglMin12Base);
        $detailTahap23 = self::hitungPentahapanSetelahRemisi($tglAwal, $tahun, $bulan, $hari, 2, 3, $totalHariRemisi, $tglMinIntegrasiBase);

        $o1RemisiHari = $milestonesSebelumRemisiTerakhir ? ($milestonesSebelumRemisiTerakhir['total_remisi_hari'] ?? 0) : 0;
        $o1RemisiTeks = self::formatRemisiBulanHari($o1RemisiHari);
        $detailTahap23Opsi1 = self::hitungPentahapanSetelahRemisi($tglAwal, $tahun, $bulan, $hari, 2, 3, $o1RemisiHari, $tglMinIntegrasiBase);
        $tgl23SubsiderOpsi1 = $isSubsiderAktifBb ? self::tambahDurasiVonis($tgl23PraRemisi, $subTahun, $subBulan, $subHari) : null;

        $opsiPengusulan['opsi_1_pra_remisi']['tgl_2_3_subsider'] = $tgl23SubsiderOpsi1?->toDateString();
        $opsiPengusulan['opsi_1_pra_remisi']['tgl_2_3_subsider_indo'] = $tgl23SubsiderOpsi1 ? self::formatTanggalIndonesia($tgl23SubsiderOpsi1) : null;
        $opsiPengusulan['opsi_1_pra_remisi']['tgl_2_3_subsider_numeric'] = $tgl23SubsiderOpsi1 ? self::formatTanggalNumeric($tgl23SubsiderOpsi1) : null;
        $opsiPengusulan['opsi_1_pra_remisi']['remisi_hari'] = $o1RemisiHari;
        $opsiPengusulan['opsi_1_pra_remisi']['remisi_teks'] = $o1RemisiTeks;
        $opsiPengusulan['opsi_1_pra_remisi']['pidana_bulan_teks'] = $detailTahap23Opsi1['pidana_bulan_teks'];
        $opsiPengusulan['opsi_1_pra_remisi']['fraksi_2_3_bulan_teks'] = $detailTahap23Opsi1['fraksi_bulan_teks'];
        $opsiPengusulan['opsi_1_pra_remisi']['selisih_2_3_bulan_teks'] = $detailTahap23Opsi1['selisih_bulan_teks'];
        $opsiPengusulan['opsi_1_pra_remisi']['selisih_2_3_tahun_teks'] = $detailTahap23Opsi1['selisih_tahun_teks'];

        $opsiPengusulan['opsi_2_pasca_remisi']['tgl_2_3_subsider'] = $tgl23SubsiderDefinitif?->toDateString();
        $opsiPengusulan['opsi_2_pasca_remisi']['tgl_2_3_subsider_indo'] = $tgl23SubsiderDefinitif ? self::formatTanggalIndonesia($tgl23SubsiderDefinitif) : null;
        $opsiPengusulan['opsi_2_pasca_remisi']['tgl_2_3_subsider_numeric'] = $tgl23SubsiderDefinitif ? self::formatTanggalNumeric($tgl23SubsiderDefinitif) : null;
        $opsiPengusulan['opsi_2_pasca_remisi']['remisi_hari'] = $totalHariRemisi;
        $opsiPengusulan['opsi_2_pasca_remisi']['remisi_teks'] = self::formatRemisiBulanHari($totalHariRemisi);
        $opsiPengusulan['opsi_2_pasca_remisi']['pidana_bulan_teks'] = $detailTahap23['pidana_bulan_teks'];
        $opsiPengusulan['opsi_2_pasca_remisi']['fraksi_2_3_bulan_teks'] = $detailTahap23['fraksi_bulan_teks'];
        $opsiPengusulan['opsi_2_pasca_remisi']['selisih_2_3_bulan_teks'] = $detailTahap23['selisih_bulan_teks'];
        $opsiPengusulan['opsi_2_pasca_remisi']['selisih_2_3_tahun_teks'] = $detailTahap23['selisih_tahun_teks'];

        $result = [
            'mode' => 'simulasi_remisi',
            'target_basis' => $targetBasis,
            'target_basis_label' => $targetBasis === '2_3' ? 'Standar 2/3 Masa Pidana' : 'Sampai Ekspirasi Sebelum Remisi',
            'nama_wbp' => $namaWbp,
            'no_register' => $noRegister,
            'perkara_pasal' => $perkaraPasal,
            'tgl_awal' => $tglAwalFisik->toDateString(),
            'tgl_mulai_ditahan_fisik' => $tglAwalFisik->toDateString(),
            'tgl_awal_fisik' => $tglAwalFisik->toDateString(),
            'tgl_mulai_ditahan_efektif' => $tglAwal->toDateString(),
            'tgl_awal_efektif' => $tglAwal->toDateString(),
            'has_riwayat_penahanan' => $hasRiwayatPenahanan,
            'riwayat_penahanan' => $riwayatData['items'] ?? [],
            'total_hari_kalender_tahanan' => $riwayatData['total_hari_kalender'] ?? $masaTahananHari,
            'total_hari_penahanan_kalender' => $riwayatData['total_hari_penahanan_kalender'] ?? $masaTahananHari,
            'total_hari_jeda_tahanan' => $riwayatData['total_hari_jeda'] ?? 0,
            'total_hari_luar_upt' => $totalHariLuarUpt,
            'total_hari_luar_upt_pra' => $totalHariLuarUptPra,
            'total_hari_luar_upt_pasca' => $totalHariLuarUptPasca,
            'tgl_putusan' => $tglPutusan ? $tglPutusan->toDateString() : null,
            'masa_tahanan_hari' => $masaTahananHari,
            'masa_tahanan_teks' => $masaTahananTeks,
            'sisa_hari_pasca_putusan' => $sisaHariPascaPutusan,
            'sisa_pidana_pasca_putusan_teks' => $sisaPidanaPascaPutusanTeks,
            'lama_pidana_teks' => implode(', ', $formatVonis) ?: '0 Hari',
            'pidana_bulan_teks' => $detailTahap12['pidana_bulan_teks'],
            'durasi_1_2_teks' => $durasi12Teks,
            'durasi_2_3_teks' => $durasi23Teks,
            'fraksi_1_3_bulan_teks' => $detailTahap13['fraksi_bulan_teks'],
            'selisih_1_3_bulan_teks' => $detailTahap13['selisih_bulan_teks'],
            'selisih_1_3_tahun_teks' => $detailTahap13['selisih_tahun_teks'],
            'fraksi_1_2_bulan_teks' => $detailTahap12['fraksi_bulan_teks'],
            'selisih_1_2_bulan_teks' => $detailTahap12['selisih_bulan_teks'],
            'selisih_1_2_tahun_teks' => $detailTahap12['selisih_tahun_teks'],
            'fraksi_2_3_bulan_teks' => $detailTahap23['fraksi_bulan_teks'],
            'selisih_2_3_bulan_teks' => $detailTahap23['selisih_bulan_teks'],
            'selisih_2_3_tahun_teks' => $detailTahap23['selisih_tahun_teks'],
            'tahun' => $tahun,
            'bulan' => $bulan,
            'hari' => $hari,
            'total_hari_vonis' => $totalHariVonis,
            'has_subsider' => $hasSubsider,
            'has_denda' => $hasDenda,
            'denda_nominal' => $dendaClean,
            'denda_nominal_formatted' => $dendaNominalFormatted,
            'subsider_status' => $subsiderStatus,
            'subsider_identitas_teks' => $subsiderIdentitasTeks,
            'judul_program' => $judulProgram,
            'kode_program' => $kodeProgram,
            'sub_tahun' => $subTahun,
            'sub_bulan' => $subBulan,
            'sub_hari' => $subHari,
            'subsider_teks' => $subsiderTeks,
            'total_hari_subsider' => $totalHariSubsider,
            'agama' => ucfirst($agama),
            'jenis_program' => $jenisProgram,
            'nama_program' => $namaProgram,
            'keterangan_program' => $keteranganProgram,
            'include_dasawarsa' => $includeDasawarsa,
            'kategori_tambahan' => $kategoriTambahan,
            'tahun_mulai_tambahan' => $tahunMulaiTambahan,
            'pemuka_tahun_list' => $pemukaTahunClean,
            'donor_tahun_list' => $donorTahunClean,
            'label_kategori_tambahan' => $labelKategoriTambahan,
            'remisi_awal_hari' => $remisiAwalHari,
            'total_proyeksi_remisi_hari' => $totalHariRemisi,
            'total_proyeksi_remisi_teks' => self::formatRemisiBulanHari($totalHariRemisi),
            'status_pengusulan' => $statusPengusulan,
            'milestones' => [
                'ekspirasi_sebelum_remisi' => $tglEkspirasiMurni->toDateString(),
                'ekspirasi_murni' => $tglEkspirasiMurni->toDateString(), // alias
                'tgl_putusan' => $tglPutusan ? $tglPutusan->toDateString() : null,
                'tgl_1_3_sebelum_remisi' => $tgl13Murni->toDateString(),
                'tgl_1_3_murni' => $tgl13Murni->toDateString(), // alias
                'tgl_1_2_sebelum_remisi' => $tgl12Murni->toDateString(),
                'tgl_1_2_murni' => $tgl12Murni->toDateString(), // alias
                'tgl_2_3_sebelum_remisi' => $tgl23Murni->toDateString(),
                'tgl_2_3_murni' => $tgl23Murni->toDateString(), // alias
                'tgl_1_3_definitif' => $tgl13Definitif->toDateString(),
                'tgl_1_2_definitif' => $tgl12Definitif->toDateString(),
                'tgl_2_3_definitif' => $tgl23Definitif->toDateString(),
                'tgl_2_3_subsider_definitif' => $tgl23SubsiderDefinitif?->toDateString(),
                'tgl_mulai_pengusulan' => $tglMulaiPengusulan->toDateString(),
                'tgl_mulai_pengusulan_murni' => $tglUsulMurni->toDateString(),
                'tgl_mulai_pengusulan_opsi_1' => $tglUsulOpsi1->toDateString(),
                'tgl_mulai_pengusulan_opsi_2' => $tglUsulOpsi2->toDateString(),
                'tgl_2_3_sebelum_remisi_terakhir' => $tgl23PraRemisi->toDateString(),
                'ekspirasi_definitif' => $tglEkspirasiDefinitif->toDateString(),
                'ekspirasi_subsider_sebelum_remisi' => $tglEkspirasiSubsiderSebelumRemisi?->toDateString(),
                'ekspirasi_subsider_murni' => $tglEkspirasiSubsiderSebelumRemisi?->toDateString(), // alias
                'ekspirasi_subsider_definitif' => $tglEkspirasiSubsiderDefinitif?->toDateString(),
            ],
            'formatted' => [
                'tgl_awal' => self::formatTanggalIndonesia($tglAwalFisik),
                'tgl_mulai_ditahan_fisik' => self::formatTanggalIndonesia($tglAwalFisik),
                'tgl_mulai_ditahan_efektif' => self::formatTanggalIndonesia($tglAwal),
                'total_hari_kalender_tahanan' => ($riwayatData['total_hari_kalender'] ?? $masaTahananHari).' Hari',
                'total_hari_jeda_tahanan' => ($riwayatData['total_hari_jeda'] ?? 0).' Hari',
                'tgl_putusan' => $tglPutusan ? self::formatTanggalIndonesia($tglPutusan) : '-',
                'masa_tahanan' => $masaTahananTeks,
                'sisa_pidana_pasca_putusan' => $sisaPidanaPascaPutusanTeks,
                'ekspirasi_sebelum_remisi' => self::formatTanggalIndonesia($tglEkspirasiMurni),
                'ekspirasi_murni' => self::formatTanggalIndonesia($tglEkspirasiMurni), // alias
                'tgl_1_3_sebelum_remisi' => self::formatTanggalIndonesia($tgl13Murni),
                'tgl_1_3_murni' => self::formatTanggalIndonesia($tgl13Murni), // alias
                'tgl_1_2_sebelum_remisi' => self::formatTanggalIndonesia($tgl12Murni),
                'tgl_1_2_murni' => self::formatTanggalIndonesia($tgl12Murni), // alias
                'tgl_2_3_sebelum_remisi' => self::formatTanggalIndonesia($tgl23Murni),
                'tgl_2_3_murni' => self::formatTanggalIndonesia($tgl23Murni), // alias
                'tgl_1_3_definitif' => self::formatTanggalIndonesia($tgl13Definitif),
                'tgl_1_2_definitif' => self::formatTanggalIndonesia($tgl12Definitif),
                'tgl_2_3_definitif' => self::formatTanggalIndonesia($tgl23Definitif),
                'tgl_2_3_subsider_definitif' => $tgl23SubsiderDefinitif ? self::formatTanggalIndonesia($tgl23SubsiderDefinitif) : '-',
                'tgl_mulai_pengusulan' => self::formatTanggalIndonesia($tglMulaiPengusulan),
                'tgl_mulai_pengusulan_murni' => self::formatTanggalIndonesia($tglUsulMurni),
                'tgl_mulai_pengusulan_opsi_1' => self::formatTanggalIndonesia($tglUsulOpsi1),
                'tgl_mulai_pengusulan_opsi_2' => self::formatTanggalIndonesia($tglUsulOpsi2),
                'ekspirasi_definitif' => self::formatTanggalIndonesia($tglEkspirasiDefinitif),
                'ekspirasi_subsider_sebelum_remisi' => $tglEkspirasiSubsiderSebelumRemisi ? self::formatTanggalIndonesia($tglEkspirasiSubsiderSebelumRemisi) : '-',
                'ekspirasi_subsider_murni' => $tglEkspirasiSubsiderSebelumRemisi ? self::formatTanggalIndonesia($tglEkspirasiSubsiderSebelumRemisi) : '-', // alias
                'ekspirasi_subsider_definitif' => $tglEkspirasiSubsiderDefinitif ? self::formatTanggalIndonesia($tglEkspirasiSubsiderDefinitif) : '-',
                'durasi_1_2' => $durasi12Teks,
                'durasi_2_3' => $durasi23Teks,
                'subsider_teks' => $subsiderTeks,
                'subsider_identitas_teks' => $subsiderIdentitasTeks,
            ],
            'numeric' => [
                'tgl_awal' => self::formatTanggalNumeric($tglAwalFisik),
                'tgl_mulai_ditahan_fisik' => self::formatTanggalNumeric($tglAwalFisik),
                'tgl_mulai_ditahan_efektif' => self::formatTanggalNumeric($tglAwal),
                'tgl_putusan' => $tglPutusan ? self::formatTanggalNumeric($tglPutusan) : '-',
                'ekspirasi_awal' => self::formatTanggalNumeric($tglEkspirasiAwal),
                'ekspirasi_sebelum_remisi' => self::formatTanggalNumeric($tglEkspirasiMurni),
                'ekspirasi_subsider_sebelum_remisi' => $tglEkspirasiSubsiderSebelumRemisi ? self::formatTanggalNumeric($tglEkspirasiSubsiderSebelumRemisi) : '-',
                'tgl_1_3_definitif' => self::formatTanggalNumeric($tgl13Definitif),
                'tgl_1_2_definitif' => self::formatTanggalNumeric($tgl12Definitif),
                'tgl_2_3_definitif' => self::formatTanggalNumeric($tgl23Definitif),
                'tgl_2_3_subsider_definitif' => $tgl23SubsiderDefinitif ? self::formatTanggalNumeric($tgl23SubsiderDefinitif) : '-',
                'ekspirasi_definitif' => self::formatTanggalNumeric($tglEkspirasiDefinitif),
                'ekspirasi_subsider_definitif' => $tglEkspirasiSubsiderDefinitif ? self::formatTanggalNumeric($tglEkspirasiSubsiderDefinitif) : '-',
                'tgl_mulai_pengusulan' => self::formatTanggalNumeric($tglMulaiPengusulan),
                'tgl_mulai_pengusulan_murni' => self::formatTanggalNumeric($tglUsulMurni),
                'tgl_mulai_pengusulan_opsi_1' => self::formatTanggalNumeric($tglUsulOpsi1),
                'tgl_mulai_pengusulan_opsi_2' => self::formatTanggalNumeric($tglUsulOpsi2),
            ],
            'opsi_pengusulan' => $opsiPengusulan,
            'proyeksi_ledger' => $ledger,
        ];

        $result['hitungan_manual_teks'] = self::generateHitunganManualTeks($result);
        $result['hitungan_manual_html'] = self::renderHitunganManualHtml($result);

        return $result;
    }

    /**
     * Konversi jumlah hari baku ke teks deskriptif (Tahun, Bulan, Hari)
     */
    public static function formatHariKeTeks(int $totalHari): string
    {
        if ($totalHari <= 0) {
            return '0 Hari';
        }

        $thn = (int) floor($totalHari / 360);
        $sisaThn = $totalHari % 360;
        $bln = (int) floor($sisaThn / 30);
        $hri = $sisaThn % 30;

        $parts = [];
        if ($thn > 0) {
            $parts[] = "{$thn} Tahun";
        }
        if ($bln > 0) {
            $parts[] = "{$bln} Bulan";
        }
        if ($hri > 0) {
            $parts[] = "{$hri} Hari";
        }

        return implode(', ', $parts) ?: '0 Hari';
    }

    /**
     * Konversi durasi tahun, bulan, hari ke format teks deskriptif
     */
    public static function formatDurasiTeks(int $tahun, int $bulan, int $hari): string
    {
        $parts = [];
        if ($tahun > 0) {
            $parts[] = "{$tahun} Tahun";
        }
        if ($bulan > 0) {
            $parts[] = "{$bulan} Bulan";
        }
        if ($hari > 0) {
            $parts[] = "{$hari} Hari";
        }

        return implode(', ', $parts) ?: '0 Hari';
    }

    /**
     * Hasilkan rincian teks hitungan manual lengkap beserta rumus dan substitusi angka.
     * Mengikuti kaidah penulisan matematika dengan tanda sama dengan (=) yang lurus dan rapi.
     */
    public static function generateHitunganManualTeks(array $data): string
    {
        $judulProgram = $data['judul_program'] ?? 'PERHITUNGAN PEMBEBASAN BERSYARAT (PB)';
        $namaWbp = $data['nama_wbp'] ?? 'Narapidana';
        $noRegister = $data['no_register'] ?? '-';
        $perkaraPasal = $data['perkara_pasal'] ?? '-';
        $lamaVonis = $data['lama_pidana_teks'] ?? '-';
        $subsiderIdentitas = $data['formatted']['subsider_identitas_teks'] ?? ($data['subsider_identitas_teks'] ?? '-');
        $subsiderTeks = $data['formatted']['subsider_teks'] ?? ($data['subsider_teks'] ?? '0 Hari');
        $hasSubsiderBb = ! empty($data['has_subsider']) && (($data['subsider_status'] ?? 'bb') === 'bb');

        $num = $data['numeric'] ?? [];
        $fmt = $data['formatted'] ?? [];

        $tglAwalNum = $num['tgl_awal'] ?? '-';
        $tglAwalIndo = $fmt['tgl_awal'] ?? '-';

        $tglPutusanNum = $num['tgl_putusan'] ?? '-';
        $tglPutusanIndo = $fmt['tgl_putusan'] ?? '-';

        $masaTahananTeks = $fmt['masa_tahanan'] ?? '-';
        $masaTahananHari = (int) ($data['masa_tahanan_hari'] ?? 0);

        $ekspirasiAwalNum = $num['ekspirasi_awal'] ?? ($num['ekspirasi_sebelum_remisi'] ?? '-');
        $ekspirasiAwalIndo = $fmt['ekspirasi_sebelum_remisi'] ?? '-';

        $ekspirasiSebelumRemisiNum = $num['ekspirasi_sebelum_remisi'] ?? '-';
        $ekspirasiSebelumRemisiIndo = $fmt['ekspirasi_sebelum_remisi'] ?? '-';

        $ekspirasiSubSebelumRemisiNum = $num['ekspirasi_subsider_sebelum_remisi'] ?? '-';
        $ekspirasiSubSebelumRemisiIndo = $fmt['ekspirasi_subsider_sebelum_remisi'] ?? '-';

        $totalRemisiHari = (int) ($data['total_proyeksi_remisi_hari'] ?? 0);
        $totalRemisiTeks = $data['total_proyeksi_remisi_teks'] ?? '0 Hari';

        $tgl12DefNum = $num['tgl_1_2_definitif'] ?? '-';
        $tgl12DefIndo = $fmt['tgl_1_2_definitif'] ?? '-';

        $tgl23DefNum = $num['tgl_2_3_definitif'] ?? '-';
        $tgl23DefIndo = $fmt['tgl_2_3_definitif'] ?? '-';

        $tgl23SubDefNum = $num['tgl_2_3_subsider_definitif'] ?? '-';
        $tgl23SubDefIndo = $fmt['tgl_2_3_subsider_definitif'] ?? '-';

        $ekspirasiDefNum = $num['ekspirasi_definitif'] ?? '-';
        $ekspirasiDefIndo = $fmt['ekspirasi_definitif'] ?? '-';

        $ekspirasiSubDefNum = $num['ekspirasi_subsider_definitif'] ?? '-';
        $ekspirasiSubDefIndo = $fmt['ekspirasi_subsider_definitif'] ?? '-';

        $tglUsulNum = $num['tgl_mulai_pengusulan'] ?? '-';
        $tglUsulIndo = $fmt['tgl_mulai_pengusulan'] ?? '-';

        $thn = (int) ($data['tahun'] ?? 0);
        $bln = (int) ($data['bulan'] ?? 0);
        $hri = (int) ($data['hari'] ?? 0);
        $tglAwalCarbon = ! empty($data['tgl_awal']) ? Carbon::parse($data['tgl_awal'])->startOfDay() : Carbon::today();

        $calc12 = self::hitungPentahapanSetelahRemisi($tglAwalCarbon, $thn, $bln, $hri, 1, 2, $totalRemisiHari);
        $calc23 = self::hitungPentahapanSetelahRemisi($tglAwalCarbon, $thn, $bln, $hri, 2, 3, $totalRemisiHari);

        $pidanaBulanTeks = $data['pidana_bulan_teks'] ?? $calc12['pidana_bulan_teks'];
        $fraksi12BulanTeks = $data['fraksi_1_2_bulan_teks'] ?? $calc12['fraksi_bulan_teks'];
        $selisih12BulanTeks = $data['selisih_1_2_bulan_teks'] ?? $calc12['selisih_bulan_teks'];
        $selisih12TahunTeks = $data['selisih_1_2_tahun_teks'] ?? $calc12['selisih_tahun_teks'];

        $fraksi23BulanTeks = $data['fraksi_2_3_bulan_teks'] ?? $calc23['fraksi_bulan_teks'];
        $selisih23BulanTeks = $data['selisih_2_3_bulan_teks'] ?? $calc23['selisih_bulan_teks'];
        $selisih23TahunTeks = $data['selisih_2_3_tahun_teks'] ?? $calc23['selisih_tahun_teks'];

        $teks = "{$judulProgram}\n";
        $teks .= "Sultan Banten - Kantor Wilayah Ditjenpas Banten\n";
        $teks .= "========================================================\n\n";

        $teks .= "IDENTITAS WARGA BINAAN PEMASYARAKATAN\n";
        $teks .= "--------------------------------------------------------\n";
        $teks .= "Nama                : {$namaWbp}\n";
        $teks .= "Nomor Register      : {$noRegister}\n";
        $teks .= "Perkara / Pasal     : {$perkaraPasal}\n";
        $teks .= "Tgl. Mulai Ditahan  : {$tglAwalIndo}\n";
        $teks .= "Tgl. Putusan        : {$tglPutusanIndo}\n";
        $teks .= "Pidana Pokok        : {$lamaVonis}\n";
        $teks .= "Pidana Subsider     : {$subsiderIdentitas}\n\n";

        $labelTambahanManual = $data['label_kategori_tambahan'] ?? 'Reguler (Tanpa Remisi Tambahan)';
        if (($data['kategori_tambahan'] ?? 'none') !== 'none') {
            $teks .= "Remisi Tambahan     : {$labelTambahanManual}\n";
        }
        $teks .= "\n";

        if (! empty($data['tgl_putusan'])) {
            $teks .= "A. POTONGAN TAHANAN (POTAH)\n";
            if (! empty($data['has_riwayat_penahanan']) && ! empty($data['riwayat_penahanan'])) {
                $teks .= "   Rincian Riwayat Penahanan & Hari di Luar UPT (Ps. 22 & 31 KUHAP):\n";
                $teks .= "   -----------------------------------------------------------------------------------------\n";
                $teks .= "   No | Status Penahanan              | Periode                 | Hari | Rasio | Potah\n";
                $teks .= "   -----------------------------------------------------------------------------------------\n";
                foreach ($data['riwayat_penahanan'] as $r) {
                    $noStr = str_pad((string) $r['no'], 2, ' ', STR_PAD_RIGHT);
                    $jenisStr = str_pad(mb_substr($r['label_jenis'], 0, 28), 28, ' ', STR_PAD_RIGHT);
                    $periodeStr = str_pad($r['periode_teks'], 23, ' ', STR_PAD_RIGHT);
                    $hariStr = str_pad((string) $r['hari_kalender'], 4, ' ', STR_PAD_LEFT);
                    $rasioStr = str_pad($r['rasio'], 5, ' ', STR_PAD_RIGHT);
                    $potahStr = str_pad($r['hari_potah'].' hr', 6, ' ', STR_PAD_LEFT);
                    $teks .= "   {$noStr} | {$jenisStr} | {$periodeStr} | {$hariStr} | {$rasioStr} | {$potahStr}\n";
                }
                $teks .= "   -----------------------------------------------------------------------------------------\n";
                $totalKal = $data['total_hari_kalender_tahanan'] ?? 0;
                $totalLuarUpt = (int) ($data['total_hari_luar_upt'] ?? ($data['total_hari_jeda_tahanan'] ?? 0));
                $totalLuarUptPasca = (int) ($data['total_hari_luar_upt_pasca'] ?? 0);
                $tglAwalEfektifNum = $num['tgl_mulai_ditahan_efektif'] ?? '-';
                $tglAwalEfektifIndo = $fmt['tgl_mulai_ditahan_efektif'] ?? '-';
                $teks .= "   Total Hari Kalender        = {$totalKal} Hari\n";
                if ($totalLuarUpt > 0) {
                    $teks .= "   Total Hari di Luar UPT     = {$totalLuarUpt} Hari (Eksklusif Opsi A, 0 Hari Potah)\n";
                    if ($totalLuarUptPasca > 0) {
                        $teks .= "   Luar UPT Pasca-Putusan     = {$totalLuarUptPasca} Hari (Menggeser Ekspirasi & Pentahapan +{$totalLuarUptPasca} Hari)\n";
                    }
                }
                $teks .= "   Total Potongan Tahanan     = {$masaTahananHari} Hari ({$masaTahananTeks})\n";
                $teks .= "   Tgl. Mulai Ditahan Efektif = Tgl. Putusan - Total Potah\n";
                $teks .= "                              = {$tglPutusanNum} - {$masaTahananHari} Hari\n";
                $teks .= "                              = {$tglAwalEfektifNum} ({$tglAwalEfektifIndo})\n\n";
            } else {
                $teks .= "   Potah = Tgl. Mulai Ditahan - Tgl. Putusan\n";
                $teks .= "         = {$tglAwalNum} - {$tglPutusanNum}\n";
                $teks .= "         = {$masaTahananTeks}\n\n";
            }
        }

        $totalLuarUptPascaB = (int) ($data['total_hari_luar_upt_pasca'] ?? 0);
        $teks .= "B. EKSPIRASI SEBELUM REMISI\n";
        if (! empty($data['tgl_putusan'])) {
            $teks .= "   Ekspirasi Awal  = Tgl. Putusan + Lama Pidana Pokok\n";
            $teks .= "                   = {$tglPutusanNum} + {$lamaVonis}\n";
            $teks .= "                   = {$ekspirasiAwalNum}\n";
            $teks .= "                   = {$ekspirasiAwalIndo}\n\n";

            if ($totalLuarUptPascaB > 0) {
                $teks .= "   Ekspirasi Akhir = Ekspirasi Awal - Potah + Hari di Luar UPT Pasca-Putusan\n";
                $teks .= "                   = {$ekspirasiAwalNum} - {$masaTahananTeks} + {$totalLuarUptPascaB} Hari\n";
            } else {
                $teks .= "   Ekspirasi Akhir = Ekspirasi Awal - Potah\n";
                $teks .= "                   = {$ekspirasiAwalNum} - {$masaTahananTeks}\n";
            }
            $teks .= "                   = {$ekspirasiSebelumRemisiNum}\n";
            $teks .= "                   = {$ekspirasiSebelumRemisiIndo}\n";
        } else {
            $teks .= "   Ekspirasi Akhir = Tgl. Mulai Ditahan + Lama Pidana Pokok\n";
            $teks .= "                   = {$tglAwalNum} + {$lamaVonis}\n";
            $teks .= "                   = {$ekspirasiSebelumRemisiNum}\n";
            $teks .= "                   = {$ekspirasiSebelumRemisiIndo}\n";
        }

        if ($hasSubsiderBb) {
            $teks .= "\n";
            $teks .= "   Ekspirasi (+Subsider BB) = Ekspirasi Akhir + Pidana Subsider\n";
            $teks .= "                            = {$ekspirasiSebelumRemisiNum} + {$subsiderTeks}\n";
            $teks .= "                            = {$ekspirasiSubSebelumRemisiNum}\n";
            $teks .= "                            = {$ekspirasiSubSebelumRemisiIndo}\n";
        }
        $teks .= "\n";

        $labelDitahan = ! empty($data['has_riwayat_penahanan']) ? 'Tgl. Mulai Ditahan (Efektif)' : 'Tgl. Mulai Ditahan';
        $tglDitahanAcuanNum = ! empty($data['has_riwayat_penahanan']) ? ($num['tgl_mulai_ditahan_efektif'] ?? $tglAwalNum) : $tglAwalNum;

        $teks .= "C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)\n";
        $teks .= "   • Jumlah Remisi yang diperoleh = {$totalRemisiTeks}\n\n";

        $teks .= "   • 1/2 Masa Pidana = {$labelDitahan} + ((1/2 x Masa Pidana) - Remisi)\n";
        $teks .= "                     = {$tglDitahanAcuanNum} + ((1/2 x {$lamaVonis}) - {$totalRemisiTeks})\n";
        $teks .= "                     = {$tglDitahanAcuanNum} + ((1/2 x {$pidanaBulanTeks}) - {$totalRemisiTeks})\n";
        $teks .= "                     = {$tglDitahanAcuanNum} + ({$fraksi12BulanTeks} - {$totalRemisiTeks})\n";
        $teks .= "                     = {$tglDitahanAcuanNum} + {$selisih12BulanTeks}\n";
        $teks .= "                     = {$tglDitahanAcuanNum} + {$selisih12TahunTeks}\n";
        $teks .= "                     = {$tgl12DefNum}\n";
        $teks .= "                     = {$tgl12DefIndo}\n\n";

        $opsi = $data['opsi_pengusulan'] ?? [];
        $hasBenturan = ! empty($opsi['has_benturan']);

        if ($hasBenturan) {
            $o1 = $opsi['opsi_1_pra_remisi'] ?? [];
            $o2 = $opsi['opsi_2_pasca_remisi'] ?? [];
            $remisiTerk = $opsi['remisi_terakhir'] ?? [];
            $remisiNama = $remisiTerk['nama_event'] ?? 'Remisi Terakhir';
            $remisiJenis = $remisiTerk['jenis'] ?? 'RK';
            $remisiTglIndo = $remisiTerk['tanggal_indo'] ?? '-';
            $tglUsulMurniNum = $num['tgl_mulai_pengusulan_murni'] ?? ($opsi['tgl_usul_murni_numeric'] ?? '-');
            $tglUsulMurniIndo = $data['formatted']['tgl_mulai_pengusulan_murni'] ?? ($opsi['tgl_usul_murni_indo'] ?? '-');

            $o1RemisiTeks = $o1['remisi_teks'] ?? '0 Hari';
            $o1SelisihBulanTeks = $o1['selisih_2_3_bulan_teks'] ?? $fraksi23BulanTeks;
            $o1SelisihTahunTeks = $o1['selisih_2_3_tahun_teks'] ?? $fraksi23BulanTeks;
            $o1Tgl23Num = $o1['tgl_2_3_numeric'] ?? '-';
            $o1Tgl23Indo = $o1['tgl_2_3_indo'] ?? '-';
            $o1TglUsulNum = $o1['tgl_usul_numeric'] ?? '-';
            $o1TglUsulIndo = $o1['tgl_usul_indo'] ?? '-';

            $o2TglUsulNum = $o2['tgl_usul_numeric'] ?? '-';
            $o2TglUsulIndo = $o2['tgl_usul_indo'] ?? '-';

            $teks .= "   • Tersedia 2 Opsi Perhitungan 2/3 Masa Pidana:\n\n";

            $teks .= "     a. Opsi 1 = 2/3 Sebelum Remisi {$remisiNama} ({$remisiJenis}):\n";
            $teks .= "        - Hitungan 2/3 Masa Pidana:\n";
            $teks .= "          = {$labelDitahan} + ((2/3 x Masa Pidana) - Remisi Berjalan)\n";
            $teks .= "          = {$tglDitahanAcuanNum} + ((2/3 x {$lamaVonis}) - {$o1RemisiTeks})\n";
            $teks .= "          = {$tglDitahanAcuanNum} + ((2/3 x {$pidanaBulanTeks}) - {$o1RemisiTeks})\n";
            $teks .= "          = {$tglDitahanAcuanNum} + ({$fraksi23BulanTeks} - {$o1RemisiTeks})\n";
            $teks .= "          = {$tglDitahanAcuanNum} + {$o1SelisihBulanTeks}\n";
            $teks .= "          = {$tglDitahanAcuanNum} + {$o1SelisihTahunTeks}\n";
            $teks .= "          = {$o1Tgl23Num}\n";
            $teks .= "          = {$o1Tgl23Indo}\n";
            if ($hasSubsiderBb) {
                $o1SubNum = $o1['tgl_2_3_subsider_numeric'] ?? '-';
                $o1SubIndo = $o1['tgl_2_3_subsider_indo'] ?? '-';
                $teks .= "          - 2/3 Masa Pidana (+Subsider BB) = {$o1Tgl23Num} + {$subsiderTeks} = {$o1SubNum} ({$o1SubIndo})\n";
            }
            $teks .= "        - Prediksi Tanggal Usulan Diusulkan (H-60 Hari):\n";
            $teks .= "          = Tgl. 2/3 Masa Pidana - 60 Hari\n";
            $teks .= "          = {$o1Tgl23Num} - 60 Hari\n";
            $teks .= "          = {$o1TglUsulNum} = {$o1TglUsulIndo}\n";
            $teks .= "          *Keterangan: Usulan diajukan lebih awal menggunakan SK remisi definitif yang sudah terbit di tangan tanpa menunggu SK remisi berikutnya.\n\n";

            $teks .= "     b. Opsi 2 = 2/3 Setelah Remisi {$remisiNama} ({$remisiJenis}):\n";
            $teks .= "        - Hitungan 2/3 Masa Pidana:\n";
            $teks .= "          = {$labelDitahan} + ((2/3 x Masa Pidana) - Remisi Definitif)\n";
            $teks .= "          = {$tglDitahanAcuanNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})\n";
            $teks .= "          = {$tglDitahanAcuanNum} + ((2/3 x {$pidanaBulanTeks}) - {$totalRemisiTeks})\n";
            $teks .= "          = {$tglDitahanAcuanNum} + ({$fraksi23BulanTeks} - {$totalRemisiTeks})\n";
            $teks .= "          = {$tglDitahanAcuanNum} + {$selisih23BulanTeks}\n";
            $teks .= "          = {$tglDitahanAcuanNum} + {$selisih23TahunTeks}\n";
            $teks .= "          = {$tgl23DefNum}\n";
            $teks .= "          = {$tgl23DefIndo}\n";
            if ($hasSubsiderBb) {
                $teks .= "          - 2/3 Masa Pidana (+Subsider BB) = {$tgl23DefNum} + {$subsiderTeks} = {$tgl23SubDefNum} ({$tgl23SubDefIndo})\n";
            }
            $teks .= "        - Prediksi Tanggal Usulan Diusulkan:\n";
            $teks .= "          {$tgl23DefNum} - 60 hr = {$tglUsulMurniNum} < tgl remisi {$remisiTglIndo}\n";
            $teks .= "          *Catatan: Pada sistem SDP, usulan tidak dapat kurang dari tgl remisi, disesuaikan tepat Hari H SK {$remisiNama} = {$o2TglUsulNum} = {$o2TglUsulIndo}.\n";
        } else {
            $teks .= "   • 2/3 Masa Pidana = {$labelDitahan} + ((2/3 x Masa Pidana) - Remisi)\n";
            $teks .= "                     = {$tglDitahanAcuanNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})\n";
            $teks .= "                     = {$tglDitahanAcuanNum} + ((2/3 x {$pidanaBulanTeks}) - {$totalRemisiTeks})\n";
            $teks .= "                     = {$tglDitahanAcuanNum} + ({$fraksi23BulanTeks} - {$totalRemisiTeks})\n";
            $teks .= "                     = {$tglDitahanAcuanNum} + {$selisih23BulanTeks}\n";
            $teks .= "                     = {$tglDitahanAcuanNum} + {$selisih23TahunTeks}\n";
            $teks .= "                     = {$tgl23DefNum}\n";
            $teks .= "                     = {$tgl23DefIndo}\n";

            if ($hasSubsiderBb) {
                $teks .= "\n";
                $teks .= "   • 2/3 Masa Pidana (+Subsider BB) = 2/3 Masa Pidana + Pidana Subsider\n";
                $teks .= "                                    = {$tgl23DefNum} + {$subsiderTeks}\n";
                $teks .= "                                    = {$tgl23SubDefNum}\n";
                $teks .= "                                    = {$tgl23SubDefIndo}\n";
            }
        }
        $teks .= "\n";

        $teks .= "   • Ekspirasi Akhir = Ekspirasi Sebelum Remisi - Remisi\n";
        $teks .= "                     = {$ekspirasiSebelumRemisiNum} - {$totalRemisiTeks}\n";
        $teks .= "                     = {$ekspirasiDefNum}\n";
        $teks .= "                     = {$ekspirasiDefIndo}\n";

        if ($hasSubsiderBb) {
            $teks .= "\n";
            $teks .= "   • Ekspirasi Akhir (+Subsider BB) = Ekspirasi Akhir + Pidana Subsider\n";
            $teks .= "                                    = {$ekspirasiDefNum} + {$subsiderTeks}\n";
            $teks .= "                                    = {$ekspirasiSubDefNum}\n";
            $teks .= "                                    = {$ekspirasiSubDefIndo} (Tanpa Remisi)\n";
        }
        $teks .= "\n";

        $opsi = $data['opsi_pengusulan'] ?? [];
        $hasBenturan = ! empty($opsi['has_benturan']);

        $teks .= "D. USUL SDP (H-2 BULAN)\n";
        if ($hasBenturan) {
            $remisiTerk = $opsi['remisi_terakhir'] ?? [];
            $remisiNama = $remisiTerk['nama_event'] ?? 'Remisi Terakhir';
            $remisiJenis = $remisiTerk['jenis'] ?? 'RK';
            $o1 = $opsi['opsi_1_pra_remisi'] ?? [];
            $o2 = $opsi['opsi_2_pasca_remisi'] ?? [];
            $tglUsulMurniNum = $num['tgl_mulai_pengusulan_murni'] ?? '-';
            $tglUsulMurniIndo = $fmt['tgl_mulai_pengusulan_murni'] ?? '-';

            $teks .= "   [CATATAN OPERASIONAL SDP: PENYESUAIAN REMISI TERAKHIR]\n";
            $teks .= "   Hitungan matematis murni 2/3 Definitif - 60 Hari:\n";
            $teks .= "   = {$tgl23DefNum} - 60 Hari\n";
            $teks .= "   = {$tglUsulMurniNum} ({$tglUsulMurniIndo})\n";
            $teks .= "   *Catatan: Tanggal di atas mendahului tanggal pemberian {$remisiNama} ({$remisiTerk['tanggal_numeric']}).\n";
            $teks .= "   Pada sistem SDP, pengusulan tidak dapat diinput sebelum SK remisi bersangkutan terbit (tidak kurang dari tanggal remisi).\n";
            $teks .= "   Oleh karena itu tersedia 2 Opsi Pengusulan:\n\n";

            $teks .= "   • Opsi 1 = Usul Sebelum Remisi {$remisiNama} ({$remisiJenis}):\n";
            $teks .= "     - Tgl. 2/3 Berjalan   = {$o1['tgl_2_3_numeric']} ({$o1['tgl_2_3_indo']})\n";
            $teks .= "     - Mulai Usul di SDP   = {$o1['tgl_2_3_numeric']} - 60 Hari\n";
            $teks .= "                           = {$o1['tgl_usul_numeric']} = {$o1['tgl_usul_indo']}\n";
            $teks .= "     (Petugas dapat memproses berkas lebih awal dengan SK remisi yang sudah definitif di tangan tanpa menunggu SK remisi berikutnya)\n\n";

            $teks .= "   • Opsi 2 = Usul Setelah Remisi {$remisiNama} ({$remisiJenis}):\n";
            $teks .= "     - Tgl. 2/3 Definitif  = {$tgl23DefNum} ({$tgl23DefIndo})\n";
            $teks .= "     - Mulai Usul di SDP   = {$o2['tgl_usul_numeric']} = {$o2['tgl_usul_indo']} [Hari H SK Remisi]\n";
            $teks .= "     (Pengusulan diajukan tepat pada tanggal pemberian remisi {$remisiNama} karena pada SDP tidak dapat diinput sebelum SK terbit)\n";
        } else {
            $teks .= "   • Pengusulan di SDP = Tgl. 2/3 Masa Pidana - 60 Hari (H-2 Bulan)\n";
            $teks .= "                       = {$tgl23DefNum} - 60 Hari\n";
            $teks .= "                       = {$tglUsulNum}\n";
            $teks .= "                       = {$tglUsulIndo}\n";
        }
        $teks .= "========================================================\n";

        return $teks;
    }

    /**
     * Render komponen visual HTML hitungan manual langkah demi langkah untuk web modal
     */
    public static function renderHitunganManualHtml(array $data): string
    {
        $judulProgram = htmlspecialchars($data['judul_program'] ?? 'PERHITUNGAN PEMBEBASAN BERSYARAT (PB)');
        $namaWbp = htmlspecialchars($data['nama_wbp'] ?? 'Narapidana');
        $noRegister = htmlspecialchars($data['no_register'] ?? '-');
        $perkaraPasal = htmlspecialchars($data['perkara_pasal'] ?? '-');
        $lamaVonis = htmlspecialchars($data['lama_pidana_teks'] ?? '-');
        $subsiderIdentitas = htmlspecialchars($data['formatted']['subsider_identitas_teks'] ?? ($data['subsider_identitas_teks'] ?? '-'));
        $subsiderTeks = htmlspecialchars($data['formatted']['subsider_teks'] ?? ($data['subsider_teks'] ?? '0 Hari'));
        $hasSubsiderBb = ! empty($data['has_subsider']) && (($data['subsider_status'] ?? 'bb') === 'bb');

        $num = $data['numeric'] ?? [];
        $fmt = $data['formatted'] ?? [];

        $tglAwalNum = htmlspecialchars($num['tgl_awal'] ?? '-');
        $tglAwalIndo = htmlspecialchars($fmt['tgl_awal'] ?? '-');

        $tglPutusanNum = htmlspecialchars($num['tgl_putusan'] ?? '-');
        $tglPutusanIndo = htmlspecialchars($fmt['tgl_putusan'] ?? '-');

        $masaTahananTeks = htmlspecialchars($fmt['masa_tahanan'] ?? '-');
        $masaTahananHari = (int) ($data['masa_tahanan_hari'] ?? 0);

        $ekspirasiAwalNum = htmlspecialchars($num['ekspirasi_awal'] ?? ($num['ekspirasi_sebelum_remisi'] ?? '-'));
        $ekspirasiAwalIndo = htmlspecialchars($fmt['ekspirasi_sebelum_remisi'] ?? '-');

        $ekspirasiSebelumRemisiNum = htmlspecialchars($num['ekspirasi_sebelum_remisi'] ?? '-');
        $ekspirasiSebelumRemisiIndo = htmlspecialchars($fmt['ekspirasi_sebelum_remisi'] ?? '-');

        $ekspirasiSubSebelumRemisiNum = htmlspecialchars($num['ekspirasi_subsider_sebelum_remisi'] ?? '-');
        $ekspirasiSubSebelumRemisiIndo = htmlspecialchars($fmt['ekspirasi_subsider_sebelum_remisi'] ?? '-');

        $totalRemisiHari = (int) ($data['total_proyeksi_remisi_hari'] ?? 0);
        $totalRemisiTeks = htmlspecialchars($data['total_proyeksi_remisi_teks'] ?? '0 Hari');

        $tgl12DefNum = htmlspecialchars($num['tgl_1_2_definitif'] ?? '-');
        $tgl12DefIndo = htmlspecialchars($fmt['tgl_1_2_definitif'] ?? '-');

        $tgl23DefNum = htmlspecialchars($num['tgl_2_3_definitif'] ?? '-');
        $tgl23DefIndo = htmlspecialchars($fmt['tgl_2_3_definitif'] ?? '-');

        $tgl23SubDefNum = htmlspecialchars($num['tgl_2_3_subsider_definitif'] ?? '-');
        $tgl23SubDefIndo = htmlspecialchars($fmt['tgl_2_3_subsider_definitif'] ?? '-');

        $ekspirasiDefNum = htmlspecialchars($num['ekspirasi_definitif'] ?? '-');
        $ekspirasiDefIndo = htmlspecialchars($fmt['ekspirasi_definitif'] ?? '-');

        $ekspirasiSubDefNum = htmlspecialchars($num['ekspirasi_subsider_definitif'] ?? '-');
        $ekspirasiSubDefIndo = htmlspecialchars($fmt['ekspirasi_subsider_definitif'] ?? '-');

        $tglUsulNum = htmlspecialchars($num['tgl_mulai_pengusulan'] ?? '-');
        $tglUsulIndo = htmlspecialchars($fmt['tgl_mulai_pengusulan'] ?? '-');

        $thn = (int) ($data['tahun'] ?? 0);
        $bln = (int) ($data['bulan'] ?? 0);
        $hri = (int) ($data['hari'] ?? 0);
        $tglAwalCarbon = ! empty($data['tgl_awal']) ? Carbon::parse($data['tgl_awal'])->startOfDay() : Carbon::today();

        $calc12 = self::hitungPentahapanSetelahRemisi($tglAwalCarbon, $thn, $bln, $hri, 1, 2, $totalRemisiHari);
        $calc23 = self::hitungPentahapanSetelahRemisi($tglAwalCarbon, $thn, $bln, $hri, 2, 3, $totalRemisiHari);

        $pidanaBulanTeks = htmlspecialchars($data['pidana_bulan_teks'] ?? $calc12['pidana_bulan_teks']);
        $fraksi12BulanTeks = htmlspecialchars($data['fraksi_1_2_bulan_teks'] ?? $calc12['fraksi_bulan_teks']);
        $selisih12BulanTeks = htmlspecialchars($data['selisih_1_2_bulan_teks'] ?? $calc12['selisih_bulan_teks']);
        $selisih12TahunTeks = htmlspecialchars($data['selisih_1_2_tahun_teks'] ?? $calc12['selisih_tahun_teks']);

        $fraksi23BulanTeks = htmlspecialchars($data['fraksi_2_3_bulan_teks'] ?? $calc23['fraksi_bulan_teks']);
        $selisih23BulanTeks = htmlspecialchars($data['selisih_2_3_bulan_teks'] ?? $calc23['selisih_bulan_teks']);
        $selisih23TahunTeks = htmlspecialchars($data['selisih_2_3_tahun_teks'] ?? $calc23['selisih_tahun_teks']);

        $html = '<div class="hitungan-manual-container d-flex flex-column gap-3" style="gap: 16px !important;">';

        // Judul Utama Dinamis
        $html .= <<<HEADER
        <div class="text-center pb-2 border-bottom">
            <h5 class="fw-bold text-dark mb-0 tracking-wide text-uppercase" style="letter-spacing: 0.5px;">
                {$judulProgram}
            </h5>
            <small class="text-muted">Kaidah Perhitungan Matematis Sesuai Regulasi Pemasyarakatan</small>
        </div>
HEADER;

        // Identitas WBP 2 Kolom
        $html .= <<<IDENTITAS
        <div class="card border-0 shadow-sm mb-3" style="background-color: #f8fafc; border-radius: 10px;">
            <div class="card-body p-3.5">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-sm-6">
                        <table class="table-borderless w-100 mb-0">
                            <tr>
                                <td style="width: 135px; vertical-align: top;" class="fw-bold text-secondary">Nama</td>
                                <td style="width: 14px; vertical-align: top;" class="fw-bold text-secondary">:</td>
                                <td style="vertical-align: top;" class="fw-bold text-dark">{$namaWbp}</td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">No. Register</td>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">:</td>
                                <td style="vertical-align: top;" class="fw-semibold text-dark">{$noRegister}</td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">Perkara / Pasal</td>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">:</td>
                                <td style="vertical-align: top;" class="fw-semibold text-dark">{$perkaraPasal}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table-borderless w-100 mb-0">
                            <tr>
                                <td style="width: 145px; vertical-align: top;" class="fw-bold text-secondary">Tgl. Mulai Ditahan</td>
                                <td style="width: 14px; vertical-align: top;" class="fw-bold text-secondary">:</td>
                                <td style="vertical-align: top;" class="fw-semibold text-dark">{$tglAwalIndo}</td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">Tgl. Putusan</td>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">:</td>
                                <td style="vertical-align: top;" class="fw-semibold text-dark">{$tglPutusanIndo}</td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">Pidana Pokok</td>
                                <td style="vertical-align: top;" class="fw-bold text-secondary">:</td>
                                <td style="vertical-align: top;" class="fw-bold text-dark">{$lamaVonis}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-12 border-top pt-2 mt-1">
                        <table class="table-borderless w-100 mb-0">
                            <tr>
                                <td style="width: 135px; vertical-align: top;" class="fw-bold text-secondary">Pidana Subsider</td>
                                <td style="width: 14px; vertical-align: top;" class="fw-bold text-secondary">:</td>
                                <td style="vertical-align: top;" class="fw-semibold text-primary">{$subsiderIdentitas}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
IDENTITAS;

        $totalLuarUptPascaHtml = (int) ($data['total_hari_luar_upt_pasca'] ?? 0);

        // Bagian A (Jika Tgl Putusan diisi)
        if (! empty($data['tgl_putusan'])) {
            if (! empty($data['has_riwayat_penahanan']) && ! empty($data['riwayat_penahanan'])) {
                $rowsRiwayatHtml = '';
                foreach ($data['riwayat_penahanan'] as $r) {
                    $metodeTeks = htmlspecialchars($r['metode_hitung'] ?? ($r['is_jeda'] ? 'Eksklusif (T2-T1)' : 'Inklusif (T2-T1+1)'));
                    $fasePascaBadge = ! empty($r['hari_luar_upt_pasca'])
                        ? '<br><span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 9.5px;">Pasca-Putusan: +'.$r['hari_luar_upt_pasca'].' hr Ekspirasi</span>'
                        : '';
                    $badgeJeda = $r['is_jeda']
                        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">0 Hari (Luar UPT)</span>'.$fasePascaBadge
                        : '<b class="text-success">'.$r['hari_potah'].' hr</b>';
                    $rowsRiwayatHtml .= '<tr>'.
                        '<td class="text-center font-monospace">'.$r['no'].'</td>'.
                        '<td><b>'.htmlspecialchars($r['label_jenis']).'</b><br><small class="text-muted">'.htmlspecialchars($r['dasar_hukum']).' &bull; '.$metodeTeks.'</small></td>'.
                        '<td class="text-center font-monospace">'.htmlspecialchars($r['periode_teks']).'</td>'.
                        '<td class="text-center font-monospace">'.$r['hari_kalender'].' hr</td>'.
                        '<td class="text-center font-monospace">'.htmlspecialchars($r['rasio']).'</td>'.
                        '<td class="text-center font-monospace">'.$badgeJeda.'</td>'.
                        '<td style="font-size: 11.5px;">'.htmlspecialchars($r['keterangan']).'</td>'.
                    '</tr>';
                }

                $totalKal = $data['total_hari_kalender_tahanan'] ?? 0;
                $totalLuarUpt = (int) ($data['total_hari_luar_upt'] ?? ($data['total_hari_jeda_tahanan'] ?? 0));
                $jedaNote = $totalLuarUpt > 0
                    ? " <span class=\"text-danger\">(Hari di Luar UPT: {$totalLuarUpt} hr".($totalLuarUptPascaHtml > 0 ? ", Pasca-Putusan: {$totalLuarUptPascaHtml} hr" : '').')</span>'
                    : '';
                $tglAwalEfektifNum = htmlspecialchars($num['tgl_mulai_ditahan_efektif'] ?? '-');
                $tglAwalEfektifIndo = htmlspecialchars($fmt['tgl_mulai_ditahan_efektif'] ?? '-');

                $rowLuarUptSummaryHtml = '';
                if ($totalLuarUpt > 0) {
                    $rowLuarUptSummaryHtml = '<tr><td style="font-weight: 600; color: #b91c1c;">Total Hari di Luar UPT</td><td style="text-align: center; font-weight: bold;">=</td><td><b class="text-danger">'.$totalLuarUpt.' Hari</b> <span class="text-muted">(Eksklusif T2-T1, 0 Hari Potah'.($totalLuarUptPascaHtml > 0 ? "; Pasca-Putusan {$totalLuarUptPascaHtml} hr menggeser Ekspirasi" : '').')</span></td></tr>';
                }

                $html .= <<<CARD_A
            <div class="card border mb-3" style="border-radius: 10px; border-color: #bbf7d0;">
                <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #f0fdf4;">
                    <strong style="color: #166534; font-size: 14px;"><i class="fas fa-layer-group me-1.5"></i> A. POTONGAN TAHANAN (POTAH) &amp; HARI DI LUAR UPT</strong>
                    <span class="badge bg-success text-white" style="font-size: 10px;">Pasal 22 &amp; 31 KUHAP</span>
                </div>
                <div class="card-body p-3.5" style="font-size: 13.5px;">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered table-hover mb-0 align-middle" style="font-size: 12px;">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 4%;">No</th>
                                    <th style="width: 24%;">Status Penahanan</th>
                                    <th style="width: 22%;">Periode</th>
                                    <th style="width: 10%;">Kalender</th>
                                    <th style="width: 12%;">Rasio</th>
                                    <th style="width: 12%;">Hari Potah</th>
                                    <th style="width: 16%;">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$rowsRiwayatHtml}
                            </tbody>
                            <tfoot class="table-light fw-bold" style="font-size: 12px;">
                                <tr>
                                    <td colspan="3" class="text-end">Total Hari Kalender:</td>
                                    <td class="text-center font-monospace">{$totalKal} hr</td>
                                    <td class="text-end">Total Potah:</td>
                                    <td class="text-center font-monospace text-success" style="font-size: 13px;">{$masaTahananHari} hr</td>
                                    <td>{$jedaNote}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <table class="table-borderless w-100 font-monospace mb-0" style="font-size: 13px; line-height: 1.8;">
                        {$rowLuarUptSummaryHtml}
                        <tr>
                            <td style="width: 250px; font-weight: 600; color: #0f172a;">Total Potah Diperoleh</td>
                            <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                            <td><b class="text-success" style="font-size: 14.5px;">{$masaTahananTeks} ({$masaTahananHari} Hari)</b></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: #0f172a;">Tgl. Mulai Ditahan (Efektif)</td>
                            <td style="text-align: center; font-weight: bold;">=</td>
                            <td>Tgl. Putusan - Total Potah</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: center; font-weight: bold;">=</td>
                            <td>{$tglPutusanNum} - {$masaTahananHari} Hari</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: center; font-weight: bold;">=</td>
                            <td><b class="text-primary" style="font-size: 14px;">{$tglAwalEfektifNum}</b> <span class="text-muted">({$tglAwalEfektifIndo})</span></td>
                        </tr>
                    </table>
                </div>
            </div>
CARD_A;
            } else {
                $html .= <<<CARD_A
            <div class="card border mb-3" style="border-radius: 10px; border-color: #bbf7d0;">
                <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #f0fdf4;">
                    <strong style="color: #166534; font-size: 14px;"><i class="fas fa-gavel me-1.5"></i> A. POTONGAN TAHANAN (POTAH)</strong>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10.5px;">KUHAP Ps. 22(4)</span>
                </div>
                <div class="card-body p-3.5" style="font-size: 13.5px;">
                    <table class="table-borderless w-100 font-monospace mb-0" style="font-size: 13px; line-height: 1.8;">
                        <tr>
                            <td style="width: 240px; font-weight: 600; color: #0f172a;">Potah</td>
                            <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                            <td>Tgl. Mulai Ditahan - Tgl. Putusan</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: center; font-weight: bold;">=</td>
                            <td>{$tglAwalNum} - {$tglPutusanNum}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: center; font-weight: bold;">=</td>
                            <td><b class="text-success" style="font-size: 14.5px;">{$masaTahananTeks}</b></td>
                        </tr>
                    </table>
                </div>
            </div>
CARD_A;
            }
        }

        // Bagian B: Ekspirasi Sebelum Remisi
        $subsiderHtmlB = '';
        if ($hasSubsiderBb) {
            $subsiderHtmlB = <<<SUB_B
            <tr style="height: 8px;"><td colspan="3"></td></tr>
            <tr>
                <td style="width: 240px; font-weight: bold; color: #991b1b;">Ekspirasi (+Subsider BB)</td>
                <td style="width: 25px; text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td style="color: #991b1b;">Ekspirasi Akhir + Pidana Subsider</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td>{$ekspirasiSebelumRemisiNum} + {$subsiderTeks}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td>{$ekspirasiSubSebelumRemisiNum}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td><b style="color: #991b1b; font-size: 14.5px;">{$ekspirasiSubSebelumRemisiIndo}</b></td>
            </tr>
SUB_B;
        }

        $luarUptFormulaB = $totalLuarUptPascaHtml > 0 ? ' + Hari di Luar UPT Pasca-Putusan' : '';
        $luarUptValB = $totalLuarUptPascaHtml > 0 ? " + {$totalLuarUptPascaHtml} Hari" : '';

        $barisEkspirasiAwal = '';
        if (! empty($data['tgl_putusan'])) {
            $barisEkspirasiAwal = <<<EKS_AWAL
            <tr>
                <td style="width: 240px; font-weight: 600; color: #0f172a;">Ekspirasi Awal</td>
                <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                <td>Tgl. Putusan + Lama Pidana Pokok</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold;">=</td>
                <td>{$tglPutusanNum} + {$lamaVonis}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold;">=</td>
                <td>{$ekspirasiAwalNum}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold;">=</td>
                <td><b class="text-primary" style="font-size: 14.5px;">{$ekspirasiAwalIndo}</b></td>
            </tr>
            <tr style="height: 8px;"><td colspan="3"></td></tr>
            <tr>
                <td style="width: 240px; font-weight: 600; color: #0f172a;">Ekspirasi Akhir</td>
                <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                <td>Ekspirasi Awal - Potah{$luarUptFormulaB}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold;">=</td>
                <td>{$ekspirasiAwalNum} - {$masaTahananTeks}{$luarUptValB}</td>
            </tr>
EKS_AWAL;
        } else {
            $barisEkspirasiAwal = <<<EKS_AWAL
            <tr>
                <td style="width: 240px; font-weight: 600; color: #0f172a;">Ekspirasi Akhir</td>
                <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                <td>Tgl. Mulai Ditahan + Lama Pidana Pokok</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold;">=</td>
                <td>{$tglAwalNum} + {$lamaVonis}</td>
            </tr>
EKS_AWAL;
        }

        $html .= <<<CARD_B
        <div class="card border mb-3" style="border-radius: 10px; border-color: #bfdbfe;">
            <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #eff6ff;">
                <strong style="color: #1e40af; font-size: 14px;"><i class="fas fa-door-open me-1.5"></i> B. EKSPIRASI SEBELUM REMISI</strong>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10.5px;">Vonis Pokok</span>
            </div>
            <div class="card-body p-3.5" style="font-size: 13.5px;">
                <table class="table-borderless w-100 font-monospace mb-0" style="font-size: 13px; line-height: 1.8;">
                    {$barisEkspirasiAwal}
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$ekspirasiSebelumRemisiNum}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td><b class="text-danger" style="font-size: 14.5px;">{$ekspirasiSebelumRemisiIndo}</b></td>
                    </tr>
                    {$subsiderHtmlB}
                </table>
            </div>
        </div>
CARD_B;

        // Bagian C: Pentahapan Masa Pidana (Setelah Remisi)
        $subsider23HtmlC = '';
        if ($hasSubsiderBb) {
            $subsider23HtmlC = <<<SUB_23
            <tr style="height: 8px;"><td colspan="3"></td></tr>
            <tr>
                <td style="width: 240px; font-weight: bold; color: #14532d;">• 2/3 (+Subsider BB)</td>
                <td style="width: 25px; text-align: center; font-weight: bold; color: #14532d;">=</td>
                <td style="color: #14532d;">2/3 Masa Pidana + Pidana Subsider</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #14532d;">=</td>
                <td>{$tgl23DefNum} + {$subsiderTeks}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #14532d;">=</td>
                <td>{$tgl23SubDefNum}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #14532d;">=</td>
                <td><b style="color: #14532d; font-size: 14.5px;">{$tgl23SubDefIndo}</b></td>
            </tr>
SUB_23;
        }

        $subsiderEkspirasiHtmlC = '';
        if ($hasSubsiderBb) {
            $subsiderEkspirasiHtmlC = <<<SUB_EKS_C
            <tr style="height: 8px;"><td colspan="3"></td></tr>
            <tr>
                <td style="width: 240px; font-weight: bold; color: #991b1b;">• Ekspirasi (+Subsider BB)</td>
                <td style="width: 25px; text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td style="color: #991b1b;">Ekspirasi Akhir + Pidana Subsider (Tanpa Remisi)</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td>{$ekspirasiDefNum} + {$subsiderTeks}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td>{$ekspirasiSubDefNum}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold; color: #991b1b;">=</td>
                <td><b style="color: #991b1b; font-size: 14.5px;">{$ekspirasiSubDefIndo}</b> <span class="text-muted" style="font-size: 11px;">(Tanpa Remisi)</span></td>
            </tr>
SUB_EKS_C;
        }

        $labelDitahanHtml = ! empty($data['has_riwayat_penahanan']) ? 'Tgl. Mulai Ditahan (Efektif)' : 'Tgl. Mulai Ditahan';
        $tglDitahanAcuanNum = ! empty($data['has_riwayat_penahanan']) ? htmlspecialchars($num['tgl_mulai_ditahan_efektif'] ?? $tglAwalNum) : $tglAwalNum;

        $opsiHtml = $data['opsi_pengusulan'] ?? [];
        $hasBenturanHtml = ! empty($opsiHtml['has_benturan']);

        $baris23HtmlC = '';
        if ($hasBenturanHtml) {
            $remisiTerk = $opsiHtml['remisi_terakhir'] ?? [];
            $o1 = $opsiHtml['opsi_1_pra_remisi'] ?? [];
            $o2 = $opsiHtml['opsi_2_pasca_remisi'] ?? [];
            $remisiTerkNama = htmlspecialchars($remisiTerk['nama_event'] ?? 'Remisi Terakhir');
            $remisiTerkJenis = htmlspecialchars($remisiTerk['jenis'] ?? 'RK');
            $remisiTerkTglIndo = htmlspecialchars($remisiTerk['tanggal_indo'] ?? '-');

            $tglUsulMurniNum = htmlspecialchars($num['tgl_mulai_pengusulan_murni'] ?? ($opsiHtml['tgl_usul_murni_numeric'] ?? '-'));
            $tglUsulMurniIndo = htmlspecialchars($data['formatted']['tgl_mulai_pengusulan_murni'] ?? ($opsiHtml['tgl_usul_murni_indo'] ?? '-'));

            $o1RemisiTeks = htmlspecialchars($o1['remisi_teks'] ?? '0 Hari');
            $o1SelisihTahunTeks = htmlspecialchars($o1['selisih_2_3_tahun_teks'] ?? $fraksi23BulanTeks);
            $o1Tgl23Num = htmlspecialchars($o1['tgl_2_3_numeric'] ?? '-');
            $o1Tgl23Indo = htmlspecialchars($o1['tgl_2_3_indo'] ?? '-');
            $o1TglUsulNum = htmlspecialchars($o1['tgl_usul_numeric'] ?? '-');
            $o1TglUsulIndo = htmlspecialchars($o1['tgl_usul_indo'] ?? '-');

            $o2TglUsulNum = htmlspecialchars($o2['tgl_usul_numeric'] ?? '-');
            $o2TglUsulIndo = htmlspecialchars($o2['tgl_usul_indo'] ?? '-');

            $subsider23Opsi1Html = '';
            if ($hasSubsiderBb) {
                $o1SubNum = htmlspecialchars($o1['tgl_2_3_subsider_numeric'] ?? '-');
                $o1SubIndo = htmlspecialchars($o1['tgl_2_3_subsider_indo'] ?? '-');
                $subsider23Opsi1Html = <<<SUB_O1
                    <tr>
                        <td style="color: #14532d; font-weight: 600;">(+Subsider BB)</td>
                        <td style="text-align: center; font-weight: bold; color: #14532d;">=</td>
                        <td>{$o1Tgl23Num} + {$subsiderTeks} = {$o1SubNum} = <b style="color: #14532d;">{$o1SubIndo}</b></td>
                    </tr>
SUB_O1;
            }

            $baris23HtmlC = <<<BARIS_23_BENTURAN
                    <tr>
                        <td colspan="3" class="pt-2 pb-1">
                            <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded-2" style="background: linear-gradient(90deg, #fef3c7 0%, #fffbeb 100%); border-left: 4px solid #f59e0b;">
                                <i class="fas fa-code-branch text-warning fs-5"></i>
                                <div>
                                    <strong class="text-dark" style="font-size: 13px;">• Tersedia 2 Opsi Perhitungan 2/3 Masa Pidana:</strong>
                                    <div class="text-muted" style="font-size: 11px;">Mepet tanggal pemberian remisi terakhir ({$remisiTerkNama} [{$remisiTerkTglIndo}])</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="pb-2">
                            <!-- Opsi 1 Card -->
                            <div class="p-3 rounded-3 mb-2.5 shadow-sm" style="background-color: #f0fdf4; border: 1px solid #86efac;">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-1.5 border-bottom border-success-subtle">
                                    <strong class="text-success" style="font-size: 13px;"><i class="fas fa-hourglass-start me-1.5"></i> Opsi 1 = 2/3 Sebelum Remisi {$remisiTerkNama} ({$remisiTerkJenis})</strong>
                                    <span class="badge bg-success text-white" style="font-size: 10px;">Usulan Berkas Awal</span>
                                </div>
                                <div class="text-muted small mb-2" style="font-size: 11px;">Perhitungan berdasarkan remisi berjalan ({$o1RemisiTeks}) untuk pengusulan lebih awal di SDP tanpa menunggu SK remisi berikutnya terbit.</div>
                                <table class="table-borderless w-100 font-monospace mb-2" style="font-size: 12.5px; line-height: 1.6;">
                                    <tr>
                                        <td style="width: 220px; color: #15803d; font-weight: 600;">Rumus 2/3</td>
                                        <td style="width: 22px; text-align: center; font-weight: bold;">=</td>
                                        <td>{$labelDitahanHtml} + ((2/3 x Masa Pidana) - Remisi Berjalan)</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$tglDitahanAcuanNum} + ((2/3 x {$lamaVonis}) - {$o1RemisiTeks})</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$tglDitahanAcuanNum} + ({$fraksi23BulanTeks} - {$o1RemisiTeks})</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$tglDitahanAcuanNum} + {$o1SelisihTahunTeks}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$o1Tgl23Num}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td><b class="text-success" style="font-size: 14.5px;">{$o1Tgl23Indo}</b></td>
                                    </tr>
                                    {$subsider23Opsi1Html}
                                </table>
                                <div class="p-2 rounded-2 bg-white border border-success-subtle d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-success fw-bold" style="font-size: 12px;"><i class="fas fa-paper-plane me-1"></i> Prediksi Tanggal Usulan Diusulkan (H-60 Hari)</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace" style="font-size: 12px;">{$o1TglUsulIndo}</span>
                                    </div>
                                    <div class="text-secondary small font-monospace" style="font-size: 11px;">= Tgl. 2/3 - 60 Hari = {$o1Tgl23Num} - 60 Hari = {$o1TglUsulNum} = <b class="text-success">{$o1TglUsulIndo}</b></div>
                                    <div class="text-muted" style="font-size: 10.5px; font-style: italic;">Petugas dapat memproses berkas lebih awal dengan SK remisi definitif di tangan tanpa menunggu SK remisi berikutnya.</div>
                                </div>
                            </div>

                            <!-- Opsi 2 Card -->
                            <div class="p-3 rounded-3 shadow-sm" style="background-color: #fff7ed; border: 1px solid #fdba74;">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-1.5 border-bottom border-warning-subtle">
                                    <strong style="color: #c2410c; font-size: 13px;"><i class="fas fa-award me-1.5"></i> Opsi 2 = 2/3 Setelah Remisi {$remisiTerkNama} ({$remisiTerkJenis})</strong>
                                    <span class="badge bg-warning text-dark" style="font-size: 10px;">Definitif Pasca-SK</span>
                                </div>
                                <div class="text-muted small mb-2" style="font-size: 11px;">Perhitungan setelah dipotong remisi definitif ({$totalRemisiTeks}). Diusulkan tepat setelah SK remisi bersangkutan terbit di SDP.</div>
                                <table class="table-borderless w-100 font-monospace mb-2" style="font-size: 12.5px; line-height: 1.6;">
                                    <tr>
                                        <td style="width: 220px; color: #c2410c; font-weight: 600;">Rumus 2/3</td>
                                        <td style="width: 22px; text-align: center; font-weight: bold;">=</td>
                                        <td>{$labelDitahanHtml} + ((2/3 x Masa Pidana) - Remisi Definitif)</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$tglDitahanAcuanNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$tglDitahanAcuanNum} + ({$fraksi23BulanTeks} - {$totalRemisiTeks})</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$tglDitahanAcuanNum} + {$selisih23TahunTeks}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td>{$tgl23DefNum}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="text-align: center; font-weight: bold;">=</td>
                                        <td><b style="color: #c2410c; font-size: 14.5px;">{$tgl23DefIndo}</b></td>
                                    </tr>
                                    {$subsider23HtmlC}
                                </table>
                                <div class="p-2 rounded-2 bg-white border border-warning-subtle d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="fw-bold" style="color: #c2410c; font-size: 12px;"><i class="fas fa-exclamation-triangle me-1"></i> Prediksi Tanggal Usulan Diusulkan</span>
                                        <span class="badge bg-warning text-dark font-monospace" style="font-size: 12px;">{$o2TglUsulIndo} (Hari H SK)</span>
                                    </div>
                                    <div class="text-secondary small font-monospace" style="font-size: 11px;">{$tgl23DefNum} - 60 hr = <b>{$tglUsulMurniNum}</b> &lt; tgl remisi <b>{$remisiTerkTglIndo}</b></div>
                                    <div class="alert alert-warning py-1.5 px-2.5 mb-0 mt-1" style="font-size: 11px; border-left: 3px solid #d97706;">
                                        <strong>Ketentuan SDP:</strong> Pada SDP, usulan tidak dapat kurang dari tgl remisi, disesuaikan tepat Hari H SK {$remisiTerkNama} = {$o2TglUsulNum} = <b style="color: #c2410c;">{$o2TglUsulIndo}</b>.
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
BARIS_23_BENTURAN;
        } else {
            $baris23HtmlC = <<<BARIS_23_NORMAL
                    <tr>
                        <td style="width: 240px; font-weight: 600; color: #0f172a;">• 2/3 Masa Pidana</td>
                        <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                        <td>{$labelDitahanHtml} + ((2/3 x Masa Pidana) - Remisi)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + ((2/3 x {$pidanaBulanTeks}) - {$totalRemisiTeks})</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + ({$fraksi23BulanTeks} - {$totalRemisiTeks})</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + {$selisih23BulanTeks}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + {$selisih23TahunTeks}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tgl23DefNum}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td><b class="text-success" style="font-size: 15px;">{$tgl23DefIndo}</b></td>
                    </tr>
                    {$subsider23HtmlC}
BARIS_23_NORMAL;
        }

        $html .= <<<CARD_C
        <div class="card border mb-3" style="border-radius: 10px; border-color: #fecaca;">
            <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #fef2f2;">
                <strong style="color: #991b1b; font-size: 14px;"><i class="fas fa-layer-group me-1.5"></i> C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)</strong>
                <span class="badge bg-success text-white" style="font-size: 11.5px;">Remisi: {$totalRemisiTeks}</span>
            </div>
            <div class="card-body p-3.5" style="font-size: 13.5px;">
                <table class="table-borderless w-100 font-monospace mb-0" style="font-size: 13px; line-height: 1.8;">
                    <tr>
                        <td style="width: 240px; font-weight: 600; color: #0f172a;">• Jumlah Remisi yang diperoleh</td>
                        <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                        <td><b class="text-success" style="font-size: 14px;">{$totalRemisiTeks}</b></td>
                    </tr>
                    <tr style="height: 6px;"><td colspan="3"></td></tr>
                    <tr>
                        <td style="width: 240px; font-weight: 600; color: #0f172a;">• 1/2 Masa Pidana</td>
                        <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                        <td>{$labelDitahanHtml} + ((1/2 x Masa Pidana) - Remisi)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + ((1/2 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + ((1/2 x {$pidanaBulanTeks}) - {$totalRemisiTeks})</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + ({$fraksi12BulanTeks} - {$totalRemisiTeks})</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + {$selisih12BulanTeks}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglDitahanAcuanNum} + {$selisih12TahunTeks}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tgl12DefNum}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td><b style="color: #0284c7; font-size: 14.5px;">{$tgl12DefIndo}</b></td>
                    </tr>
                    <tr style="height: 8px;"><td colspan="3"></td></tr>
                    {$baris23HtmlC}
                    <tr style="height: 8px;"><td colspan="3"></td></tr>
                    <tr>
                        <td style="width: 240px; font-weight: 600; color: #0f172a;">• Ekspirasi Akhir</td>
                        <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                        <td>Ekspirasi Sebelum Remisi - Remisi</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$ekspirasiSebelumRemisiNum} - {$totalRemisiTeks}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$ekspirasiDefNum}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td><b class="text-danger" style="font-size: 15px;">{$ekspirasiDefIndo}</b></td>
                    </tr>
                    {$subsiderEkspirasiHtmlC}
                </table>
            </div>
        </div>
CARD_C;

        $opsiHtml = $data['opsi_pengusulan'] ?? [];
        $hasBenturanHtml = ! empty($opsiHtml['has_benturan']);

        if ($hasBenturanHtml) {
            $remisiTerk = $opsiHtml['remisi_terakhir'] ?? [];
            $o1 = $opsiHtml['opsi_1_pra_remisi'] ?? [];
            $o2 = $opsiHtml['opsi_2_pasca_remisi'] ?? [];
            $tglUsulMurniNum = htmlspecialchars($num['tgl_mulai_pengusulan_murni'] ?? '-');
            $tglUsulMurniIndo = htmlspecialchars($fmt['tgl_mulai_pengusulan_murni'] ?? '-');
            $remisiTerkNama = htmlspecialchars($remisiTerk['nama_event'] ?? 'Remisi Terakhir');
            $remisiTerkJenis = htmlspecialchars($remisiTerk['jenis'] ?? 'RK');
            $remisiTerkTglIndo = htmlspecialchars($remisiTerk['tanggal_indo'] ?? '-');
            $o1Tgl23Num = htmlspecialchars($o1['tgl_2_3_numeric'] ?? '-');
            $o1Tgl23Indo = htmlspecialchars($o1['tgl_2_3_indo'] ?? '-');
            $o1TglUsulNum = htmlspecialchars($o1['tgl_usul_numeric'] ?? '-');
            $o1TglUsulIndo = htmlspecialchars($o1['tgl_usul_indo'] ?? '-');
            $o2TglUsulNum = htmlspecialchars($o2['tgl_usul_numeric'] ?? '-');
            $o2TglUsulIndo = htmlspecialchars($o2['tgl_usul_indo'] ?? '-');

            $html .= <<<CARD_D
        <div class="card border mb-3" style="border-radius: 10px; border-color: #fed7aa;">
            <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #fff7ed;">
                <strong style="color: #c2410c; font-size: 14px;"><i class="fas fa-calendar-check me-1.5"></i> D. USUL SDP (H-2 BULAN &amp; 2 OPSI OPERASIONAL)</strong>
                <span class="badge bg-warning text-dark" style="font-size: 11.5px;">Integrasi PB/CB</span>
            </div>
            <div class="card-body p-3.5" style="font-size: 13.5px;">
                <div class="alert alert-warning py-2 px-3 mb-3 border-0 d-flex align-items-start gap-2" style="background-color: #fef3c7; color: #92400e; font-size: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle mt-0.5"></i>
                    <div>
                        <strong>Catatan Operasional SDP:</strong> Hitungan matematis murni (<code>{$tgl23DefNum} - 60 Hari = {$tglUsulMurniNum} [{$tglUsulMurniIndo}]</code>) mendahului tanggal pemberian {$remisiTerkNama} ({$remisiTerkTglIndo}). Pada sistem SDP, pengusulan tidak dapat diinput sebelum SK remisi bersangkutan terbit (tidak kurang dari tanggal remisi). Berikut 2 opsi operasional SDP:
                    </div>
                </div>

                <div class="row g-2 mb-0 font-monospace" style="font-size: 12.5px;">
                    <div class="col-12 col-md-6">
                        <div class="p-3 border rounded-3 h-100" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                            <div class="fw-bold text-success mb-1" style="font-size: 12px;">OPSI 1: USUL SEBELUM REMISI ({$remisiTerkJenis})</div>
                            <div class="text-muted small mb-2">Usul lebih awal dengan SK yang sudah definitif di tangan</div>
                            <div style="line-height: 1.6;">
                                2/3 Berjalan: <b>{$o1Tgl23Num}</b> ({$o1Tgl23Indo})<br>
                                Mulai Usul: <code>{$o1Tgl23Num} - 60 hr</code><br>
                                = {$o1TglUsulNum} = <b class="text-success" style="font-size: 14px;">{$o1TglUsulIndo}</b>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 border rounded-3 h-100" style="background-color: #fff7ed; border-color: #fed7aa !important;">
                            <div class="fw-bold text-warning-emphasis mb-1" style="font-size: 12px; color: #c2410c;">OPSI 2: USUL SETELAH REMISI ({$remisiTerkJenis})</div>
                            <div class="text-muted small mb-2">Usul tepat pada Hari H SK terbit (tidak kurang dari tgl remisi)</div>
                            <div style="line-height: 1.6;">
                                2/3 Definitif: <b>{$tgl23DefNum}</b> ({$tgl23DefIndo})<br>
                                SK Remisi Terakhir: <b>{$remisiTerkTglIndo}</b><br>
                                Mulai Usul: = {$o2TglUsulNum} = <b style="color: #c2410c; font-size: 14px;">{$o2TglUsulIndo}</b> [Hari H SK]
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
CARD_D;
        } else {
            $html .= <<<CARD_D
        <div class="card border mb-3" style="border-radius: 10px; border-color: #fed7aa;">
            <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #fff7ed;">
                <strong style="color: #c2410c; font-size: 14px;"><i class="fas fa-calendar-check me-1.5"></i> D. USUL SDP (H-2 BULAN)</strong>
                <span class="badge bg-warning text-dark" style="font-size: 11.5px;">Integrasi PB/CB</span>
            </div>
            <div class="card-body p-3.5" style="font-size: 13.5px;">
                <table class="table-borderless w-100 font-monospace mb-0" style="font-size: 13px; line-height: 1.8;">
                    <tr>
                        <td style="width: 240px; font-weight: 600; color: #0f172a;">• Pengusulan di SDP</td>
                        <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                        <td>Tgl. 2/3 Masa Pidana - 60 Hari (H-2 Bulan)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tgl23DefNum} - 60 Hari</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglUsulNum}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td><b style="color: #c2410c; font-size: 15px;">{$tglUsulIndo}</b></td>
                    </tr>
                </table>
            </div>
        </div>
CARD_D;
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Konversi jumlah hari remisi ke format baku Pemasyarakatan (Bulan dan Hari, tanpa akumulasi tahun)
     */
    public static function formatRemisiBulanHari(int $totalHari): string
    {
        if ($totalHari <= 0) {
            return '0 Hari';
        }

        $bln = (int) floor($totalHari / 30);
        $hri = $totalHari % 30;

        $parts = [];
        if ($bln > 0) {
            $parts[] = "{$bln} Bulan";
        }
        if ($hri > 0) {
            $parts[] = "{$hri} Hari";
        }

        return implode(', ', $parts) ?: '0 Hari';
    }

    /**
     * Hitung durasi pecahan dari masa pidana vonis (1/3, 1/2, 2/3)
     * dalam satuan proporsional baku Tahun, Bulan, Hari
     *
     * @return array{0: int, 1: int, 2: int} [tahun, bulan, hari]
     */
    public static function hitungDurasiFraksi(int $tahun, int $bulan, int $hari, int $num, int $den): array
    {
        $totalBulan = ($tahun * 12) + $bulan;
        $fracBulan = ($totalBulan * $num) / $den;
        $fracHari = ($hari * $num) / $den;

        $wholeBulan = (int) floor($fracBulan);
        $sisaBulanFrac = $fracBulan - $wholeBulan;
        $sisaHari = (int) round(($sisaBulanFrac * 30) + $fracHari);

        if ($sisaHari >= 30) {
            $wholeBulan += (int) floor($sisaHari / 30);
            $sisaHari = $sisaHari % 30;
        }

        $outTahun = (int) floor($wholeBulan / 12);
        $outBulan = $wholeBulan % 12;
        $outHari = $sisaHari;

        return [$outTahun, $outBulan, $outHari];
    }

    /**
     * Hitung pentahapan masa pidana (1/3, 1/2, 2/3) setelah dikurangi Remisi
     * sesuai kaidah perhitungan manual Pemasyarakatan:
     * 1. Masa Pidana diubah ke satuan Bulan (& Hari) mengikuti satuan Remisi (1 Tahun = 12 Bulan)
     * 2. Dikalikan pecahan (1/3, 1/2, 2/3) dalam satuan Bulan (& Hari)
     * 3. Dikurangi total Remisi (Bulan & Hari)
     * 4. Hasil pengurangan diubah kembali ke satuan Tahun, Bulan, Hari (12 Bulan = 1 Tahun)
     * 5. Dijumlahkan ke Tanggal Mulai Ditahan dengan kaidah Telram (tambahDurasiVonis)
     *
     * @return array{
     *     tanggal: Carbon,
     *     pidana_bulan_teks: string,
     *     fraksi_bulan_teks: string,
     *     selisih_bulan_teks: string,
     *     selisih_tahun_teks: string,
     *     selisih_tahun: int,
     *     selisih_bulan: int,
     *     selisih_hari: int
     * }
     */
    public static function hitungPentahapanSetelahRemisi(
        Carbon $tglAwal,
        int $tahun,
        int $bulan,
        int $hari,
        int $num,
        int $den,
        int $totalHariRemisi,
        ?Carbon $tglMinimal = null
    ): array {
        // 1. Masa pidana dalam satuan Bulan & Hari (mengikuti satuan Remisi)
        $totalBulanPidana = ($tahun * 12) + $bulan;
        $pidanaBulanTeks = self::formatRemisiBulanHari(($totalBulanPidana * 30) + $hari);

        // 2. Hasil (Fraksi x Masa Pidana) dalam satuan Bulan & Hari
        [$fThn, $fBln, $fHri] = self::hitungDurasiFraksi($tahun, $bulan, $hari, $num, $den);
        $totalBulanFraksi = ($fThn * 12) + $fBln;
        $totalHariFraksi = ($totalBulanFraksi * 30) + $fHri;
        $fraksiBulanTeks = self::formatRemisiBulanHari($totalHariFraksi);

        // 3. Pengurangan ((Fraksi x Masa Pidana) - Remisi) dalam satuan Bulan & Hari
        $sisaHariTotal = max(0, $totalHariFraksi - max(0, $totalHariRemisi));
        $selisihBulanTotal = (int) floor($sisaHariTotal / 30);
        $selisihHari = $sisaHariTotal % 30;
        $selisihBulanTeks = self::formatRemisiBulanHari($sisaHariTotal);

        // 4. Konversi kembali hasil pengurangan ke satuan Tahun, Bulan, Hari (12 Bulan = 1 Tahun)
        $selisihTahun = (int) floor($selisihBulanTotal / 12);
        $selisihBulan = $selisihBulanTotal % 12;
        $selisihTahunTeks = self::formatDurasiTeks($selisihTahun, $selisihBulan, $selisihHari);

        // 5. Jumlahkan Tanggal Mulai Ditahan + hasil (Tahun, Bulan, Hari) dengan kaidah Telram
        $tglHasil = self::tambahDurasiVonis($tglAwal, $selisihTahun, $selisihBulan, $selisihHari);

        if ($tglMinimal && $tglHasil->lt($tglMinimal)) {
            $tglHasil = $tglMinimal->copy();
        }

        return [
            'tanggal' => $tglHasil,
            'pidana_bulan_teks' => $pidanaBulanTeks,
            'fraksi_bulan_teks' => $fraksiBulanTeks,
            'selisih_bulan_teks' => $selisihBulanTeks,
            'selisih_tahun_teks' => $selisihTahunTeks,
            'selisih_tahun' => $selisihTahun,
            'selisih_bulan' => $selisihBulan,
            'selisih_hari' => $selisihHari,
        ];
    }

    /**
     * Tambahkan durasi pidana vonis ke tanggal awal:
     * - Tahun: dari tanggal ke tanggal yang sama di tahun berikutnya (addYearsNoOverflow)
     * - Sisa bulan & hari: 1 bulan = 30 hari kalender aktual
     */
    public static function tambahDurasiVonis(Carbon $tglAwal, int $tahun, int $bulan, int $hari): Carbon
    {
        $tgl = $tglAwal->copy();
        if ($tahun > 0) {
            $tgl->addYearsNoOverflow($tahun);
        }

        $sisaHari = ($bulan * 30) + $hari;
        if ($sisaHari > 0) {
            $tgl->addDays($sisaHari);
        }

        return $tgl;
    }

    /**
     * Generate seluruh jadwal Remisi Umum dan Remisi Khusus untuk rentang tahun
     *
     * @return array<int, array{nama_event: string, jenis: string, tanggal: string}>
     */
    protected function generateRemisiEvents(string $agama, int $startYear, int $endYear): array
    {
        $events = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            // Remisi Umum (17 Agustus)
            $events[] = [
                'nama_event' => "Remisi Umum (17 Agustus {$year})",
                'jenis' => 'RU',
                'tanggal' => "{$year}-08-17",
            ];

            // Remisi Khusus (Hari Raya Keagamaan)
            $rkDates = $this->getTanggalHariRaya($agama, $year);
            foreach ($rkDates as $rkDate) {
                $events[] = [
                    'nama_event' => "Remisi Khusus {$rkDate['label']} ({$year})",
                    'jenis' => 'RK',
                    'tanggal' => $rkDate['tanggal'],
                ];
            }
        }

        return $events;
    }

    /**
     * Dapatkan tanggal Hari Raya Keagamaan berdasarkan agama dan tahun
     *
     * @return array<int, array{label: string, tanggal: string}>
     */
    protected function getTanggalHariRaya(string $agama, int $year): array
    {
        switch ($agama) {
            case 'islam':
                return $this->getTanggalIdulFitri($year);
            case 'kristen':
            case 'katolik':
                return [
                    ['label' => 'Natal', 'tanggal' => "{$year}-12-25"],
                ];
            case 'hindu':
                return [
                    ['label' => 'Nyepi', 'tanggal' => $this->getTanggalNyepi($year)],
                ];
            case 'buddha':
                return [
                    ['label' => 'Waisak', 'tanggal' => $this->getTanggalWaisak($year)],
                ];
            case 'khonghucu':
                return [
                    ['label' => 'Tahun Baru Imlek', 'tanggal' => $this->getTanggalImlek($year)],
                ];
            default:
                return $this->getTanggalIdulFitri($year);
        }
    }

    /**
     * Data astronomis kalender Idul Fitri (1 Syawal) Indonesia
     *
     * @return array<int, array{label: string, tanggal: string}>
     */
    protected function getTanggalIdulFitri(int $year): array
    {
        $idulFitriTable = [
            2015 => ['2015-07-17'],
            2016 => ['2016-07-06'],
            2017 => ['2017-06-25'],
            2018 => ['2018-06-15'],
            2019 => ['2019-06-05'],
            2020 => ['2020-05-24'],
            2021 => ['2021-05-13'],
            2022 => ['2022-05-02'],
            2023 => ['2023-04-22'],
            2024 => ['2024-04-10'],
            2025 => ['2025-03-31'],
            2026 => ['2026-03-20'],
            2027 => ['2027-03-10'],
            2028 => ['2028-02-27'],
            2029 => ['2029-02-15'],
            2030 => ['2030-02-05', '2030-12-26'], // Tahun 2030 mengalami 2 kali Idul Fitri dalam 1 tahun Masehi
            2031 => ['2031-01-15'],
            2032 => ['2032-01-05', '2032-12-24'],
            2033 => ['2033-12-13'],
            2034 => ['2034-12-02'],
            2035 => ['2035-11-22'],
            2036 => ['2036-11-10'],
            2037 => ['2037-10-31'],
            2038 => ['2038-10-20'],
            2039 => ['2039-10-09'],
            2040 => ['2040-09-28'],
            2041 => ['2041-09-17'],
            2042 => ['2042-09-07'],
            2043 => ['2043-08-27'],
            2044 => ['2044-08-15'],
            2045 => ['2045-08-05'],
        ];

        if (isset($idulFitriTable[$year])) {
            $results = [];
            foreach ($idulFitriTable[$year] as $dateStr) {
                $results[] = ['label' => 'Idul Fitri', 'tanggal' => $dateStr];
            }

            return $results;
        }

        // Estimasi kalender Hijriah (geser ~11 hari per tahun) untuk tahun di luar tabel
        $diffYears = $year - 2026;
        $approxDays = (int) round($diffYears * 354.367);
        $approxDate = Carbon::parse('2026-03-20')->addDays($approxDays);

        return [
            ['label' => 'Idul Fitri', 'tanggal' => $approxDate->toDateString()],
        ];
    }

    /**
     * Data Kalender Hari Raya Nyepi
     */
    protected function getTanggalNyepi(int $year): string
    {
        $nyepiTable = [
            2020 => '2020-03-25',
            2021 => '2021-03-14',
            2022 => '2022-03-03',
            2023 => '2023-03-22',
            2024 => '2024-03-11',
            2025 => '2025-03-29',
            2026 => '2026-03-19',
            2027 => '2027-03-08',
            2028 => '2028-03-26',
            2029 => '2029-03-15',
            2030 => '2030-03-05',
            2031 => '2031-03-24',
            2032 => '2032-03-12',
            2033 => '2033-03-31',
            2034 => '2034-03-20',
            2035 => '2035-03-10',
            2036 => '2036-03-28',
            2037 => '2037-03-17',
            2038 => '2038-03-06',
            2039 => '2039-03-25',
            2040 => '2040-03-13',
        ];

        return $nyepiTable[$year] ?? "{$year}-03-20";
    }

    /**
     * Data Kalender Hari Raya Waisak
     */
    protected function getTanggalWaisak(int $year): string
    {
        $waisakTable = [
            2020 => '2020-05-07',
            2021 => '2021-05-26',
            2022 => '2022-05-16',
            2023 => '2023-06-04',
            2024 => '2024-05-23',
            2025 => '2025-05-12',
            2026 => '2026-05-31',
            2027 => '2027-05-20',
            2028 => '2028-05-08',
            2029 => '2029-05-27',
            2030 => '2030-05-16',
            2031 => '2031-05-06',
            2032 => '2032-05-24',
            2033 => '2033-05-13',
            2034 => '2034-06-02',
            2035 => '2035-05-22',
            2036 => '2036-05-10',
            2037 => '2037-05-29',
            2038 => '2038-05-18',
            2039 => '2039-05-07',
            2040 => '2040-05-25',
        ];

        return $waisakTable[$year] ?? "{$year}-05-20";
    }

    /**
     * Data Kalender Tahun Baru Imlek
     */
    protected function getTanggalImlek(int $year): string
    {
        $imlekTable = [
            2020 => '2020-01-25',
            2021 => '2021-02-12',
            2022 => '2022-02-01',
            2023 => '2023-01-22',
            2024 => '2024-02-10',
            2025 => '2025-01-29',
            2026 => '2026-02-17',
            2027 => '2027-02-06',
            2028 => '2028-01-26',
            2029 => '2029-02-13',
            2030 => '2030-02-03',
            2031 => '2031-01-23',
            2032 => '2032-02-11',
            2033 => '2033-01-31',
            2034 => '2034-02-19',
            2035 => '2035-02-08',
            2036 => '2036-01-28',
            2037 => '2037-02-15',
            2038 => '2038-02-04',
            2039 => '2039-01-24',
            2040 => '2040-02-12',
        ];

        return $imlekTable[$year] ?? "{$year}-02-05";
    }

    /**
     * Generate Dokumen PDF Resmi untuk Lembar Estimasi Telram & Remisi
     *
     * @param  array<string, mixed>  $data
     */
    public static function generatePdfReport(array $data): string
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
                $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages().' | Perkiraan Perolehan Remisi Sultan Banten', 0, 0, 'R');
            }
        };

        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor(auth()->user()->nama_lengkap ?? 'Petugas Registrasi');
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nama_wbp'] ?? 'Narapidana');
        $pdf->SetTitle("Perkiraan_Perolehan_Remisi_{$cleanName}");
        $pdf->SetMargins(14, 14, 14);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        $html = self::renderPdfTemplateHtml($data);
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    /**
     * Render Template HTML untuk Dokumen PDF
     *
     * @param  array<string, mixed>  $data
     */
    public static function renderPdfTemplateHtml(array $data, ?Instansi $instansi = null): string
    {
        $namaWbp = htmlspecialchars($data['nama_wbp'] ?? 'Narapidana');
        $noRegister = htmlspecialchars($data['no_register'] ?? '-');
        $perkaraPasal = htmlspecialchars($data['perkara_pasal'] ?? '-');
        $tglAwal = htmlspecialchars($data['formatted']['tgl_awal'] ?? '-');
        $tglPutusan = htmlspecialchars($data['formatted']['tgl_putusan'] ?? '-');
        $masaTahananTeks = htmlspecialchars($data['formatted']['masa_tahanan'] ?? '-');
        $lamaVonis = htmlspecialchars($data['lama_pidana_teks'] ?? '-');

        $tglUsul = htmlspecialchars($data['formatted']['tgl_mulai_pengusulan'] ?? '-');
        $tgl23Def = htmlspecialchars($data['formatted']['tgl_2_3_definitif'] ?? '-');
        $tgl12Def = htmlspecialchars($data['formatted']['tgl_1_2_definitif'] ?? '-');
        $tgl13Def = htmlspecialchars($data['formatted']['tgl_1_3_definitif'] ?? ($data['formatted']['tgl_1_3_murni'] ?? '-'));
        $tglEkspirasiDef = htmlspecialchars($data['formatted']['ekspirasi_definitif'] ?? '-');
        $hasSubsider = ! empty($data['has_subsider']);
        $subsiderTeks = htmlspecialchars($data['formatted']['subsider_teks'] ?? '-');
        $tglEkspirasiSubMurni = htmlspecialchars($data['formatted']['ekspirasi_subsider_murni'] ?? '-');
        $tglEkspirasiSubDef = htmlspecialchars($data['formatted']['ekspirasi_subsider_definitif'] ?? '-');

        $rowSubsiderPdf = '';
        $borderBottomPotah = '';
        if ($hasSubsider || ! empty($data['has_denda'])) {
            $borderBottomPotah = 'border-bottom: 0.5px solid #e2e8f0;';
            $subsiderIdentitasTeks = htmlspecialchars($data['formatted']['subsider_identitas_teks'] ?? ($data['subsider_identitas_teks'] ?? '-'));
            $rowSubsiderPdf = <<<SUB
    <tr>
        <td width="19%" style="color: #475569; padding: 2px 6px; font-size: 8.5pt;">Pidana Subsider</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="79%" colspan="4" style="font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$subsiderIdentitasTeks}</td>
    </tr>
SUB;
        }
        $tglEkspirasiMurni = htmlspecialchars($data['formatted']['ekspirasi_murni'] ?? '-');
        $totalRemisiHari = (int) ($data['total_proyeksi_remisi_hari'] ?? 0);
        $totalRemisiTeks = htmlspecialchars($data['total_proyeksi_remisi_teks'] ?? '0 Hari');

        $statusPesan = htmlspecialchars($data['status_pengusulan']['pesan'] ?? '-');

        $tahun = (int) ($data['tahun'] ?? 0);
        $bulan = (int) ($data['bulan'] ?? 0);
        $hari = (int) ($data['hari'] ?? 0);
        $totalHariVonis = (int) ($data['total_hari_vonis'] ?? (($tahun * 360) + ($bulan * 30) + $hari));
        $totalHariVonisFmt = number_format($totalHariVonis, 0, ',', '.');
        $masaTahananHari = (int) ($data['masa_tahanan_hari'] ?? 0);
        $sisaHariPascaPutusan = (int) ($data['sisa_hari_pasca_putusan'] ?? 0);
        $sisaPidanaPascaPutusanTeks = htmlspecialchars($data['sisa_pidana_pasca_putusan_teks'] ?? '-');

        [$y13, $m13, $d13] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 3);
        [$y12, $m12, $d12] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 2);
        [$y23, $m23, $d23] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 2, 3);

        $durasi13Teks = htmlspecialchars(self::formatDurasiTeks($y13, $m13, $d13));
        $durasi12Teks = htmlspecialchars(self::formatDurasiTeks($y12, $m12, $d12));
        $durasi23Teks = htmlspecialchars(self::formatDurasiTeks($y23, $m23, $d23));

        $tglAwalCarbon = ! empty($data['tgl_awal']) ? Carbon::parse($data['tgl_awal'])->startOfDay() : Carbon::today();
        $calcPdf12 = self::hitungPentahapanSetelahRemisi($tglAwalCarbon, $tahun, $bulan, $hari, 1, 2, $totalRemisiHari);
        $calcPdf23 = self::hitungPentahapanSetelahRemisi($tglAwalCarbon, $tahun, $bulan, $hari, 2, 3, $totalRemisiHari);

        $pidanaBulanTeks = htmlspecialchars($data['pidana_bulan_teks'] ?? $calcPdf12['pidana_bulan_teks']);
        $fraksi12BulanTeks = htmlspecialchars($data['fraksi_1_2_bulan_teks'] ?? $calcPdf12['fraksi_bulan_teks']);
        $selisih12BulanTeks = htmlspecialchars($data['selisih_1_2_bulan_teks'] ?? $calcPdf12['selisih_bulan_teks']);
        $selisih12TahunTeks = htmlspecialchars($data['selisih_1_2_tahun_teks'] ?? $calcPdf12['selisih_tahun_teks']);

        $fraksi23BulanTeks = htmlspecialchars($data['fraksi_2_3_bulan_teks'] ?? $calcPdf23['fraksi_bulan_teks']);
        $selisih23BulanTeks = htmlspecialchars($data['selisih_2_3_bulan_teks'] ?? $calcPdf23['selisih_bulan_teks']);
        $selisih23TahunTeks = htmlspecialchars($data['selisih_2_3_tahun_teks'] ?? $calcPdf23['selisih_tahun_teks']);

        $tgl13Murni = htmlspecialchars($data['formatted']['tgl_1_3_murni'] ?? '-');
        $tgl12Murni = htmlspecialchars($data['formatted']['tgl_1_2_murni'] ?? '-');
        $tgl23Murni = htmlspecialchars($data['formatted']['tgl_2_3_murni'] ?? '-');

        $tglCetakIndo = Carbon::now()->locale('id')->isoFormat('D MMMM Y');
        $petugasNama = htmlspecialchars(auth()->user()->nama_lengkap ?? 'Petugas Registrasi');

        $judulProgram = htmlspecialchars($data['judul_program'] ?? 'PERHITUNGAN PEMBEBASAN BERSYARAT (PB)');
        $subsiderIdentitasTeks = htmlspecialchars($data['formatted']['subsider_identitas_teks'] ?? ($data['subsider_identitas_teks'] ?? '-'));
        $hasSubsiderBb = ! empty($data['has_subsider']) && (($data['subsider_status'] ?? 'bb') === 'bb');

        $num = $data['numeric'] ?? [];
        $tglAwalNum = htmlspecialchars($num['tgl_awal'] ?? '-');
        $tglPutusanNum = htmlspecialchars($num['tgl_putusan'] ?? '-');
        $ekspirasiAwalNum = htmlspecialchars($num['ekspirasi_awal'] ?? ($num['ekspirasi_sebelum_remisi'] ?? '-'));
        $ekspirasiSebelumRemisiNum = htmlspecialchars($num['ekspirasi_sebelum_remisi'] ?? '-');
        $ekspirasiSubSebelumRemisiNum = htmlspecialchars($num['ekspirasi_subsider_sebelum_remisi'] ?? '-');
        $tgl12DefNum = htmlspecialchars($num['tgl_1_2_definitif'] ?? '-');
        $tgl23DefNum = htmlspecialchars($num['tgl_2_3_definitif'] ?? '-');
        $tgl23SubDefNum = htmlspecialchars($num['tgl_2_3_subsider_definitif'] ?? '-');
        $ekspirasiDefNum = htmlspecialchars($num['ekspirasi_definitif'] ?? '-');
        $ekspirasiSubDefNum = htmlspecialchars($num['ekspirasi_subsider_definitif'] ?? '-');
        $tglUsulNum = htmlspecialchars($num['tgl_mulai_pengusulan'] ?? '-');

        $ekspirasiAwalIndo = htmlspecialchars($data['formatted']['ekspirasi_sebelum_remisi'] ?? '-');
        $ekspirasiSebelumRemisiIndo = htmlspecialchars($data['formatted']['ekspirasi_sebelum_remisi'] ?? '-');
        $ekspirasiSubSebelumRemisiIndo = htmlspecialchars($data['formatted']['ekspirasi_subsider_sebelum_remisi'] ?? '-');
        $tgl12DefIndo = htmlspecialchars($data['formatted']['tgl_1_2_definitif'] ?? '-');
        $tgl23DefIndo = htmlspecialchars($data['formatted']['tgl_2_3_definitif'] ?? '-');
        $tgl23SubDefIndo = htmlspecialchars($data['formatted']['tgl_2_3_subsider_definitif'] ?? '-');
        $ekspirasiDefIndo = htmlspecialchars($data['formatted']['ekspirasi_definitif'] ?? '-');
        $ekspirasiSubDefIndo = htmlspecialchars($data['formatted']['ekspirasi_subsider_definitif'] ?? '-');
        $tglUsulIndo = htmlspecialchars($data['formatted']['tgl_mulai_pengusulan'] ?? '-');

        $opsiPdf = $data['opsi_pengusulan'] ?? [];
        $hasBenturanPdf = ! empty($opsiPdf['has_benturan']);
        $remisiTerkPdf = $opsiPdf['remisi_terakhir'] ?? [];
        $o1Pdf = $opsiPdf['opsi_1_pra_remisi'] ?? [];
        $o2Pdf = $opsiPdf['opsi_2_pasca_remisi'] ?? [];

        $tglUsulMurniPdfIndo = htmlspecialchars($data['formatted']['tgl_mulai_pengusulan_murni'] ?? ($opsiPdf['tgl_usul_murni_indo'] ?? '-'));
        $tglUsulMurniPdfNum = htmlspecialchars($num['tgl_mulai_pengusulan_murni'] ?? ($opsiPdf['tgl_usul_murni_numeric'] ?? '-'));
        $remisiTerkNamaPdf = htmlspecialchars($remisiTerkPdf['nama_event'] ?? 'Remisi Terakhir');
        $remisiTerkTglPdfIndo = htmlspecialchars($remisiTerkPdf['tanggal_indo'] ?? '-');

        $o1Tgl23PdfNum = htmlspecialchars($o1Pdf['tgl_2_3_numeric'] ?? '-');
        $o1Tgl23PdfIndo = htmlspecialchars($o1Pdf['tgl_2_3_indo'] ?? '-');
        $o1TglUsulPdfNum = htmlspecialchars($o1Pdf['tgl_usul_numeric'] ?? '-');
        $o1TglUsulPdfIndo = htmlspecialchars($o1Pdf['tgl_usul_indo'] ?? '-');

        $o2TglUsulPdfNum = htmlspecialchars($o2Pdf['tgl_usul_numeric'] ?? '-');
        $o2TglUsulPdfIndo = htmlspecialchars($o2Pdf['tgl_usul_indo'] ?? '-');

        $cell23Pdf = $hasBenturanPdf
            ? '<b style="color: #047857; font-size: 8.5pt;">'.$tgl23Def.'</b><br><span style="font-size: 6.5pt; color: #15803d; font-weight: bold;">(Opsi 1: '.$o1Tgl23PdfIndo.')</span>'
            : '<b style="color: #047857; font-size: 9pt;">'.$tgl23Def.'</b>';

        $labelPentahapanPdf = ! empty($data['has_riwayat_penahanan']) ? 'Tgl. Mulai Ditahan (Efektif)' : 'Tgl. Mulai Ditahan';
        $tglPentahapanPdfNum = ! empty($data['has_riwayat_penahanan']) ? htmlspecialchars($num['tgl_mulai_ditahan_efektif'] ?? $tglAwalNum) : $tglAwalNum;

        $cardSpacerPdf = '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size: 6pt; line-height: 0.7;"><tr><td>&nbsp;</td></tr></table>';

        $totalLuarUptPascaPdf = (int) ($data['total_hari_luar_upt_pasca'] ?? 0);

        $potahPdfHtml = '';
        if (! empty($data['tgl_putusan'])) {
            if (! empty($data['has_riwayat_penahanan']) && ! empty($data['riwayat_penahanan'])) {
                $rowsRiwayatPdf = '';
                foreach ($data['riwayat_penahanan'] as $r) {
                    $metodePdf = htmlspecialchars($r['metode_hitung'] ?? ($r['is_jeda'] ? 'Eksklusif (T2-T1)' : 'Inklusif'));
                    $potahBadgePdf = $r['is_jeda']
                        ? '<span style="color: #b91c1c; font-weight: bold;">0 hr (Luar UPT)</span>'
                        : '<b style="color: #166534;">'.$r['hari_potah'].' hr</b>';
                    $rowsRiwayatPdf .= '<tr nobr="true">'.
                        '<td width="5%" align="center">'.$r['no'].'</td>'.
                        '<td width="26%" align="left"><b>'.htmlspecialchars($r['label_jenis']).'</b><br><span style="font-size: 6pt; color: #64748b;">'.htmlspecialchars($r['dasar_hukum']).' | '.$metodePdf.'</span></td>'.
                        '<td width="23%" align="center">'.htmlspecialchars($r['periode_teks']).'</td>'.
                        '<td width="10%" align="center">'.$r['hari_kalender'].' hr</td>'.
                        '<td width="14%" align="center">'.htmlspecialchars($r['rasio']).'</td>'.
                        '<td width="12%" align="center">'.$potahBadgePdf.'</td>'.
                        '<td width="10%" align="left" style="font-size: 6.5pt;">'.htmlspecialchars($r['keterangan']).'</td>'.
                    '</tr>';
                }

                $totalKal = $data['total_hari_kalender_tahanan'] ?? 0;
                $totalLuarUptPdf = (int) ($data['total_hari_luar_upt'] ?? ($data['total_hari_jeda_tahanan'] ?? 0));
                $tglAwalEfektifNum = htmlspecialchars($num['tgl_mulai_ditahan_efektif'] ?? '-');
                $tglAwalEfektifIndo = htmlspecialchars($data['formatted']['tgl_mulai_ditahan_efektif'] ?? '-');
                $jedaTeksPdf = $totalLuarUptPdf > 0 ? " (Luar UPT: {$totalLuarUptPdf} hr)" : '';
                $rowLuarUptPdf = $totalLuarUptPdf > 0
                    ? '<tr><td width="30%" style="color: #b91c1c; font-weight: bold;">Total Hari di Luar UPT</td><td width="4%" align="center" style="font-weight: bold;">=</td><td width="66%"><b style="color: #b91c1c;">'.$totalLuarUptPdf.' Hari</b> (Eksklusif T2-T1, 0 Potah'.($totalLuarUptPascaPdf > 0 ? "; Pasca-Putusan: {$totalLuarUptPascaPdf} hr" : '').')</td></tr>'
                    : '';

                $potahPdfHtml = <<<POTAH
{$cardSpacerPdf}
<!-- A. POTONGAN TAHANAN (POTAH) & HARI DI LUAR UPT -->
<table width="100%" cellpadding="0" cellspacing="0" style="border: 0.5px solid #bbf7d0;">
    <tr style="background-color: #f0fdf4;">
        <td style="font-weight: bold; font-size: 8.5pt; color: #166534; padding: 2.5px 6px;">A. POTONGAN TAHANAN (POTAH) &amp; HARI DI LUAR UPT (PS. 22 &amp; 31 KUHAP)</td>
    </tr>
    <tr>
        <td style="padding: 2.5px 6px;"><table width="100%" border="1" cellpadding="1.5" cellspacing="0" style="font-size: 7pt; border-collapse: collapse; border-color: #cbd5e1; margin-bottom: 3px;">
                <thead>
                    <tr style="background-color: #f1f5f9; font-weight: bold; text-align: center;">
                        <th width="5%">No</th>
                        <th width="26%">Status Penahanan</th>
                        <th width="23%">Periode</th>
                        <th width="10%">Kalender</th>
                        <th width="14%">Rasio KUHAP</th>
                        <th width="12%">Hari Potah</th>
                        <th width="10%">Ket.</th>
                    </tr>
                </thead>
                <tbody>
                    {$rowsRiwayatPdf}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold;">
                        <td colspan="3" align="right" style="padding-right: 4px;">Total Kalender{$jedaTeksPdf}:</td>
                        <td align="center">{$totalKal} hr</td>
                        <td align="right" style="padding-right: 4px;">Total Potah:</td>
                        <td align="center" style="color: #166534;"><b>{$masaTahananHari} hr</b></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size: 8.5pt; line-height: 1.12;">
                {$rowLuarUptPdf}
                <tr>
                    <td width="30%" style="color: #0f172a; font-weight: bold;">Total Potah Diperoleh</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="66%"><b style="color: #166534; font-size: 9pt;">{$masaTahananTeks} ({$masaTahananHari} Hari)</b></td>
                </tr>
                <tr>
                    <td width="30%" style="color: #0f172a; font-weight: bold;">Tgl. Mulai Ditahan (Efektif)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="66%">Tgl. Putusan - Total Potah</td>
                </tr>
                <tr>
                    <td width="30%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="66%">{$tglPutusanNum} - {$masaTahananHari} Hari</td>
                </tr>
                <tr>
                    <td width="30%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="66%"><b style="color: #0284c7; font-size: 9pt;">{$tglAwalEfektifNum}</b> ({$tglAwalEfektifIndo})</td>
                </tr>
            </table></td>
    </tr>
</table>
POTAH;
            } else {
                $potahPdfHtml = <<<POTAH
{$cardSpacerPdf}
<!-- A. POTONGAN TAHANAN (POTAH) -->
<table width="100%" cellpadding="0" cellspacing="0" style="border: 0.5px solid #bbf7d0;">
    <tr style="background-color: #f0fdf4;">
        <td style="font-weight: bold; font-size: 8.5pt; color: #166534; padding: 2.5px 6px;">A. POTONGAN TAHANAN (POTAH)</td>
    </tr>
    <tr>
        <td style="padding: 2.5px 6px;"><table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size: 8.5pt; line-height: 1.12;">
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">Potah</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Tgl. Mulai Ditahan - Tgl. Putusan</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglAwalNum} - {$tglPutusanNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #166534; font-size: 9pt;">{$masaTahananTeks}</b></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
POTAH;
            }
        }

        $luarUptFormulaPdf = $totalLuarUptPascaPdf > 0 ? ' + Hari di Luar UPT Pasca-Putusan' : '';
        $luarUptValPdf = $totalLuarUptPascaPdf > 0 ? " + {$totalLuarUptPascaPdf} Hari" : '';

        $barisEkspirasiAwalPdf = '';
        if (! empty($data['tgl_putusan'])) {
            $barisEkspirasiAwalPdf = <<<EKS_AWAL_PDF
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">Ekspirasi Awal</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Tgl. Putusan + Lama Pidana Pokok</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPutusanNum} + {$lamaVonis}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiAwalNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #1e40af; font-size: 9.5pt;">{$ekspirasiAwalIndo}</b></td>
                </tr>
                <tr style="font-size: 2pt; line-height: 0.3;"><td colspan="3">&nbsp;</td></tr>
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">Ekspirasi Akhir</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Ekspirasi Awal - Potah{$luarUptFormulaPdf}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiAwalNum} - {$masaTahananTeks}{$luarUptValPdf}</td>
                </tr>
EKS_AWAL_PDF;
        } else {
            $barisEkspirasiAwalPdf = <<<EKS_AWAL_PDF
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">Ekspirasi Akhir</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Tgl. Mulai Ditahan + Lama Pidana Pokok</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglAwalNum} + {$lamaVonis}</td>
                </tr>
EKS_AWAL_PDF;
        }

        $subsiderPdfSectionB = '';
        if ($hasSubsiderBb) {
            $subsiderPdfSectionB = <<<SUB_B_PDF
                <tr style="font-size: 2pt; line-height: 0.3;"><td colspan="3">&nbsp;</td></tr>
                <tr>
                    <td width="28%" style="color: #991b1b; font-weight: bold;">Ekspirasi (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Ekspirasi Akhir + Pidana Subsider</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiSebelumRemisiNum} + {$subsiderTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiSubSebelumRemisiNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #991b1b; font-size: 9.5pt;">{$ekspirasiSubSebelumRemisiIndo}</b></td>
                </tr>
SUB_B_PDF;
        }

        $subsiderPdf23 = '';
        if ($hasSubsiderBb) {
            $subsiderPdf23 = <<<SUB_23_PDF
                <tr style="font-size: 2pt; line-height: 0.3;"><td colspan="3">&nbsp;</td></tr>
                <tr>
                    <td width="28%" style="color: #14532d; font-weight: bold;">&bull; 2/3 (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">2/3 Masa Pidana + Pidana Subsider</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tgl23DefNum} + {$subsiderTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tgl23SubDefNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #14532d; font-size: 9.5pt;">{$tgl23SubDefIndo}</b></td>
                </tr>
SUB_23_PDF;
        }

        $subsiderPdfEkspirasiC = '';
        if ($hasSubsiderBb) {
            $subsiderPdfEkspirasiC = <<<SUB_EKS_C_PDF
                <tr style="font-size: 2pt; line-height: 0.3;"><td colspan="3">&nbsp;</td></tr>
                <tr>
                    <td width="28%" style="color: #991b1b; font-weight: bold;">&bull; Ekspirasi (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Ekspirasi Akhir + Pidana Subsider (Tanpa Remisi)</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiDefNum} + {$subsiderTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiSubDefNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #991b1b; font-size: 9.5pt;">{$ekspirasiSubDefIndo}</b> <span style="font-size: 7.5pt; color: #555;">(Tanpa Remisi)</span></td>
                </tr>
SUB_EKS_C_PDF;
        }

        $baris23PdfHtml = '';
        if ($hasBenturanPdf) {
            $remisiTerkJenisPdf = htmlspecialchars($remisiTerkPdf['jenis'] ?? 'RK');
            $o1RemisiTeksPdf = htmlspecialchars($o1Pdf['remisi_teks'] ?? '0 Hari');
            $o1SelisihTahunTeksPdf = htmlspecialchars($o1Pdf['selisih_2_3_tahun_teks'] ?? $fraksi23BulanTeks);
            $sub23Opsi1Pdf = '';
            $sub23Opsi2Pdf = '';
            if ($hasSubsiderBb) {
                $o1SubNumPdf = htmlspecialchars($o1Pdf['tgl_2_3_subsider_numeric'] ?? '-');
                $o1SubIndoPdf = htmlspecialchars($o1Pdf['tgl_2_3_subsider_indo'] ?? '-');
                $sub23Opsi1Pdf = <<<SUB_O1_PDF
                <tr>
                    <td width="28%" style="padding-left: 4px; color: #14532d; font-size: 7.2pt;">- (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold; font-size: 7.2pt;">=</td>
                    <td width="68%" style="font-size: 7.2pt;">{$o1Tgl23PdfNum} + {$subsiderTeks} = {$o1SubNumPdf} = <b style="color: #14532d;">{$o1SubIndoPdf}</b></td>
                </tr>
SUB_O1_PDF;
                $sub23Opsi2Pdf = <<<SUB_O2_PDF
                <tr>
                    <td width="28%" style="padding-left: 4px; color: #9a3412; font-size: 7.2pt;">- (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold; font-size: 7.2pt;">=</td>
                    <td width="68%" style="font-size: 7.2pt;">{$tgl23DefNum} + {$subsiderTeks} = {$tgl23SubDefNum} = <b style="color: #9a3412;">{$tgl23SubDefIndo}</b></td>
                </tr>
SUB_O2_PDF;
            }

            $baris23PdfHtml = <<<BARIS_23_BENTURAN_PDF
                <tr>
                    <td colspan="3" style="padding-top: 2px; padding-bottom: 2px;">
                        <span style="color: #b45309; font-weight: bold; font-size: 8.3pt;">&bull; Tersedia 2 Opsi Perhitungan 2/3 Masa Pidana:</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="padding: 1px 0 2px 0;">
                        <table width="100%" cellpadding="1.5" cellspacing="0" border="0" style="background-color: #f0fdf4; border: 0.5px solid #86efac; font-size: 7.6pt; line-height: 1.12;">
                            <tr style="background-color: #dcfce7;">
                                <td colspan="3" style="color: #15803d; font-weight: bold; font-size: 7.8pt; padding: 1.5px 4px;">Opsi 1 = 2/3 Sebelum Remisi {$remisiTerkNamaPdf} ({$remisiTerkJenisPdf})</td>
                            </tr>
                            <tr>
                                <td width="28%" style="color: #166534; padding-left: 4px;">- Hitungan 2/3</td>
                                <td width="4%" align="center" style="font-weight: bold;">=</td>
                                <td width="68%">{$labelPentahapanPdf} + ((2/3 x {$lamaVonis}) - {$o1RemisiTeksPdf})</td>
                            </tr>
                            <tr>
                                <td width="28%"></td>
                                <td width="4%" align="center" style="font-weight: bold;">=</td>
                                <td width="68%">{$tglPentahapanPdfNum} + {$o1SelisihTahunTeksPdf} = {$o1Tgl23PdfNum} = <b style="color: #15803d; font-size: 8.5pt;">{$o1Tgl23PdfIndo}</b></td>
                            </tr>
                            {$sub23Opsi1Pdf}
                            <tr>
                                <td width="28%" style="color: #166534; font-weight: bold; padding-left: 4px;">- Prediksi Tgl. Usulan</td>
                                <td width="4%" align="center" style="font-weight: bold;">=</td>
                                <td width="68%">Tgl. 2/3 - 60 Hari = {$o1Tgl23PdfNum} - 60 Hari = {$o1TglUsulPdfNum} = <b style="color: #15803d; font-size: 8.5pt;">{$o1TglUsulPdfIndo}</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr style="font-size: 1.5pt; line-height: 0.2;"><td colspan="3">&nbsp;</td></tr>
                <tr>
                    <td colspan="3" style="padding: 1px 0 2px 0;">
                        <table width="100%" cellpadding="1.5" cellspacing="0" border="0" style="background-color: #fff7ed; border: 0.5px solid #fdba74; font-size: 7.6pt; line-height: 1.12;">
                            <tr style="background-color: #ffedd5;">
                                <td colspan="3" style="color: #c2410c; font-weight: bold; font-size: 7.8pt; padding: 1.5px 4px;">Opsi 2 = 2/3 Setelah Remisi {$remisiTerkNamaPdf} ({$remisiTerkJenisPdf})</td>
                            </tr>
                            <tr>
                                <td width="28%" style="color: #9a3412; padding-left: 4px;">- Hitungan 2/3</td>
                                <td width="4%" align="center" style="font-weight: bold;">=</td>
                                <td width="68%">{$labelPentahapanPdf} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                            </tr>
                            <tr>
                                <td width="28%"></td>
                                <td width="4%" align="center" style="font-weight: bold;">=</td>
                                <td width="68%">{$tglPentahapanPdfNum} + {$selisih23TahunTeks} = {$tgl23DefNum} = <b style="color: #c2410c; font-size: 8.5pt;">{$tgl23DefIndo}</b></td>
                            </tr>
                            {$sub23Opsi2Pdf}
                            <tr>
                                <td width="28%" style="color: #9a3412; font-weight: bold; padding-left: 4px;">- Prediksi Tgl. Usulan</td>
                                <td width="4%" align="center" style="font-weight: bold;">=</td>
                                <td width="68%">{$tgl23DefNum} - 60 hr = <b>{$tglUsulMurniPdfNum}</b> &lt; tgl remisi <b>{$remisiTerkTglPdfIndo}</b>.<br>Pada SDP, usulan tidak dapat kurang dari tgl remisi, disesuaikan tepat Hari H SK = {$o2TglUsulPdfNum} = <b style="color: #c2410c; font-size: 8.5pt;">{$o2TglUsulPdfIndo}</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
BARIS_23_BENTURAN_PDF;
        } else {
            $baris23PdfHtml = <<<BARIS_23_NORMAL_PDF
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">&bull; 2/3 Masa Pidana</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$labelPentahapanPdf} + ((2/3 x Masa Pidana) - Remisi)</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + ((2/3 x {$pidanaBulanTeks}) - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + ({$fraksi23BulanTeks} - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + {$selisih23BulanTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + {$selisih23TahunTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tgl23DefNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #15803d; font-size: 9.5pt;">{$tgl23DefIndo}</b></td>
                </tr>
                {$subsiderPdf23}
BARIS_23_NORMAL_PDF;
        }

        // Render Ledger Rows (Tabel Rincian Proyeksi Remisi 7 Kolom)
        $ledgerRowsHtml = '';
        $ledger = (array) ($data['proyeksi_ledger'] ?? []);
        if (empty($ledger)) {
            $ledgerRowsHtml = '<tr><td colspan="7" width="100%" align="center" style="color: #64748b; padding: 12px; border: 0.5px solid #cbd5e1;">Tidak ada jadwal event remisi sebelum tanggal ekspirasi.</td></tr>';
        } else {
            foreach ($ledger as $idx => $r) {
                $no = $idx + 1;
                $evNama = htmlspecialchars($r['nama_event'] ?? '-');
                $evTgl = htmlspecialchars($r['tanggal_indo'] ?? '-');
                $evDijalani = htmlspecialchars($r['hari_dijalani'] ?? 0);
                $evMasaTeks = htmlspecialchars($r['masa_dijalani_teks'] ?? '');
                $evBesaran = htmlspecialchars($r['besaran_teks'] ?? '0 Hari');
                $evBesaranHari = (int) ($r['besaran_hari'] ?? 0);
                $evAkumulasi = htmlspecialchars($r['total_akumulasi_hari'] ?? 0).' hr';
                $evEkspirasiBaru = htmlspecialchars($r['perkiraan_ekspirasi_baru_indo'] ?? '-');
                $ev13Baru = htmlspecialchars($r['tgl_1_3_baru_indo'] ?? '-');
                $ev12Baru = htmlspecialchars($r['tgl_1_2_baru_indo'] ?? '-');
                $ev23Baru = htmlspecialchars($r['tgl_2_3_baru_indo'] ?? '-');
                $evAlasan = htmlspecialchars($r['keterangan_alasan'] ?? ($r['keterangan_status'] ?? '-'));
                $tahap = htmlspecialchars($r['tahap'] ?? '-');
                $eligible = (bool) ($r['eligible'] ?? true);
                $jenis = $r['jenis'] ?? '';

                // Warna background baris & sorotan jenis remisi
                $bgColor = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
                if (! $eligible) {
                    $bgColor = '#fff1f2'; // Sorotan merah lembut untuk belum memenuhi syarat 6 bulan
                } elseif ($jenis === 'RD') {
                    $bgColor = '#fffbeb'; // Sorotan amber lembut untuk Remisi Dasawarsa
                } elseif ($jenis === 'RT') {
                    $bgColor = '#f0fdf4'; // Sorotan mint lembut untuk Remisi Tambahan
                }

                // Format besaran remisi
                if ($eligible) {
                    $besaranHtml = '<b style="color: #1e40af; font-size: 8.5pt;">'.$evBesaran.'</b>';
                } else {
                    $besaranHtml = '<span style="color: #e11d48; font-weight: bold; font-size: 8pt;">0 Hari</span><br><span style="color: #9f1239; font-size: 6.8pt;">(Belum 6 Bln)</span>';
                }

                // Format Subtitle Tahap Remisi
                $tahapBadge = '';
                if ($tahap && $tahap !== '-') {
                    $badgeColor = match ($jenis) {
                        'RD' => '#b45309',
                        'RT' => '#047857',
                        default => '#0284c7',
                    };
                    $tahapBadge = '<br><span style="font-size: 7.2pt; color: '.$badgeColor.'; font-weight: bold;">[Tahap: '.$tahap.']</span>';
                }

                $ledgerRowsHtml .= <<<ROW
                <tr nobr="true" bgcolor="{$bgColor}">
                    <td width="4%" align="center" style="font-weight: bold; border: 0.5px solid #cbd5e1; padding: 1.5px; color: #475569; font-size: 7.5pt;">{$no}</td>
                    <td width="20%" align="left" style="border: 0.5px solid #cbd5e1; padding: 1.5px 4px; font-size: 7.5pt; line-height: 1.1;"><b>{$evNama}</b>{$tahapBadge}</td>
                    <td width="12%" align="center" style="border: 0.5px solid #cbd5e1; padding: 1.5px; font-size: 7.5pt; color: #1e293b;">{$evTgl}</td>
                    <td width="13%" align="center" style="border: 0.5px solid #cbd5e1; padding: 1.5px; font-size: 7.5pt; line-height: 1.1;"><b>{$evDijalani} hr</b><br><span style="font-size: 6.8pt; color: #64748b;">({$evMasaTeks})</span></td>
                    <td width="13%" align="center" style="border: 0.5px solid #cbd5e1; padding: 1.5px;">{$besaranHtml}</td>
                    <td width="20%" align="left" style="border: 0.5px solid #cbd5e1; padding: 1.5px 5px; font-size: 7.5pt; line-height: 1.1;"><span style="color: #b91c1c; font-weight: bold;">Eks: {$evEkspirasiBaru}</span><br><span style="color: #0369a1; font-weight: bold;">1/3: {$ev13Baru}</span><br><span style="color: #b45309; font-weight: bold;">1/2: {$ev12Baru}</span><br><span style="color: #047857; font-weight: bold;">2/3: {$ev23Baru}</span></td>
                    <td width="18%" align="left" style="border: 0.5px solid #cbd5e1; padding: 1.5px 4px; font-size: 7pt; color: #334155; line-height: 1.08;">{$evAlasan}</td>
                </tr>
ROW;
            }
        }

        if ($hasBenturanPdf) {
            $bannerPengusulanPdfHtml = <<<BANNER_BENTURAN_PDF
<!-- HIGHLIGHT ALERT: MULAI PENGUSULAN DI SDP (2 OPSI OPERASIONAL SDP) -->
<table width="100%" cellpadding="2" cellspacing="0" style="border: 1px solid #d97706; background-color: #fffbeb; margin-bottom: 2px;">
    <tr>
        <td width="73%" valign="middle" style="padding: 1.5px 6px;">
            <span style="font-size: 7.5pt; font-weight: bold; color: #92400e; text-transform: uppercase;">MULAI PENGUSULAN DI SDP (2 OPSI REALISTIS):</span><br>
            <span style="font-size: 8pt; color: #0f172a; line-height: 1.15;">
                <b>[Opsi 1: Pra-Remisi]</b> <b style="color: #0369a1;">{$o1TglUsulPdfIndo}</b> &nbsp;|&nbsp;
                <b>[Opsi 2: Hari H SK]</b> <b style="color: #b45309;">{$o2TglUsulPdfIndo}</b>
            </span><br>
            <span style="font-size: 6.2pt; color: #78350f; line-height: 1;">Hitungan murni ({$tglUsulMurniPdfIndo}) &lt; tgl remisi ({$remisiTerkTglPdfIndo}). Di SDP, usulan tidak dapat diinput sebelum SK terbit.</span>
        </td>
        <td width="27%" align="center" valign="middle" style="background-color: #fef3c7; border-left: 1px dashed #d97706; padding: 1.5px 4px;">
            <span style="font-size: 7.2pt; color: #78350f; font-weight: bold;">2/3 MASA PIDANA</span><br>
            <span style="font-size: 9.5pt; font-weight: bold; color: #047857;">{$tgl23Def}</span><br>
            <span style="font-size: 6.5pt; color: #555;">(2/3 Pokok - Full Remisi)</span>
        </td>
    </tr>
</table>
BANNER_BENTURAN_PDF;

            $sectionDPdfHtml = <<<SEC_D_BENTURAN_PDF
<!-- D. USUL SDP (H-2 BULAN & 2 OPSI OPERASIONAL) -->
<table width="100%" cellpadding="0" cellspacing="0" style="border: 0.5px solid #fed7aa;">
    <tr style="background-color: #fff7ed;">
        <td style="font-weight: bold; font-size: 8.2pt; color: #c2410c; padding: 2px 6px;">D. REKAPITULASI USUL SDP (H-2 BULAN)</td>
    </tr>
    <tr>
        <td style="padding: 2px 6px;">
            <table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size: 7.8pt; line-height: 1.1;">
                <tr>
                    <td width="38%" style="color: #0369a1; font-weight: bold;">&bull; Opsi 1 = Usul Sebelum Remisi ({$remisiTerkJenisPdf})</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="58%">2/3 Berjalan - 60 Hari = {$o1TglUsulPdfNum} = <b style="color: #0369a1; font-size: 8.5pt;">{$o1TglUsulPdfIndo}</b></td>
                </tr>
                <tr>
                    <td width="38%" style="color: #c2410c; font-weight: bold;">&bull; Opsi 2 = Usul Setelah Remisi ({$remisiTerkJenisPdf})</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="58%">Hari H SK {$remisiTerkNamaPdf} = {$o2TglUsulPdfNum} = <b style="color: #c2410c; font-size: 8.5pt;">{$o2TglUsulPdfIndo}</b></td>
                </tr>
                <tr>
                    <td colspan="3" style="font-size: 6.8pt; color: #78350f; padding-top: 1px;">*Catatan: Hitungan murni ({$tgl23DefNum} - 60 hr = {$tglUsulMurniPdfNum} [{$tglUsulMurniPdfIndo}]) mendahului tgl remisi ({$remisiTerkTglPdfIndo}). Di SDP, usulan tidak dapat diinput sebelum SK terbit (tidak kurang dari tanggal remisi).</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
SEC_D_BENTURAN_PDF;
        } else {
            $bannerPengusulanPdfHtml = <<<BANNER_NORMAL_PDF
<!-- HIGHLIGHT ALERT: MULAI PENGUSULAN DI SDP (H-2 BULAN SEBELUM 2/3) -->
<table width="100%" cellpadding="2.5" cellspacing="0" style="border: 1px solid #d97706; background-color: #fffbeb; margin-bottom: 4px;">
    <tr>
        <td width="73%" valign="middle" style="padding: 2.5px 8px;"><span style="font-size: 8pt; font-weight: bold; color: #92400e; text-transform: uppercase;">MULAI PENGUSULAN DI SDP (H-2 BULAN SEBELUM 2/3):</span><br><span style="font-size: 10pt; font-weight: bold; color: #b45309;">{$tglUsul}</span><br><span style="font-size: 7.5pt; color: #78350f;">{$statusPesan}</span></td>
        <td width="27%" align="center" valign="middle" style="background-color: #fef3c7; border-left: 1px dashed #d97706; padding: 2.5px 6px;"><span style="font-size: 7.5pt; color: #78350f; font-weight: bold;">TANGGAL 2/3 MASA PIDANA</span><br><span style="font-size: 10pt; font-weight: bold; color: #047857;">{$tgl23Def}</span><br><span style="font-size: 7pt; color: #555;">(2/3 Pokok - Full Remisi)</span></td>
    </tr>
</table>
BANNER_NORMAL_PDF;

            $sectionDPdfHtml = <<<SEC_D_NORMAL_PDF
<!-- D. USUL SDP (H-2 BULAN) -->
<table width="100%" cellpadding="0" cellspacing="0" style="border: 0.5px solid #fed7aa;">
    <tr style="background-color: #fff7ed;">
        <td style="font-weight: bold; font-size: 8.5pt; color: #c2410c; padding: 2.5px 6px;">D. USUL SDP (H-2 BULAN)</td>
    </tr>
    <tr>
        <td style="padding: 2.5px 6px;"><table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size: 8.5pt; line-height: 1.12;">
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">&bull; Pengusulan di SDP</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Tgl. 2/3 Masa Pidana - 60 Hari (H-2 Bulan)</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tgl23DefNum} - 60 Hari</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglUsulNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #c2410c; font-size: 9.5pt;">{$tglUsulIndo}</b></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
SEC_D_NORMAL_PDF;
        }

        return <<<HTML
<style>
    body { font-family: helvetica, sans-serif; font-size: 8pt; color: #0f172a; line-height: 1.15; }
    h1 { font-size: 11.5pt; font-weight: bold; text-align: center; margin: 0; padding: 0 0 2px 0; text-transform: uppercase; letter-spacing: 0.3px; }
    .table-identitas td { padding: 1.5px 6px; font-size: 8.5pt; }
    .table-ledger th { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; border: 0.5px solid #475569; padding: 2.5px 2px; font-size: 7.2pt; }
</style>

<h1 style="color: #0f172a;">PERKIRAAN PEROLEHAN REMISI</h1>
<table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size: 2pt; line-height: 0.3;"><tr><td>&nbsp;</td></tr></table>

<!-- I. IDENTITAS WARGA BINAAN -->
<table width="100%" cellpadding="2" cellspacing="0" class="table-identitas" style="border: 0.5px solid #cbd5e1; border-collapse: collapse; margin-bottom: 4px;">
    <tr style="background-color: #f1f5f9;">
        <td colspan="6" style="font-weight: bold; font-size: 8.8pt; color: #0f172a; border-bottom: 0.5px solid #cbd5e1; padding: 2.5px 6px;">I. IDENTITAS WARGA BINAAN</td>
    </tr>
    <tr>
        <td width="19%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 2px 6px; font-size: 8.5pt;">Nama</td>
        <td width="2%" align="center" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="29%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$namaWbp}</td>
        <td width="21%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 2px 6px; font-size: 8.5pt;">Tgl. Mulai Ditahan</td>
        <td width="2%" align="center" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="27%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$tglAwal}</td>
    </tr>
    <tr>
        <td width="19%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 2px 6px; font-size: 8.5pt;">Nomor Register</td>
        <td width="2%" align="center" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="29%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$noRegister}</td>
        <td width="21%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 2px 6px; font-size: 8.5pt;">Tgl. Putusan</td>
        <td width="2%" align="center" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="27%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$tglPutusan}</td>
    </tr>
    <tr>
        <td width="19%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 2px 6px; font-size: 8.5pt;">Pidana / Pasal</td>
        <td width="2%" align="center" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="29%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$perkaraPasal}</td>
        <td width="21%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 2px 6px; font-size: 8.5pt;">Pidana Pokok</td>
        <td width="2%" align="center" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="27%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$lamaVonis}</td>
    </tr>
    <tr>
        <td width="19%" style="{$borderBottomPotah} color: #475569; padding: 2px 6px; font-size: 8.5pt;">Potongan Tahanan (Potah)</td>
        <td width="2%" align="center" style="{$borderBottomPotah} font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="29%" style="{$borderBottomPotah} font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$masaTahananTeks}</td>
        <td width="21%" style="{$borderBottomPotah} color: #475569; padding: 2px 6px; font-size: 8.5pt;">Ekspirasi Pokok</td>
        <td width="2%" align="center" style="{$borderBottomPotah} font-weight: bold; color: #0f172a; padding: 2px 0; font-size: 8.8pt;">:</td>
        <td width="27%" style="{$borderBottomPotah} font-weight: bold; color: #0f172a; padding: 2px 6px; font-size: 8.8pt;">{$tglEkspirasiMurni}</td>
    </tr>
    {$rowSubsiderPdf}
</table>

<!-- II. PERHITUNGAN PERKIRAAN INTEGRASI -->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="font-weight: bold; font-size: 8.8pt; color: #0f172a; padding-bottom: 2px;">II. PERHITUNGAN PERKIRAAN INTEGRASI</td>
    </tr>
</table>
<table width="100%" cellpadding="2" cellspacing="0" style="border: 0.5px solid #94a3b8; border-collapse: collapse; margin-bottom: 4px;">
    <thead>
        <tr style="background-color: #0f172a; color: #ffffff; text-align: center; font-weight: bold; font-size: 8pt;">
            <th width="20%" style="border: 0.5px solid #475569; padding: 2.5px 2px;">1/3</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 2.5px 2px;">1/2</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 2.5px 2px;">2/3</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 2.5px 2px;">Ekspirasi Akhir</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 2.5px 2px;">Jumlah Remisi</th>
        </tr>
    </thead>
    <tbody>
        <tr style="text-align: center; font-size: 8.8pt; background-color: #f8fafc;">
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 2.5px 2px;"><b style="color: #0284c7; font-size: 8.8pt;">{$tgl13Def}</b></td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 2.5px 2px;"><b style="color: #d97706; font-size: 8.8pt;">{$tgl12Def}</b></td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 2.5px 2px; background-color: #ecfdf5;">{$cell23Pdf}</td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 2.5px 2px; background-color: #fef2f2;"><b style="color: #b91c1c; font-size: 9pt;">{$tglEkspirasiDef}</b></td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 2.5px 2px; background-color: #fffbeb;"><b style="color: #b45309; font-size: 9pt;">{$totalRemisiHari} Hari</b><br><span style="font-size: 7.2pt; color: #92400e;">({$totalRemisiTeks})</span></td>
        </tr>
    </tbody>
</table>

<!-- HIGHLIGHT ALERT: MULAI PENGUSULAN DI SDP -->
{$bannerPengusulanPdfHtml}

<!-- III. PERKIRAAN PEROLEHAN REMISI -->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="font-weight: bold; font-size: 8.8pt; color: #0f172a; padding-bottom: 2px;">III. PERKIRAAN PEROLEHAN REMISI</td>
    </tr>
</table>
<table width="100%" border="1" cellpadding="1.2" cellspacing="0" class="table-ledger" style="border-collapse: collapse; border-color: #cbd5e1; margin-bottom: 4px;">
    <thead>
        <tr style="background-color: #1e293b; color: #ffffff; text-align: center; font-weight: bold; font-size: 7.2pt;">
            <th width="4%" align="center"><span style="font-size: 2pt;">&nbsp;<br></span>No</th>
            <th width="20%" align="center">Jenis Remisi /<br>Tahun</th>
            <th width="12%" align="center">Tgl.<br>Pelaksanaan</th>
            <th width="13%" align="center">Lama Menjalani<br>Pidana</th>
            <th width="13%" align="center"><span style="font-size: 2pt;">&nbsp;<br></span>Besaran Remisi</th>
            <th width="20%" align="center">Ekspirasi &amp; Tahapan<br>Setelah Remisi</th>
            <th width="18%" align="center"><span style="font-size: 2pt;">&nbsp;<br></span>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        {$ledgerRowsHtml}
    </tbody>
    <tfoot>
        <tr nobr="true" style="background-color: #f1f5f9; font-weight: bold; border-top: 1.5px solid #475569;">
            <td colspan="4" width="49%" align="right" style="border: 0.5px solid #cbd5e1; padding: 2.5px 6px; font-size: 7.5pt; color: #0f172a;">TOTAL AKUMULASI REMISI YANG DIPEROLEH:</td>
            <td width="13%" align="center" style="border: 0.5px solid #cbd5e1; padding: 2.5px 2px; font-size: 7.8pt; color: #1e40af;"><b>{$totalRemisiTeks}</b></td>
            <td colspan="2" width="38%" align="center" style="border: 0.5px solid #cbd5e1; padding: 2.5px 4px; font-size: 7pt; color: #64748b;"><i>(Total Pengurangan Pidana)</i></td>
        </tr>
        <tr nobr="true" style="background-color: #fef2f2; font-weight: bold;">
            <td colspan="5" width="62%" align="right" style="border: 0.5px solid #cbd5e1; padding: 2.5px 6px; font-size: 7.5pt; color: #991b1b;">TANGGAL EKSPIRASI AKHIR (SETELAH REMISI):</td>
            <td colspan="2" width="38%" align="center" style="border: 0.5px solid #cbd5e1; padding: 2.5px 4px; font-size: 8.5pt; color: #b91c1c;"><b>{$tglEkspirasiDef}</b></td>
        </tr>
    </tfoot>
</table>

<!-- ============================================================== -->
<!-- HALAMAN 2: PERHITUNGAN MANUAL LENGKAP                        -->
<!-- ============================================================== -->
<div style="page-break-before: always;"></div>

<h1 style="color: #0f172a; font-size: 11.5pt; text-align: center; font-weight: bold; margin: 0; padding: 2px 0 3px 0; letter-spacing: 0.3px;">{$judulProgram}</h1>
<table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size: 3pt; line-height: 0.4;"><tr><td>&nbsp;</td></tr></table>

<!-- IDENTITAS SINGKAT LAMPIRAN -->
<table width="100%" cellpadding="2.5" cellspacing="0" style="border: 0.5px solid #cbd5e1; background-color: #f8fafc; font-size: 8pt;">
    <tr>
        <td width="18%" style="color: #475569; padding: 2.5px 6px;">Nama</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2.5px 0;">:</td>
        <td width="30%" style="font-weight: bold; color: #0f172a; padding: 2.5px 6px;">{$namaWbp}</td>
        <td width="21%" style="color: #475569; padding: 2.5px 6px;">Tgl. Mulai Ditahan</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2.5px 0;">:</td>
        <td width="27%" style="font-weight: bold; color: #0f172a; padding: 2.5px 6px;">{$tglAwal}</td>
    </tr>
    <tr>
        <td width="18%" style="color: #475569; padding: 2.5px 6px;">Nomor Register</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2.5px 0;">:</td>
        <td width="30%" style="font-weight: bold; color: #0f172a; padding: 2.5px 6px;">{$noRegister}</td>
        <td width="21%" style="color: #475569; padding: 2.5px 6px;">Tgl. Putusan</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2.5px 0;">:</td>
        <td width="27%" style="font-weight: bold; color: #0f172a; padding: 2.5px 6px;">{$tglPutusan}</td>
    </tr>
    <tr>
        <td width="18%" style="color: #475569; padding: 2.5px 6px;">Perkara / Pasal</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2.5px 0;">:</td>
        <td width="30%" style="font-weight: bold; color: #0f172a; padding: 2.5px 6px;">{$perkaraPasal}</td>
        <td width="21%" style="color: #475569; padding: 2.5px 6px;">Pidana Pokok</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2.5px 0;">:</td>
        <td width="27%" style="font-weight: bold; color: #0f172a; padding: 2.5px 6px;">{$lamaVonis}</td>
    </tr>
    <tr>
        <td width="18%" style="color: #475569; padding: 2.5px 6px;">Pidana Subsider</td>
        <td width="2%" align="center" style="font-weight: bold; color: #0f172a; padding: 2.5px 0;">:</td>
        <td colspan="4" width="80%" style="font-weight: bold; color: #0f172a; padding: 2.5px 6px;">{$subsiderIdentitasTeks}</td>
    </tr>
</table>

{$potahPdfHtml}

{$cardSpacerPdf}
<!-- B. EKSPIRASI SEBELUM REMISI -->
<table width="100%" cellpadding="0" cellspacing="0" style="border: 0.5px solid #bfdbfe;">
    <tr style="background-color: #eff6ff;">
        <td style="font-weight: bold; font-size: 8.5pt; color: #1e40af; padding: 2.5px 6px;">B. EKSPIRASI SEBELUM REMISI</td>
    </tr>
    <tr>
        <td style="padding: 2.5px 6px;"><table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size: 8.5pt; line-height: 1.12;">
                {$barisEkspirasiAwalPdf}
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiSebelumRemisiNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #b91c1c; font-size: 9.5pt;">{$ekspirasiSebelumRemisiIndo}</b></td>
                </tr>
                {$subsiderPdfSectionB}
            </table></td>
    </tr>
</table>

{$cardSpacerPdf}
<!-- C. PENTAHAPAN MASA PIDANA (SETELAH REMISI) -->
<table width="100%" cellpadding="0" cellspacing="0" style="border: 0.5px solid #fecaca;">
    <tr style="background-color: #fef2f2;">
        <td style="font-weight: bold; font-size: 8.5pt; color: #991b1b; padding: 2.5px 6px;">C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)</td>
    </tr>
    <tr>
        <td style="padding: 2.5px 6px;"><table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size: 8.5pt; line-height: 1.12;">
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">&bull; Jumlah Remisi yang diperoleh</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #15803d; font-size: 9.5pt;">{$totalRemisiTeks}</b></td>
                </tr>
                <tr style="font-size: 2pt; line-height: 0.3;"><td colspan="3">&nbsp;</td></tr>
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">&bull; 1/2 Masa Pidana</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$labelPentahapanPdf} + ((1/2 x Masa Pidana) - Remisi)</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + ((1/2 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + ((1/2 x {$pidanaBulanTeks}) - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + ({$fraksi12BulanTeks} - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + {$selisih12BulanTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tglPentahapanPdfNum} + {$selisih12TahunTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$tgl12DefNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #0284c7; font-size: 9.5pt;">{$tgl12DefIndo}</b></td>
                </tr>
                <tr style="font-size: 2pt; line-height: 0.3;"><td colspan="3">&nbsp;</td></tr>
{$baris23PdfHtml}
                <tr style="font-size: 2pt; line-height: 0.3;"><td colspan="3">&nbsp;</td></tr>
                <tr>
                    <td width="28%" style="color: #0f172a; font-weight: bold;">&bull; Ekspirasi Akhir</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">Ekspirasi Sebelum Remisi - Remisi</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiSebelumRemisiNum} - {$totalRemisiTeks}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%">{$ekspirasiDefNum}</td>
                </tr>
                <tr>
                    <td width="28%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="68%"><b style="color: #b91c1c; font-size: 9.5pt;">{$ekspirasiDefIndo}</b></td>
                </tr>
                {$subsiderPdfEkspirasiC}
            </table>
        </td>
    </tr>
</table>
{$cardSpacerPdf}
{$sectionDPdfHtml}
HTML;
    }
}
