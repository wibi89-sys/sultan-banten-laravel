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
     * Hitung Proyeksi Remisi dan Hak Integrasi (PB, CB, Asimilasi)
     * Berdasarkan UU No. 22 Tahun 2022, Keppres No. 174 Tahun 1999 (RU, RK, Remisi Tambahan),
     * Keppres No. 120/1955 s.d. Keppres 120/2015 (Remisi Dasawarsa tiap 10 tahun RI),
     * Serta Metode Standar SDP Ditjenpas (2/3 Murni dikurangi Full Remisi).
     *
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
        string $subsiderStatus = 'bb'
    ): array {
        $tglAwal = Carbon::parse($tglAwalStr)->startOfDay();

        if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
            throw new InvalidArgumentException('Lama pidana vonis harus lebih dari 0.');
        }

        $tglPutusan = null;
        $masaTahananHari = 0;
        $masaTahananTeks = '-';

        if (! empty($tglPutusanStr)) {
            $parsedPutusan = Carbon::parse($tglPutusanStr)->startOfDay();
            if ($parsedPutusan->lt($tglAwal)) {
                throw new InvalidArgumentException('Tanggal putusan pengadilan tidak boleh mendahului tanggal mulai ditahan.');
            }
            $tglPutusan = $parsedPutusan;
            $masaTahananHari = (int) $tglAwal->diffInDays($tglPutusan);
            $masaTahananTeks = self::formatHariKeTeks($masaTahananHari);
            if ($masaTahananHari > 0) {
                $masaTahananTeks .= " ({$masaTahananHari} Hari)";
            }
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

        $validTambahan = ['none', 'pemuka', 'jasa_negara'];
        if (! in_array($kategoriTambahan, $validTambahan, true)) {
            $kategoriTambahan = 'none';
        }

        if ($remisiAwalHari < 0) {
            $remisiAwalHari = 0;
        }

        // 1. Ekspirasi Murni (Sebelum Remisi)
        $tglEkspirasiMurni = self::tambahDurasiVonis($tglAwal, $tahun, $bulan, $hari);

        // Pentahapan Masa Pidana Murni: Proporsional dari pidana vonis (Tahun, Bulan, Hari)
        // Dihitung dari tanggal awal penahanan dengan kaidah hitung vonis yang sama
        [$y13Murni, $m13Murni, $d13Murni] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 3);
        [$y12Murni, $m12Murni, $d12Murni] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 1, 2);
        [$y23Murni, $m23Murni, $d23Murni] = self::hitungDurasiFraksi($tahun, $bulan, $hari, 2, 3);

        $tgl13Murni = self::tambahDurasiVonis($tglAwal, $y13Murni, $m13Murni, $d13Murni);
        $tgl12Murni = self::tambahDurasiVonis($tglAwal, $y12Murni, $m12Murni, $d12Murni);
        $tgl23Murni = self::tambahDurasiVonis($tglAwal, $y23Murni, $m23Murni, $d23Murni);

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
        $startYear = $tglAwal->year;
        $endYear = $tglEkspirasiMurni->year + 1;
        $rawEvents = $this->generateRemisiEvents($agama, $startYear, $endYear);

        // Filter event yang jatuh setelah tanggal awal ditahan dan urutkan tanggalnya
        $eligibleEvents = [];
        foreach ($rawEvents as $ev) {
            $evDate = Carbon::parse($ev['tanggal'])->startOfDay();
            if ($evDate->gte($tglAwal)) {
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
        // Batas Minimal Berhak Integrasi UU No. 22 Tahun 2022
        $tglMinIntegrasi = $tglAwal->copy()->addDays($minHariIntegrasi);
        $tglMin12 = $tglAwal->copy()->addDays(180);

        // Inisialisasi akumulasi remisi & fungsi penghitung pentahapan dinamis
        $totalHariRemisi = $remisiAwalHari;
        $cumBulanRemisi = (int) floor($remisiAwalHari / 30);
        $cumHariRemisi = $remisiAwalHari % 30;

        $calcCurrentMilestones = function () use (
            $tglAwal,
            $tglEkspirasiMurni,
            $tgl13Murni,
            $tgl12Murni,
            $tgl23Murni,
            $tglMinIntegrasi,
            $tglMin12,
            &$totalHariRemisi
        ) {
            $curEkspirasi = $tglEkspirasiMurni->copy()->subDays($totalHariRemisi);

            $cur13 = $tgl13Murni->copy()->subDays($totalHariRemisi);
            if ($cur13->lt($tglAwal)) {
                $cur13 = $tglAwal->copy();
            }

            $cur12 = $tgl12Murni->copy()->subDays($totalHariRemisi);
            if ($cur12->lt($tglMin12)) {
                $cur12 = $tglMin12->copy();
            }

            $cur23 = $tgl23Murni->copy()->subDays($totalHariRemisi);
            if ($cur23->lt($tglMinIntegrasi)) {
                $cur23 = $tglMinIntegrasi->copy();
            }

            return [$curEkspirasi, $cur13, $cur12, $cur23];
        };

        [$currentEkspirasi, $current13Date, $current12Date, $current23Date] = $calcCurrentMilestones();

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

            $hariDijalani = (int) $tglAwal->diffInDays($evDate);
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

            // 4a. REMISI TAMBAHAN (Pasal 4 Keppres 174/1999) - Melekat pada Remisi Umum
            if ($item['jenis'] === 'RU' && $potonganHari > 0 && $kategoriTambahan !== 'none' && $keRu >= $tahunMulaiTambahan) {
                $potonganTambahan = 0;
                $rtBln = 0;
                $rtHr = 0;
                $keteranganTambahan = '';
                $namaEventTambahan = '';

                if ($kategoriTambahan === 'pemuka') {
                    // 1/3 dari Remisi Umum
                    $rtBln = (int) floor($evBln / 3);
                    $rtHr = (int) floor((($evBln % 3) * 30) / 3);
                    $potonganTambahan = ($rtBln * 30) + $rtHr;
                    $namaEventTambahan = "Remisi Tambahan Pemuka ({$item['tahun']})";
                    $keteranganTambahan = "Bonus 1/3 dari Remisi Umum ({$besaranTeks}) bagi narapidana pemuka pembinaan di Lapas (Pasal 4 Keppres No. 174/1999).";
                } elseif ($kategoriTambahan === 'jasa_negara') {
                    // 1/2 dari Remisi Umum
                    $rtBln = (int) floor($evBln / 2);
                    $rtHr = ($evBln % 2 === 1) ? 15 : 0;
                    $potonganTambahan = ($rtBln * 30) + $rtHr;
                    $namaEventTambahan = "Remisi Tambahan Jasa Negara/Kemanusiaan ({$item['tahun']})";
                    $keteranganTambahan = "Bonus 1/2 dari Remisi Umum ({$besaranTeks}) atas jasa bagi negara atau perbuatan kemanusiaan/donor darah (Pasal 4 Keppres No. 174/1999).";
                }

                if ($potonganTambahan > 0) {
                    $sisaHariTambahan = (int) $evDate->diffInDays($currentEkspirasi);
                    if ($potonganTambahan > $sisaHariTambahan) {
                        $potonganTambahan = max(0, $sisaHariTambahan);
                        $rtBln = (int) floor($potonganTambahan / 30);
                        $rtHr = $potonganTambahan % 30;
                    }

                    $cumBulanRemisi += $rtBln;
                    $cumHariRemisi += $rtHr;
                    if ($cumHariRemisi >= 30) {
                        $cumBulanRemisi += (int) floor($cumHariRemisi / 30);
                        $cumHariRemisi = $cumHariRemisi % 30;
                    }
                    $totalHariRemisi += $potonganTambahan;
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
        }

        // 5. Penetapan Pentahapan Definitif Akhir Hasil Simulasi
        $tgl13Definitif = $current13Date->copy();
        $tgl12Definitif = $current12Date->copy();
        $tgl23Definitif = $current23Date->copy();
        $tglEkspirasiDefinitif = $currentEkspirasi->copy();

        // 6. Tanggal Mulai Pengusulan PB/CB (H-2 Bulan / 60 Hari sebelum 2/3 tercapai)
        $tglMulaiPengusulan = $tgl23Definitif->copy()->subDays(60);

        // Status Pengusulan terhadap hari ini
        $today = Carbon::today();
        if ($today->lt($tglMulaiPengusulan)) {
            $sisaHariMenujuUsul = (int) $today->diffInDays($tglMulaiPengusulan);
            $statusPengusulan = [
                'kode' => 'menunggu',
                'label' => "Belum Waktunya ({$sisaHariMenujuUsul} hari lagi)",
                'badge_class' => 'bg-secondary',
                'sisa_hari' => $sisaHariMenujuUsul,
                'pesan' => "Pengusulan di SDP dapat dimulai pada {$this->formatTanggalIndonesia($tglMulaiPengusulan)} (tersisa {$sisaHariMenujuUsul} hari lagi).",
            ];
        } elseif ($today->lte($tgl23Definitif)) {
            $sisaHari23 = (int) $today->diffInDays($tgl23Definitif);
            $statusPengusulan = [
                'kode' => 'waktunya_usul',
                'label' => 'Waktunya Pengusulan!',
                'badge_class' => 'bg-warning text-dark',
                'sisa_hari' => $sisaHari23,
                'pesan' => "Waktunya Pengusulan! Pengusulan di SDP dapat diproses (H-2 bulan sebelum 2/3). Tanggal 2/3 masa pidana tercapai dalam {$sisaHari23} hari lagi ({$this->formatTanggalIndonesia($tgl23Definitif)}).",
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

        $labelKategoriTambahan = match ($kategoriTambahan) {
            'pemuka' => 'Pemuka Pembinaan Lapas (+1/3 RU)',
            'jasa_negara' => 'Jasa Negara / Kemanusiaan (+1/2 RU)',
            default => 'Reguler (Tanpa Remisi Tambahan)',
        };

        if ($kategoriTambahan !== 'none' && $tahunMulaiTambahan > 1) {
            $labelKategoriTambahan .= " (Mulai Tahun ke-{$tahunMulaiTambahan})";
        }

        $sisaPidanaPascaPutusanTeks = '-';
        $sisaHariPascaPutusan = null;
        if ($tglPutusan) {
            $sisaHariPascaPutusan = max(0, $totalHariVonis - $masaTahananHari);
            $sisaPidanaPascaPutusanTeks = self::formatHariKeTeks($sisaHariPascaPutusan);
            if ($sisaHariPascaPutusan > 0) {
                $sisaPidanaPascaPutusanTeks .= " ({$sisaHariPascaPutusan} Hari)";
            }
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

        $result = [
            'mode' => 'simulasi_remisi',
            'target_basis' => $targetBasis,
            'target_basis_label' => $targetBasis === '2_3' ? 'Standar 2/3 Masa Pidana' : 'Sampai Ekspirasi Sebelum Remisi',
            'nama_wbp' => $namaWbp,
            'no_register' => $noRegister,
            'perkara_pasal' => $perkaraPasal,
            'tgl_awal' => $tglAwal->toDateString(),
            'tgl_putusan' => $tglPutusan ? $tglPutusan->toDateString() : null,
            'masa_tahanan_hari' => $masaTahananHari,
            'masa_tahanan_teks' => $masaTahananTeks,
            'sisa_hari_pasca_putusan' => $sisaHariPascaPutusan,
            'sisa_pidana_pasca_putusan_teks' => $sisaPidanaPascaPutusanTeks,
            'lama_pidana_teks' => implode(', ', $formatVonis) ?: '0 Hari',
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
                'ekspirasi_definitif' => $tglEkspirasiDefinitif->toDateString(),
                'ekspirasi_subsider_sebelum_remisi' => $tglEkspirasiSubsiderSebelumRemisi?->toDateString(),
                'ekspirasi_subsider_murni' => $tglEkspirasiSubsiderSebelumRemisi?->toDateString(), // alias
                'ekspirasi_subsider_definitif' => $tglEkspirasiSubsiderDefinitif?->toDateString(),
            ],
            'formatted' => [
                'tgl_awal' => self::formatTanggalIndonesia($tglAwal),
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
                'ekspirasi_definitif' => self::formatTanggalIndonesia($tglEkspirasiDefinitif),
                'ekspirasi_subsider_sebelum_remisi' => $tglEkspirasiSubsiderSebelumRemisi ? self::formatTanggalIndonesia($tglEkspirasiSubsiderSebelumRemisi) : '-',
                'ekspirasi_subsider_murni' => $tglEkspirasiSubsiderSebelumRemisi ? self::formatTanggalIndonesia($tglEkspirasiSubsiderSebelumRemisi) : '-', // alias
                'ekspirasi_subsider_definitif' => $tglEkspirasiSubsiderDefinitif ? self::formatTanggalIndonesia($tglEkspirasiSubsiderDefinitif) : '-',
                'subsider_teks' => $subsiderTeks,
                'subsider_identitas_teks' => $subsiderIdentitasTeks,
            ],
            'numeric' => [
                'tgl_awal' => self::formatTanggalNumeric($tglAwal),
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
            ],
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

        $teks = "{$judulProgram}\n";
        $teks .= "Sultan Banten - Kantor Wilayah Kemenimipas Banten\n";
        $teks .= "========================================================\n\n";

        $teks .= "IDENTITAS WARGA BINAAN PEMASYARAKATAN\n";
        $teks .= "--------------------------------------------------------\n";
        $teks .= "Nama WBP            : {$namaWbp}\n";
        $teks .= "Nomor Register      : {$noRegister}\n";
        $teks .= "Perkara / Pasal     : {$perkaraPasal}\n";
        $teks .= "Tgl. Mulai Ditahan  : {$tglAwalIndo}\n";
        $teks .= "Tgl. Putusan        : {$tglPutusanIndo}\n";
        $teks .= "Pidana Pokok        : {$lamaVonis}\n";
        $teks .= "Pidana Subsider     : {$subsiderIdentitas}\n\n";

        if (! empty($data['tgl_putusan'])) {
            $teks .= "A. POTONGAN TAHANAN (POTAH)\n";
            $teks .= "   Potah = Tgl. Mulai Ditahan - Tgl. Putusan\n";
            $teks .= "         = {$tglAwalNum} - {$tglPutusanNum}\n";
            $teks .= "         = {$masaTahananTeks}\n\n";
        }

        $teks .= "B. EKSPIRASI SEBELUM REMISI\n";
        if (! empty($data['tgl_putusan'])) {
            $teks .= "   Ekspirasi Awal  = Tgl. Putusan + Lama Pidana Pokok\n";
            $teks .= "                   = {$tglPutusanNum} + {$lamaVonis}\n";
            $teks .= "                   = {$ekspirasiAwalNum}\n";
            $teks .= "                   = {$ekspirasiAwalIndo}\n\n";

            $teks .= "   Ekspirasi Akhir = Ekspirasi Awal - Potah\n";
            $teks .= "                   = {$ekspirasiAwalNum} - {$masaTahananTeks}\n";
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

        $teks .= "C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)\n";
        $teks .= "   • Jumlah Remisi yang diperoleh = {$totalRemisiTeks} ({$totalRemisiHari} Hari)\n\n";

        $teks .= "   • 1/2 Masa Pidana = Tgl. Mulai Ditahan + ((1/2 x Masa Pidana) - Remisi)\n";
        $teks .= "                     = {$tglAwalNum} + ((1/2 x {$lamaVonis}) - {$totalRemisiTeks})\n";
        $teks .= "                     = {$tgl12DefNum}\n";
        $teks .= "                     = {$tgl12DefIndo}\n\n";

        $teks .= "   • 2/3 Masa Pidana = Tgl. Mulai Ditahan + ((2/3 x Masa Pidana) - Remisi)\n";
        $teks .= "                     = {$tglAwalNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})\n";
        $teks .= "                     = {$tgl23DefNum}\n";
        $teks .= "                     = {$tgl23DefIndo}\n";

        if ($hasSubsiderBb) {
            $teks .= "\n";
            $teks .= "   • 2/3 Masa Pidana (+Subsider BB) = 2/3 Masa Pidana + Pidana Subsider\n";
            $teks .= "                                    = {$tgl23DefNum} + {$subsiderTeks}\n";
            $teks .= "                                    = {$tgl23SubDefNum}\n";
            $teks .= "                                    = {$tgl23SubDefIndo}\n";
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

        $teks .= "   • Waktu Mulai Pengusulan di SDP (H-2 Bulan sebelum 2/3):\n";
        $teks .= "     Tgl. Usul SDP = Tgl. 2/3 Masa Pidana - 60 Hari\n";
        $teks .= "                   = {$tgl23DefNum} - 60 Hari\n";
        $teks .= "                   = {$tglUsulNum}\n";
        $teks .= "                   = {$tglUsulIndo}\n";
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

        $html = '<div class="hitungan-manual-container d-flex flex-column gap-3.5">';

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
        <div class="card border-0 shadow-sm" style="background-color: #f8fafc; border-radius: 10px;">
            <div class="card-body p-3.5">
                <div class="row g-2.5" style="font-size: 13px;">
                    <div class="col-sm-6"><b>Nama WBP:</b> {$namaWbp}</div>
                    <div class="col-sm-6"><b>Tgl. Mulai Ditahan:</b> {$tglAwalIndo}</div>
                    <div class="col-sm-6"><b>No. Register:</b> {$noRegister}</div>
                    <div class="col-sm-6"><b>Tgl. Putusan:</b> {$tglPutusanIndo}</div>
                    <div class="col-sm-6"><b>Perkara / Pasal:</b> {$perkaraPasal}</div>
                    <div class="col-sm-6"><b>Pidana Pokok:</b> {$lamaVonis}</div>
                    <div class="col-12 border-top pt-2 mt-1">
                        <b>Pidana Denda / Subsider:</b> <span class="fw-semibold text-primary">{$subsiderIdentitas}</span>
                    </div>
                </div>
            </div>
        </div>
IDENTITAS;

        // Bagian A (Jika Tgl Putusan diisi)
        if (! empty($data['tgl_putusan'])) {
            $html .= <<<CARD_A
            <div class="card border" style="border-radius: 10px; border-color: #bbf7d0;">
                <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #f0fdf4;">
                    <strong style="color: #166534; font-size: 14px;"><i class="fas fa-gavel me-1.5"></i> A. POTONGAN TAHANAN (POTAH)</strong>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10.5px;">KUHAP Ps. 22(4)</span>
                </div>
                <div class="card-body p-3.5" style="font-size: 13.5px;">
                    <table class="table-borderless w-100 font-monospace mb-0" style="font-size: 13px; line-height: 1.8;">
                        <tr>
                            <td style="width: 175px; font-weight: 600; color: #0f172a;">Potah</td>
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

        // Bagian B: Ekspirasi Sebelum Remisi
        $subsiderHtmlB = '';
        if ($hasSubsiderBb) {
            $subsiderHtmlB = <<<SUB_B
            <tr style="height: 8px;"><td colspan="3"></td></tr>
            <tr>
                <td style="width: 175px; font-weight: bold; color: #991b1b;">Ekspirasi (+Subsider BB)</td>
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

        $barisEkspirasiAwal = '';
        if (! empty($data['tgl_putusan'])) {
            $barisEkspirasiAwal = <<<EKS_AWAL
            <tr>
                <td style="width: 175px; font-weight: 600; color: #0f172a;">Ekspirasi Awal</td>
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
                <td style="width: 175px; font-weight: 600; color: #0f172a;">Ekspirasi Akhir</td>
                <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                <td>Ekspirasi Awal - Potah</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center; font-weight: bold;">=</td>
                <td>{$ekspirasiAwalNum} - {$masaTahananTeks}</td>
            </tr>
EKS_AWAL;
        } else {
            $barisEkspirasiAwal = <<<EKS_AWAL
            <tr>
                <td style="width: 175px; font-weight: 600; color: #0f172a;">Ekspirasi Akhir</td>
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
        <div class="card border" style="border-radius: 10px; border-color: #bfdbfe;">
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
                <td style="width: 175px; font-weight: bold; color: #14532d;">• 2/3 (+Subsider BB)</td>
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
                <td style="width: 175px; font-weight: bold; color: #991b1b;">• Ekspirasi (+Subsider BB)</td>
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

        $html .= <<<CARD_C
        <div class="card border" style="border-radius: 10px; border-color: #fecaca;">
            <div class="card-header py-2.5 px-3.5 border-bottom d-flex align-items-center justify-content-between" style="background-color: #fef2f2;">
                <strong style="color: #991b1b; font-size: 14px;"><i class="fas fa-layer-group me-1.5"></i> C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)</strong>
                <span class="badge bg-success text-white" style="font-size: 11.5px;">Remisi: {$totalRemisiTeks} ({$totalRemisiHari} Hr)</span>
            </div>
            <div class="card-body p-3.5" style="font-size: 13.5px;">
                <table class="table-borderless w-100 font-monospace mb-0" style="font-size: 13px; line-height: 1.8;">
                    <tr>
                        <td colspan="3" class="pb-2">
                            • <b>Jumlah Remisi yang diperoleh</b> = <b class="text-success" style="font-size: 14px;">{$totalRemisiTeks} ({$totalRemisiHari} Hari)</b>
                        </td>
                    </tr>
                    <tr style="height: 6px;"><td colspan="3"></td></tr>
                    <tr>
                        <td style="width: 175px; font-weight: 600; color: #0f172a;">• 1/2 Masa Pidana</td>
                        <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                        <td>Tgl. Mulai Ditahan + ((1/2 x Masa Pidana) - Remisi)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglAwalNum} + ((1/2 x {$lamaVonis}) - {$totalRemisiTeks})</td>
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
                    <tr>
                        <td style="width: 175px; font-weight: 600; color: #0f172a;">• 2/3 Masa Pidana</td>
                        <td style="width: 25px; text-align: center; font-weight: bold;">=</td>
                        <td>Tgl. Mulai Ditahan + ((2/3 x Masa Pidana) - Remisi)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold;">=</td>
                        <td>{$tglAwalNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})</td>
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
                    <tr style="height: 8px;"><td colspan="3"></td></tr>
                    <tr>
                        <td style="width: 175px; font-weight: 600; color: #0f172a;">• Ekspirasi Akhir</td>
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
                    <tr style="height: 10px;"><td colspan="3"></td></tr>
                    <tr>
                        <td style="width: 175px; font-weight: bold; color: #b45309;">• Pengusulan di SDP</td>
                        <td style="width: 25px; text-align: center; font-weight: bold; color: #b45309;">=</td>
                        <td style="color: #b45309;">Tgl. 2/3 Masa Pidana - 60 Hari (H-2 Bulan)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold; color: #b45309;">=</td>
                        <td>{$tgl23DefNum} - 60 Hari</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold; color: #b45309;">=</td>
                        <td>{$tglUsulNum}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; font-weight: bold; color: #b45309;">=</td>
                        <td><b style="color: #b45309; font-size: 15px;">{$tglUsulIndo}</b></td>
                    </tr>
                </table>
            </div>
        </div>
CARD_C;

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

        $instansi = null;
        try {
            $instansi = Instansi::getFirst();
        } catch (\Throwable) {
            $instansi = null;
        }

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
        $pdf->SetMargins(10, 8, 10);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        $html = self::renderPdfTemplateHtml($data, $instansi);
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    /**
     * Render Template HTML untuk Dokumen PDF
     *
     * @param  array<string, mixed>  $data
     */
    protected static function renderPdfTemplateHtml(array $data, ?Instansi $instansi): string
    {
        $logoHtml = '';
        $logoPath = null;
        if (! empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
            $logoPath = public_path($instansi->logo_kop);
        } elseif (! empty($instansi?->logo) && file_exists(public_path($instansi->logo))) {
            $logoPath = public_path($instansi->logo);
        }

        if ($logoPath) {
            $logoHtml = '<img src="'.$logoPath.'" width="65" height="65" />';
        }

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
        <td width="20%" style="color: #475569; padding: 4px 8px; font-size: 9pt;">Pidana Subsider</td>
        <td width="80%" colspan="3" style="font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$subsiderIdentitasTeks}</td>
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

        $potahPdfHtml = '';
        if (! empty($data['tgl_putusan'])) {
            $potahPdfHtml = <<<POTAH
<!-- A. POTONGAN TAHANAN (POTAH) -->
<table width="100%" cellpadding="6" cellspacing="0" style="border: 0.5px solid #bbf7d0; margin-bottom: 14px;">
    <tr style="background-color: #f0fdf4;">
        <td style="font-weight: bold; font-size: 10pt; color: #166534; padding: 7px 10px;">
            A. POTONGAN TAHANAN (POTAH)
        </td>
    </tr>
    <tr>
        <td style="padding: 10px 12px;">
            <table width="100%" cellpadding="2" cellspacing="0" border="0" style="font-size: 9.5pt; line-height: 1.5;">
                <tr>
                    <td width="24%" style="color: #0f172a; font-weight: bold;">Potah</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Tgl. Mulai Ditahan - Tgl. Putusan</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tglAwalNum} - {$tglPutusanNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #166534; font-size: 10.5pt;">{$masaTahananTeks}</b></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
POTAH;
        }

        $barisEkspirasiAwalPdf = '';
        if (! empty($data['tgl_putusan'])) {
            $barisEkspirasiAwalPdf = <<<EKS_AWAL_PDF
                <tr>
                    <td width="24%" style="color: #0f172a; font-weight: bold;">Ekspirasi Awal</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Tgl. Putusan + Lama Pidana Pokok</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tglPutusanNum} + {$lamaVonis}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiAwalNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #1e40af; font-size: 10.5pt;">{$ekspirasiAwalIndo}</b></td>
                </tr>
                <tr><td colspan="3" style="height: 6px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #0f172a; font-weight: bold;">Ekspirasi Akhir</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Ekspirasi Awal - Potah</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiAwalNum} - {$masaTahananTeks}</td>
                </tr>
EKS_AWAL_PDF;
        } else {
            $barisEkspirasiAwalPdf = <<<EKS_AWAL_PDF
                <tr>
                    <td width="24%" style="color: #0f172a; font-weight: bold;">Ekspirasi Akhir</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Tgl. Mulai Ditahan + Lama Pidana Pokok</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tglAwalNum} + {$lamaVonis}</td>
                </tr>
EKS_AWAL_PDF;
        }

        $subsiderPdfSectionB = '';
        if ($hasSubsiderBb) {
            $subsiderPdfSectionB = <<<SUB_B_PDF
                <tr><td colspan="3" style="height: 6px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #991b1b; font-weight: bold;">Ekspirasi (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Ekspirasi Akhir + Pidana Subsider</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiSebelumRemisiNum} + {$subsiderTeks}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiSubSebelumRemisiNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #991b1b; font-size: 10.5pt;">{$ekspirasiSubSebelumRemisiIndo}</b></td>
                </tr>
SUB_B_PDF;
        }

        $subsiderPdf23 = '';
        if ($hasSubsiderBb) {
            $subsiderPdf23 = <<<SUB_23_PDF
                <tr><td colspan="3" style="height: 6px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #14532d; font-weight: bold;">&bull; 2/3 (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">2/3 Masa Pidana + Pidana Subsider</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tgl23DefNum} + {$subsiderTeks}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tgl23SubDefNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #14532d; font-size: 10.5pt;">{$tgl23SubDefIndo}</b></td>
                </tr>
SUB_23_PDF;
        }

        $subsiderPdfEkspirasiC = '';
        if ($hasSubsiderBb) {
            $subsiderPdfEkspirasiC = <<<SUB_EKS_C_PDF
                <tr><td colspan="3" style="height: 6px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #991b1b; font-weight: bold;">&bull; Ekspirasi (+Subsider BB)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Ekspirasi Akhir + Pidana Subsider (Tanpa Remisi)</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiDefNum} + {$subsiderTeks}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiSubDefNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #991b1b; font-size: 10.5pt;">{$ekspirasiSubDefIndo}</b> <span style="font-size: 8pt; color: #555;">(Tanpa Remisi)</span></td>
                </tr>
SUB_EKS_C_PDF;
        }

        // Render Ledger Rows (Tabel Rincian Proyeksi Remisi 8 Kolom)
        $ledgerRowsHtml = '';
        $ledger = (array) ($data['proyeksi_ledger'] ?? []);
        if (empty($ledger)) {
            $ledgerRowsHtml = '<tr><td colspan="8" width="100%" align="center" style="color: #64748b; padding: 12px; border: 0.5px solid #cbd5e1;">Tidak ada jadwal event remisi sebelum tanggal ekspirasi.</td></tr>';
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
                <tr bgcolor="{$bgColor}">
                    <td width="4%" align="center" style="font-weight: bold; border: 0.5px solid #cbd5e1; padding: 5px 2px; color: #475569; font-size: 8pt;">{$no}</td>
                    <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 5px 4px; font-size: 8pt;">
                        <b>{$evNama}</b>{$tahapBadge}
                    </td>
                    <td width="11%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 2px; font-size: 8pt; color: #1e293b;">{$evTgl}</td>
                    <td width="12%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 2px; font-size: 8pt;">
                        <b>{$evDijalani} hr</b><br><span style="font-size: 6.8pt; color: #64748b;">({$evMasaTeks})</span>
                    </td>
                    <td width="11%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 2px;">{$besaranHtml}</td>
                    <td width="11%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 2px; font-weight: bold; color: #047857; font-size: 8.5pt;">
                        {$evBesaranHari} hr<br><span style="font-size: 6.8pt; color: #64748b; font-weight: normal;">(Akum: {$evAkumulasi})</span>
                    </td>
                    <td width="16%" align="center" style="border: 0.5px solid #cbd5e1; padding: 4px 2px;">
                        <b style="color: #b91c1c; font-size: 7.8pt;">{$evEkspirasiBaru}</b>
                        <div style="font-size: 6.2pt; color: #475569; line-height: 1.25; margin-top: 2px;">
                            1/3: {$ev13Baru} &bull; 1/2: {$ev12Baru}<br>
                            <span style="color: #047857; font-weight: bold;">2/3: {$ev23Baru}</span>
                        </div>
                    </td>
                    <td width="15%" style="border: 0.5px solid #cbd5e1; padding: 5px 4px; font-size: 7pt; color: #334155; line-height: 1.25;">{$evAlasan}</td>
                </tr>
ROW;
            }
        }

        return <<<HTML
<style>
    body { font-family: helvetica, sans-serif; font-size: 8.5pt; color: #0f172a; line-height: 1.35; }
    h1 { font-size: 13pt; font-weight: bold; text-align: center; margin: 0; padding: 0; text-transform: uppercase; letter-spacing: 0.3px; }
    .table-identitas td { padding: 4px 6px; font-size: 9pt; }
    .table-ledger th { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; border: 0.5px solid #475569; padding: 6px 2px; font-size: 8pt; }
</style>

<!-- KOP SURAT RESMI KANWIL KEMENIMIPAS BANTEN -->
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width="14%" align="center" valign="middle">
            {$logoHtml}
        </td>
        <td width="86%" align="center" valign="middle">
            <span style="font-size: 9.5pt; font-weight: bold; color: #1e293b;">KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</span><br>
            <span style="font-size: 9.5pt; font-weight: bold; color: #1e293b;">DIREKTORAT JENDERAL PEMASYARAKATAN</span><br>
            <span style="font-size: 12pt; font-weight: bold; color: #0f172a;">KANTOR WILAYAH BANTEN</span><br>
            <span style="font-size: 8.5pt; color: #334155;">Jl. KH. Sam'un No. 44, Serang, Banten | Pos-el: djpasbanten@gmail.com</span>
        </td>
    </tr>
</table>
<hr style="height: 1.5px; border: none; background-color: #0f172a; margin-top: 4px; margin-bottom: 6px;">
<br>

<h1 style="color: #0f172a;">PERKIRAAN PEROLEHAN REMISI</h1>
<br>

<!-- I. IDENTITAS WARGA BINAAN PEMASYARAKATAN & PARAMETER VONIS -->
<table width="100%" cellpadding="4" cellspacing="0" class="table-identitas" style="border: 0.5px solid #cbd5e1; border-collapse: collapse;">
    <tr style="background-color: #f1f5f9;">
        <td colspan="4" style="font-weight: bold; font-size: 9.5pt; color: #0f172a; border-bottom: 0.5px solid #cbd5e1; padding: 5px 8px;">
            I. IDENTITAS WARGA BINAAN PEMASYARAKATAN &amp; PARAMETER VONIS
        </td>
    </tr>
    <tr>
        <td width="20%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 4px 8px; font-size: 9pt;">Nama WBP</td>
        <td width="30%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$namaWbp}</td>
        <td width="22%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 4px 8px; font-size: 9pt;">Tgl. Mulai Ditahan</td>
        <td width="28%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$tglAwal}</td>
    </tr>
    <tr>
        <td width="20%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 4px 8px; font-size: 9pt;">Nomor Register</td>
        <td width="30%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$noRegister}</td>
        <td width="22%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 4px 8px; font-size: 9pt;">Tgl. Putusan</td>
        <td width="28%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$tglPutusan}</td>
    </tr>
    <tr>
        <td width="20%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 4px 8px; font-size: 9pt;">Pidana / Pasal</td>
        <td width="30%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$perkaraPasal}</td>
        <td width="22%" style="border-bottom: 0.5px solid #e2e8f0; color: #475569; padding: 4px 8px; font-size: 9pt;">Pidana Pokok</td>
        <td width="28%" style="border-bottom: 0.5px solid #e2e8f0; font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$lamaVonis}</td>
    </tr>
    <tr>
        <td width="20%" style="{$borderBottomPotah} color: #475569; padding: 4px 8px; font-size: 9pt;">Potongan Tahanan (Potah)</td>
        <td width="30%" style="{$borderBottomPotah} font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$masaTahananTeks}</td>
        <td width="22%" style="{$borderBottomPotah} color: #475569; padding: 4px 8px; font-size: 9pt;">Ekspirasi Pokok</td>
        <td width="28%" style="{$borderBottomPotah} font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 9.5pt;">: {$tglEkspirasiMurni}</td>
    </tr>
    {$rowSubsiderPdf}
</table>

<br><br>

<!-- II. PERHITUNGAN PERKIRAAN INTEGRASI -->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="font-weight: bold; font-size: 9.5pt; color: #0f172a; padding-bottom: 4px;">
            II. PERHITUNGAN PERKIRAAN INTEGRASI
        </td>
    </tr>
</table>
<table width="100%" cellpadding="4" cellspacing="0" style="border: 0.5px solid #94a3b8; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #0f172a; color: #ffffff; text-align: center; font-weight: bold; font-size: 8.5pt;">
            <th width="20%" style="border: 0.5px solid #475569; padding: 6px 2px;">1/3</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 6px 2px;">1/2</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 6px 2px;">2/3</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 6px 2px;">Ekspirasi Akhir</th>
            <th width="20%" style="border: 0.5px solid #475569; padding: 6px 2px;">Jumlah Remisi</th>
        </tr>
    </thead>
    <tbody>
        <tr style="text-align: center; font-size: 9.5pt; background-color: #f8fafc;">
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 7px 2px;">
                <b style="color: #0284c7; font-size: 9.5pt;">{$tgl13Def}</b>
            </td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 7px 2px;">
                <b style="color: #d97706; font-size: 9.5pt;">{$tgl12Def}</b>
            </td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 7px 2px; background-color: #ecfdf5;">
                <b style="color: #047857; font-size: 10pt;">{$tgl23Def}</b>
            </td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 7px 2px; background-color: #fef2f2;">
                <b style="color: #b91c1c; font-size: 10pt;">{$tglEkspirasiDef}</b>
            </td>
            <td width="20%" style="border: 0.5px solid #cbd5e1; padding: 7px 2px; background-color: #fffbeb;">
                <b style="color: #b45309; font-size: 10pt;">{$totalRemisiHari} Hari</b><br>
                <span style="font-size: 7.5pt; color: #92400e;">({$totalRemisiTeks})</span>
            </td>
        </tr>
    </tbody>
</table>

<br>

<!-- HIGHLIGHT ALERT: MULAI PENGUSULAN DI SDP (H-2 BULAN SEBELUM 2/3) -->
<table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #d97706; background-color: #fffbeb;">
    <tr>
        <td width="73%" valign="middle" style="padding: 6px 10px;">
            <span style="font-size: 8.5pt; font-weight: bold; color: #92400e; text-transform: uppercase;">
                MULAI PENGUSULAN DI SDP (H-2 BULAN SEBELUM 2/3):
            </span><br>
            <span style="font-size: 12pt; font-weight: bold; color: #b45309;">{$tglUsul}</span><br>
            <span style="font-size: 8pt; color: #78350f;">{$statusPesan}</span>
        </td>
        <td width="27%" align="center" valign="middle" style="background-color: #fef3c7; border-left: 1px dashed #d97706; padding: 6px 8px;">
            <span style="font-size: 8pt; color: #78350f; font-weight: bold;">TANGGAL 2/3 MASA PIDANA</span><br>
            <span style="font-size: 12pt; font-weight: bold; color: #047857;">{$tgl23Def}</span><br>
            <span style="font-size: 7.5pt; color: #555;">(2/3 Pokok - Full Remisi)</span>
        </td>
    </tr>
</table>

<br><br>

<!-- III. PERKIRAAN PEROLEHAN REMISI -->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="font-weight: bold; font-size: 9.5pt; color: #0f172a; padding-bottom: 4px;">
            III. PERKIRAAN PEROLEHAN REMISI
        </td>
    </tr>
</table>
<table width="100%" border="1" cellpadding="3" cellspacing="0" class="table-ledger" style="border-collapse: collapse; border-color: #cbd5e1; margin-bottom: 8px;">
    <thead>
        <tr style="background-color: #1e293b; color: #ffffff; text-align: center; font-weight: bold; font-size: 7.3pt;">
            <th width="4%" align="center"><span style="font-size: 3.5pt;">&nbsp;<br></span>No</th>
            <th width="20%" align="center">Jenis Remisi /<br>Tahun</th>
            <th width="11%" align="center">Tgl.<br>Pelaksanaan</th>
            <th width="12%" align="center">Lama Menjalani<br>Pidana</th>
            <th width="11%" align="center">Besaran Remisi<br>(Bl, Hr)</th>
            <th width="11%" align="center">Besaran Remisi<br>(Hr)</th>
            <th width="16%" align="center">Ekspirasi setelah<br>Remisi</th>
            <th width="15%" align="center"><span style="font-size: 3.5pt;">&nbsp;<br></span>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        {$ledgerRowsHtml}
    </tbody>
    <tfoot>
        <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 1.5px solid #475569;">
            <td colspan="4" width="47%" align="right" style="border: 0.5px solid #cbd5e1; padding: 5px 6px; font-size: 8pt; color: #0f172a;">
                TOTAL AKUMULASI REMISI YANG DIPEROLEH:
            </td>
            <td width="11%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 2px; font-size: 8.5pt; color: #1e40af;">
                <b>{$totalRemisiTeks}</b>
            </td>
            <td width="11%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 2px; font-size: 8.5pt; color: #047857;">
                <b>{$totalRemisiHari} hr</b>
            </td>
            <td colspan="2" width="31%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 4px; font-size: 7.5pt; color: #64748b;">
                <i>(Total Pengurangan Pidana)</i>
            </td>
        </tr>
        <tr style="background-color: #fef2f2; font-weight: bold;">
            <td colspan="6" width="69%" align="right" style="border: 0.5px solid #cbd5e1; padding: 5px 6px; font-size: 8pt; color: #991b1b;">
                TANGGAL EKSPIRASI AKHIR (SETELAH REMISI):
            </td>
            <td colspan="2" width="31%" align="center" style="border: 0.5px solid #cbd5e1; padding: 5px 4px; font-size: 9pt; color: #b91c1c;">
                <b>{$tglEkspirasiDef}</b>
            </td>
        </tr>
    </tfoot>
</table>

<!-- ============================================================== -->
<!-- HALAMAN 2: PERHITUNGAN MANUAL LENGKAP                        -->
<!-- ============================================================== -->
<div style="page-break-before: always;"></div>

<!-- KOP SURAT RESMI KANWIL KEMENIMIPAS BANTEN (HALAMAN 2) -->
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width="14%" align="center" valign="middle">
            {$logoHtml}
        </td>
        <td width="86%" align="center" valign="middle">
            <span style="font-size: 9.5pt; font-weight: bold; color: #1e293b;">KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</span><br>
            <span style="font-size: 9.5pt; font-weight: bold; color: #1e293b;">DIREKTORAT JENDERAL PEMASYARAKATAN</span><br>
            <span style="font-size: 12pt; font-weight: bold; color: #0f172a;">KANTOR WILAYAH BANTEN</span><br>
            <span style="font-size: 8.5pt; color: #334155;">Jl. KH. Sam'un No. 44, Serang, Banten | Pos-el: djpasbanten@gmail.com</span>
        </td>
    </tr>
</table>
<hr style="height: 1.5px; border: none; background-color: #0f172a; margin-top: 4px; margin-bottom: 6px;">
<br>

<h1 style="color: #0f172a; font-size: 13pt; text-align: center; font-weight: bold; margin: 0; padding: 4px 0 10px 0; letter-spacing: 0.3px;">{$judulProgram}</h1>
<br>

<!-- IDENTITAS SINGKAT LAMPIRAN -->
<table width="100%" cellpadding="5" cellspacing="0" style="border: 0.5px solid #cbd5e1; background-color: #f8fafc; font-size: 9pt; margin-bottom: 14px;">
    <tr>
        <td width="18%" style="color: #475569; padding: 5px 8px;">Nama WBP</td>
        <td width="32%" style="font-weight: bold; color: #0f172a; padding: 5px 8px;">: {$namaWbp}</td>
        <td width="22%" style="color: #475569; padding: 5px 8px;">Tgl. Mulai Ditahan</td>
        <td width="28%" style="font-weight: bold; color: #0f172a; padding: 5px 8px;">: {$tglAwal}</td>
    </tr>
    <tr>
        <td width="18%" style="color: #475569; padding: 5px 8px;">Nomor Register</td>
        <td width="32%" style="font-weight: bold; color: #0f172a; padding: 5px 8px;">: {$noRegister}</td>
        <td width="22%" style="color: #475569; padding: 5px 8px;">Tgl. Putusan</td>
        <td width="28%" style="font-weight: bold; color: #0f172a; padding: 5px 8px;">: {$tglPutusan}</td>
    </tr>
    <tr>
        <td width="18%" style="color: #475569; padding: 5px 8px;">Perkara / Pasal</td>
        <td width="32%" style="font-weight: bold; color: #0f172a; padding: 5px 8px;">: {$perkaraPasal}</td>
        <td width="22%" style="color: #475569; padding: 5px 8px;">Pidana Pokok</td>
        <td width="28%" style="font-weight: bold; color: #0f172a; padding: 5px 8px;">: {$lamaVonis}</td>
    </tr>
    <tr>
        <td width="18%" style="color: #475569; padding: 5px 8px;">Pidana Subsider</td>
        <td colspan="3" width="82%" style="font-weight: bold; color: #0f172a; padding: 5px 8px;">: {$subsiderIdentitasTeks}</td>
    </tr>
</table>
<br>

{$potahPdfHtml}

<!-- B. EKSPIRASI SEBELUM REMISI -->
<table width="100%" cellpadding="6" cellspacing="0" style="border: 0.5px solid #bfdbfe; margin-bottom: 14px;">
    <tr style="background-color: #eff6ff;">
        <td style="font-weight: bold; font-size: 10pt; color: #1e40af; padding: 7px 10px;">
            B. EKSPIRASI SEBELUM REMISI
        </td>
    </tr>
    <tr>
        <td style="padding: 10px 12px;">
            <table width="100%" cellpadding="2" cellspacing="0" border="0" style="font-size: 9.5pt; line-height: 1.5;">
                {$barisEkspirasiAwalPdf}
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiSebelumRemisiNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #b91c1c; font-size: 10.5pt;">{$ekspirasiSebelumRemisiIndo}</b></td>
                </tr>
                {$subsiderPdfSectionB}
            </table>
        </td>
    </tr>
</table>
<br>

<!-- C. PENTAHAPAN MASA PIDANA (SETELAH REMISI) -->
<table width="100%" cellpadding="6" cellspacing="0" style="border: 0.5px solid #fecaca; margin-bottom: 14px;">
    <tr style="background-color: #fef2f2;">
        <td style="font-weight: bold; font-size: 10pt; color: #991b1b; padding: 7px 10px;">
            C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)
        </td>
    </tr>
    <tr>
        <td style="padding: 10px 12px;">
            <table width="100%" cellpadding="2" cellspacing="0" border="0" style="font-size: 9.5pt; line-height: 1.5;">
                <tr>
                    <td colspan="3" style="font-size: 9.5pt; color: #0f172a; padding-bottom: 4px;">
                        &bull; <b>Jumlah Remisi yang diperoleh</b> = <b style="color: #15803d; font-size: 10pt;">{$totalRemisiTeks} ({$totalRemisiHari} Hari)</b>
                    </td>
                </tr>
                <tr><td colspan="3" style="height: 4px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #0f172a; font-weight: bold;">&bull; 1/2 Masa Pidana</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Tgl. Mulai Ditahan + ((1/2 x Masa Pidana) - Remisi)</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tglAwalNum} + ((1/2 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tgl12DefNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #0284c7; font-size: 10.5pt;">{$tgl12DefIndo}</b></td>
                </tr>
                <tr><td colspan="3" style="height: 6px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #0f172a; font-weight: bold;">&bull; 2/3 Masa Pidana</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Tgl. Mulai Ditahan + ((2/3 x Masa Pidana) - Remisi)</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tglAwalNum} + ((2/3 x {$lamaVonis}) - {$totalRemisiTeks})</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tgl23DefNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #15803d; font-size: 10.5pt;">{$tgl23DefIndo}</b></td>
                </tr>
                {$subsiderPdf23}
                <tr><td colspan="3" style="height: 6px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #0f172a; font-weight: bold;">&bull; Ekspirasi Akhir</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">Ekspirasi Sebelum Remisi - Remisi</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiSebelumRemisiNum} - {$totalRemisiTeks}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$ekspirasiDefNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #b91c1c; font-size: 10.5pt;">{$ekspirasiDefIndo}</b></td>
                </tr>
                {$subsiderPdfEkspirasiC}
                <tr><td colspan="3" style="height: 6px;"></td></tr>
                <tr>
                    <td width="24%" style="color: #b45309; font-weight: bold;">&bull; Usul SDP (H-2 Bln)</td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">2/3 Masa Pidana - 60 Hari</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tgl23DefNum} - 60 Hari</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%">{$tglUsulNum}</td>
                </tr>
                <tr>
                    <td width="24%"></td>
                    <td width="4%" align="center" style="font-weight: bold;">=</td>
                    <td width="72%"><b style="color: #b45309; font-size: 10.5pt;">{$tglUsulIndo}</b></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
HTML;
    }
}
