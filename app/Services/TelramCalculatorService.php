<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class TelramCalculatorService
{
    /**
     * Hitung Telram Mode Maju (Cari Tanggal Ekspirasi dari Tanggal Penahanan & Lama Vonis)
     *
     * @return array<string, mixed>
     */
    public function hitungMaju(string $tglAwalStr, int $tahun, int $bulan, int $hari): array
    {
        $tglAwal = Carbon::parse($tglAwalStr)->startOfDay();

        if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
            throw new InvalidArgumentException('Lama pidana vonis harus lebih dari 0.');
        }

        // Aturan Perhitungan Ekspirasi:
        // 1. Dimulai dari tahun: jika ada tahun, tambah tahun di tanggal & bulan yang sama
        // 2. Kemudian geser bulan dan hari: 1 bulan = 30 hari kalender aktual
        $tglEkspirasi = $tglAwal->copy();

        if ($tahun > 0) {
            $tglEkspirasi->addYearsNoOverflow($tahun);
        }

        $sisaHari = ($bulan * 30) + $hari;
        if ($sisaHari > 0) {
            $tglEkspirasi->addDays($sisaHari);
        }

        // Total hari baku: 1 Tahun = 360 hari (12 bln x 30 hari), 1 Bulan = 30 hari
        $totalHariVonis = ($tahun * 360) + ($bulan * 30) + $hari;
        $hari13 = (int) floor($totalHariVonis / 3);
        $hari12 = (int) floor($totalHariVonis / 2);
        $hari23 = (int) floor(($totalHariVonis * 2) / 3);

        $tgl13 = $tglAwal->copy()->addDays($hari13);
        $tgl12 = $tglAwal->copy()->addDays($hari12);
        $tgl23 = $tglAwal->copy()->addDays($hari23);

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

        $totalHariVonis = ($vThn * 360) + ($vBln * 30) + $vHri;
        $hari13 = (int) floor($totalHariVonis / 3);
        $hari12 = (int) floor($totalHariVonis / 2);
        $hari23 = (int) floor(($totalHariVonis * 2) / 3);

        $tgl13 = $tglAwal->copy()->addDays($hari13);
        $tgl12 = $tglAwal->copy()->addDays($hari12);
        $tgl23 = $tglAwal->copy()->addDays($hari23);

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
     * Hitung Proyeksi Remisi dan Hak Integrasi (PB, CB, Asimilasi)
     * Berdasarkan UU No. 22 Tahun 2022 & Keppres No. 174 Tahun 1999
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
        int $remisiAwalHari = 0
    ): array {
        $tglAwal = Carbon::parse($tglAwalStr)->startOfDay();

        if ($tahun <= 0 && $bulan <= 0 && $hari <= 0) {
            throw new InvalidArgumentException('Lama pidana vonis harus lebih dari 0.');
        }

        $agama = strtolower(trim($agama));
        $validAgama = ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'khonghucu'];
        if (! in_array($agama, $validAgama, true)) {
            $agama = 'islam';
        }

        if ($remisiAwalHari < 0) {
            $remisiAwalHari = 0;
        }

        // 1. Ekspirasi Murni (Sebelum Remisi)
        $tglEkspirasiMurni = $tglAwal->copy();
        if ($tahun > 0) {
            $tglEkspirasiMurni->addYearsNoOverflow($tahun);
        }
        $sisaHariVonis = ($bulan * 30) + $hari;
        if ($sisaHariVonis > 0) {
            $tglEkspirasiMurni->addDays($sisaHariVonis);
        }

        // Total hari baku: 1 Tahun = 360 hari, 1 Bulan = 30 hari
        $totalHariVonis = ($tahun * 360) + ($bulan * 30) + $hari;
        $hari13Murni = (int) floor($totalHariVonis / 3);
        $hari12Murni = (int) floor($totalHariVonis / 2);
        $hari23Murni = (int) floor(($totalHariVonis * 2) / 3);

        $tgl13Murni = $tglAwal->copy()->addDays($hari13Murni);
        $tgl12Murni = $tglAwal->copy()->addDays($hari12Murni);
        $tgl23Murni = $tglAwal->copy()->addDays($hari23Murni);

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

        // 4. Simulasi Proyeksi Remisi Sesuai Keppres No. 174 Tahun 1999
        $keRu = 0;
        $keRk = 0;
        $totalHariRemisi = $remisiAwalHari;
        $currentEkspirasi = $tglEkspirasiMurni->copy()->subDays($totalHariRemisi);
        $ledger = [];

        foreach ($eligibleEvents as $item) {
            $evDate = $item['carbon'];

            // Jika tanggal event sudah sama atau melewati proyeksi ekspirasi terkini, WBP sudah bebas
            if ($evDate->gte($currentEkspirasi)) {
                break;
            }

            $hariDijalani = (int) $tglAwal->diffInDays($evDate);

            // Syarat Minimal Keppres 174/1999: Minimal telah menjalani 6 bulan (180 hari)
            if ($hariDijalani < 180) {
                $ledger[] = [
                    'nama_event' => $item['nama_event'],
                    'jenis' => $item['jenis'],
                    'tahun' => $item['tahun'],
                    'tanggal' => $item['tanggal'],
                    'tanggal_indo' => self::formatTanggalIndonesia($evDate),
                    'hari_dijalani' => $hariDijalani,
                    'masa_dijalani_teks' => self::formatHariKeTeks($hariDijalani),
                    'eligible' => false,
                    'keterangan_status' => 'Belum Memenuhi Syarat (Masa pidana < 6 bulan)',
                    'tahap' => '-',
                    'besaran_hari' => 0,
                    'besaran_teks' => '0 Hari',
                    'total_akumulasi_hari' => $totalHariRemisi,
                    'perkiraan_ekspirasi_baru' => $currentEkspirasi->toDateString(),
                    'perkiraan_ekspirasi_baru_indo' => self::formatTanggalIndonesia($currentEkspirasi),
                ];

                continue;
            }

            // Narapidana Memenuhi Syarat (Eligible >= 6 bulan)
            $potonganHari = 0;
            $besaranTeks = '';
            $tahapTeks = '';

            if ($item['jenis'] === 'RU') {
                $keRu++;
                $tahapTeks = 'Tahun ke-'.$keRu;

                if ($keRu === 1) {
                    if ($hariDijalani < 360) {
                        $potonganHari = 30; // 1 Bulan (6 - 12 bulan)
                        $besaranTeks = '1 Bulan';
                    } else {
                        $potonganHari = 60; // 2 Bulan (>= 12 bulan)
                        $besaranTeks = '2 Bulan';
                    }
                } elseif ($keRu === 2) {
                    $potonganHari = 90; // 3 Bulan
                    $besaranTeks = '3 Bulan';
                } elseif ($keRu === 3) {
                    $potonganHari = 120; // 4 Bulan
                    $besaranTeks = '4 Bulan';
                } elseif ($keRu === 4 || $keRu === 5) {
                    $potonganHari = 150; // 5 Bulan
                    $besaranTeks = '5 Bulan';
                } else {
                    $potonganHari = 180; // 6 Bulan (Tahun ke-6 dst)
                    $besaranTeks = '6 Bulan';
                }
            } else {
                // Remisi Khusus (RK)
                $keRk++;
                $tahapTeks = 'Tahun ke-'.$keRk;

                if ($keRk === 1) {
                    if ($hariDijalani < 360) {
                        $potonganHari = 15; // 15 Hari (6 - 12 bulan)
                        $besaranTeks = '15 Hari';
                    } else {
                        $potonganHari = 30; // 1 Bulan (>= 12 bulan)
                        $besaranTeks = '1 Bulan';
                    }
                } elseif ($keRk === 2 || $keRk === 3) {
                    $potonganHari = 30; // 1 Bulan
                    $besaranTeks = '1 Bulan';
                } elseif ($keRk === 4 || $keRk === 5) {
                    $potonganHari = 45; // 1 Bulan 15 Hari
                    $besaranTeks = '1 Bulan 15 Hari';
                } else {
                    $potonganHari = 60; // 2 Bulan (Tahun ke-6 dst)
                    $besaranTeks = '2 Bulan';
                }
            }

            // Pastikan potongan remisi tidak melebihi sisa hari pidana aktual menuju ekspirasi
            $sisaHariSebelumEkspirasi = (int) $evDate->diffInDays($currentEkspirasi);
            if ($potonganHari > $sisaHariSebelumEkspirasi) {
                $potonganHari = max(0, $sisaHariSebelumEkspirasi);
                $besaranTeks = "{$potonganHari} Hari (Sisa Pidana)";
            }

            $totalHariRemisi += $potonganHari;
            $currentEkspirasi = $tglEkspirasiMurni->copy()->subDays($totalHariRemisi);

            $ledger[] = [
                'nama_event' => $item['nama_event'],
                'jenis' => $item['jenis'],
                'tahun' => $item['tahun'],
                'tanggal' => $item['tanggal'],
                'tanggal_indo' => self::formatTanggalIndonesia($evDate),
                'hari_dijalani' => $hariDijalani,
                'masa_dijalani_teks' => self::formatHariKeTeks($hariDijalani),
                'eligible' => true,
                'keterangan_status' => 'Memenuhi Syarat',
                'tahap' => $tahapTeks,
                'besaran_hari' => $potonganHari,
                'besaran_teks' => $besaranTeks,
                'total_akumulasi_hari' => $totalHariRemisi,
                'perkiraan_ekspirasi_baru' => $currentEkspirasi->toDateString(),
                'perkiraan_ekspirasi_baru_indo' => self::formatTanggalIndonesia($currentEkspirasi),
            ];
        }

        // 5. Perhitungan Tanggal 2/3 dan Pengusulan Integrasi Sesuai Rumus Baku SDP Ditjenpas
        // Rumus SDP: 2/3 Murni dikurangi Full Akumulasi Remisi
        $hari23Definitif = max($minHariIntegrasi, $hari23Murni - $totalHariRemisi);
        $tgl23Definitif = $tglAwal->copy()->addDays($hari23Definitif);

        // Tanggal 1/2 Asimilasi Definitif (1/2 Murni dikurangi Full Remisi, min 180 hari)
        $hari12Definitif = max(180, $hari12Murni - $totalHariRemisi);
        $tgl12Definitif = $tglAwal->copy()->addDays($hari12Definitif);

        // Ekspirasi Definitif Setelah Remisi
        $tglEkspirasiDefinitif = $tglEkspirasiMurni->copy()->subDays($totalHariRemisi);

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
                'pesan' => "Pengusulan berkas ke sistem & permintaan Litmas Bapas dapat dimulai pada {$this->formatTanggalIndonesia($tglMulaiPengusulan)} (tersisa {$sisaHariMenujuUsul} hari lagi).",
            ];
        } elseif ($today->lte($tgl23Definitif)) {
            $sisaHariJatuhTempo = (int) $today->diffInDays($tgl23Definitif);
            $statusPengusulan = [
                'kode' => 'waktunya_usul',
                'label' => 'Waktunya Pengusulan!',
                'badge_class' => 'bg-warning text-dark',
                'sisa_hari' => $sisaHariJatuhTempo,
                'pesan' => "Waktunya Pengusulan! Segera ajukan permintaan Litmas ke Bapas dan jadwalkan Sidang TPP UPT. Jatuh tempo 2/3 tercapai dalam {$sisaHariJatuhTempo} hari lagi ({$this->formatTanggalIndonesia($tgl23Definitif)}).",
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

        return [
            'mode' => 'simulasi_remisi',
            'tgl_awal' => $tglAwal->toDateString(),
            'lama_pidana_teks' => implode(', ', $formatVonis) ?: '0 Hari',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'hari' => $hari,
            'total_hari_vonis' => $totalHariVonis,
            'agama' => ucfirst($agama),
            'jenis_program' => $jenisProgram,
            'nama_program' => $namaProgram,
            'keterangan_program' => $keteranganProgram,
            'remisi_awal_hari' => $remisiAwalHari,
            'total_proyeksi_remisi_hari' => $totalHariRemisi,
            'total_proyeksi_remisi_teks' => self::formatHariKeTeks($totalHariRemisi),
            'status_pengusulan' => $statusPengusulan,
            'milestones' => [
                'ekspirasi_murni' => $tglEkspirasiMurni->toDateString(),
                'tgl_1_3_murni' => $tgl13Murni->toDateString(),
                'tgl_1_2_murni' => $tgl12Murni->toDateString(),
                'tgl_2_3_murni' => $tgl23Murni->toDateString(),
                'tgl_1_2_definitif' => $tgl12Definitif->toDateString(),
                'tgl_2_3_definitif' => $tgl23Definitif->toDateString(),
                'tgl_mulai_pengusulan' => $tglMulaiPengusulan->toDateString(),
                'ekspirasi_definitif' => $tglEkspirasiDefinitif->toDateString(),
            ],
            'formatted' => [
                'tgl_awal' => self::formatTanggalIndonesia($tglAwal),
                'ekspirasi_murni' => self::formatTanggalIndonesia($tglEkspirasiMurni),
                'tgl_1_3_murni' => self::formatTanggalIndonesia($tgl13Murni),
                'tgl_1_2_murni' => self::formatTanggalIndonesia($tgl12Murni),
                'tgl_2_3_murni' => self::formatTanggalIndonesia($tgl23Murni),
                'tgl_1_2_definitif' => self::formatTanggalIndonesia($tgl12Definitif),
                'tgl_2_3_definitif' => self::formatTanggalIndonesia($tgl23Definitif),
                'tgl_mulai_pengusulan' => self::formatTanggalIndonesia($tglMulaiPengusulan),
                'ekspirasi_definitif' => self::formatTanggalIndonesia($tglEkspirasiDefinitif),
            ],
            'proyeksi_ledger' => $ledger,
        ];
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
}
