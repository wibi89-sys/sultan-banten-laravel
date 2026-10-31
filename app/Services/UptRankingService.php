<?php

namespace App\Services;

use App\Models\Instansi;
use App\Models\Upt;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class UptRankingService
{
    /**
     * Hitung rekapitulasi poin dan peringkat kedisiplinan seluruh UPT dalam rentang tanggal.
     *
     * @param  string|null  $startDate  (Y-m-d)
     * @param  string|null  $endDate  (Y-m-d)
     */
    public static function calculateRanking(?string $startDate = null, ?string $endDate = null): array
    {
        $today = date('Y-m-d');
        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-t');

        // Pastikan format tanggal valid
        try {
            $carbonStart = Carbon::parse($startDate)->startOfDay();
            $carbonEnd = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            $carbonStart = Carbon::now()->startOfMonth();
            $carbonEnd = Carbon::now()->endOfMonth();
            $startDate = $carbonStart->format('Y-m-d');
            $endDate = $carbonEnd->format('Y-m-d');
        }

        if ($carbonStart->gt($carbonEnd)) {
            $temp = $carbonStart;
            $carbonStart = $carbonEnd;
            $carbonEnd = $temp;
            $startDate = $carbonStart->format('Y-m-d');
            $endDate = $carbonEnd->format('Y-m-d');
        }

        // Tanggal evaluasi efektif: tidak menghitung tanggal masa depan jika belum tiba
        $effectiveEnd = Carbon::parse(min($endDate, $today))->endOfDay();
        $isFutureRangeOnly = $carbonStart->gt($effectiveEnd);

        // Ambil batas jam instansi
        $instansi = Instansi::getFirst();
        $jamMulaiStr = substr($instansi->jam_mulai ?? '08:00:00', 0, 8);
        if (strlen($jamMulaiStr) === 5) {
            $jamMulaiStr .= ':00';
        }
        $jamSelesaiStr = substr($instansi->jam_selesai ?? '11:00:00', 0, 8);
        if (strlen($jamSelesaiStr) === 5) {
            $jamSelesaiStr .= ':00';
        }

        // Generate daftar hari dalam periode evaluasi
        $dateList = [];
        if (! $isFutureRangeOnly) {
            $period = CarbonPeriod::create($carbonStart->format('Y-m-d'), $effectiveEnd->format('Y-m-d'));
            foreach ($period as $date) {
                $dateList[] = $date->format('Y-m-d');
            }
        }
        $totalDays = count($dateList);
        $maxPointsPerUpt = $totalDays * 100;

        // Ambil seluruh UPT terdaftar
        $upts = Upt::orderBy('nama_upt')->get();

        if ($totalDays === 0) {
            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'effective_end_date' => $effectiveEnd->format('Y-m-d'),
                    'total_days' => 0,
                    'max_points' => 0,
                    'jam_mulai' => substr($jamMulaiStr, 0, 5),
                    'jam_selesai' => substr($jamSelesaiStr, 0, 5),
                ],
                'rankings' => [],
                'summary' => [
                    'total_upt' => $upts->count(),
                    'top_upt' => null,
                    'avg_compliance' => 0,
                    'total_sync_kroscek' => 0,
                ],
            ];
        }

        $queryStartDate = $startDate;
        $queryEndDate = $effectiveEnd->format('Y-m-d');

        // 1. Data Penghuni: min created_at & total tahanan valid kroscek
        $penghuniRaw = DB::table('data_penghuni')
            ->select(
                'upt_id',
                'tanggal',
                DB::raw('MIN(created_at) as first_created_at'),
                DB::raw('MAX(updated_at) as last_updated_at'),
                DB::raw("SUM(CASE WHEN klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') 
                         THEN (tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) 
                         ELSE 0 END) as total_tahanan")
            )
            ->whereBetween('tanggal', [$queryStartDate, $queryEndDate])
            ->groupBy('upt_id', 'tanggal')
            ->get();

        $penghuniMap = [];
        foreach ($penghuniRaw as $r) {
            $penghuniMap[$r->upt_id][$r->tanggal] = [
                'created_at' => $r->first_created_at,
                'updated_at' => $r->last_updated_at,
                'total_tahanan' => (int) $r->total_tahanan,
            ];
        }

        // 2. Data Overstaying: min created_at & total tahanan AI-AV
        $overstayingRaw = DB::table('data_overstaying')
            ->select(
                'upt_id',
                'tanggal',
                DB::raw('MIN(created_at) as first_created_at'),
                DB::raw('MAX(updated_at) as last_updated_at'),
                DB::raw('SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) as total_tahanan')
            )
            ->whereBetween('tanggal', [$queryStartDate, $queryEndDate])
            ->groupBy('upt_id', 'tanggal')
            ->get();

        $overstayingMap = [];
        foreach ($overstayingRaw as $r) {
            $overstayingMap[$r->upt_id][$r->tanggal] = [
                'created_at' => $r->first_created_at,
                'updated_at' => $r->last_updated_at,
                'total_tahanan' => (int) $r->total_tahanan,
            ];
        }

        // 5. Activity Logs: cek riwayat perbaikan (edit) modul penghuni / overstaying
        $editLogMap = [];
        try {
            $editLogs = DB::table('activity_logs')
                ->where('aksi', 'edit')
                ->whereIn('modul', ['penghuni', 'overstaying'])
                ->whereNotNull('detail')
                ->get(['detail', 'created_at']);

            foreach ($editLogs as $log) {
                $detail = is_array($log->detail) ? $log->detail : json_decode($log->detail, true);
                if (is_array($detail) && ! empty($detail['upt_id']) && ! empty($detail['tanggal'])) {
                    // Jika log memiliki info 'is_tahanan_changed' dan nilainya false, bukan perbaikan tahanan
                    if (isset($detail['is_tahanan_changed']) && ! $detail['is_tahanan_changed']) {
                        continue;
                    }

                    $uId = $detail['upt_id'];
                    $t = $detail['tanggal'];
                    $logTime = ! empty($log->created_at) ? Carbon::parse($log->created_at) : null;
                    if (! isset($editLogMap[$uId][$t]) || ($logTime && (! $editLogMap[$uId][$t] || $logTime->gt($editLogMap[$uId][$t])))) {
                        $editLogMap[$uId][$t] = $logTime;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel atau log tidak tersedia
        }

        // 3. Data Integrasi: min created_at
        $integrasiRaw = DB::table('data_integrasi')
            ->select('upt_id', 'tanggal', DB::raw('MIN(created_at) as first_created_at'))
            ->whereBetween('tanggal', [$queryStartDate, $queryEndDate])
            ->groupBy('upt_id', 'tanggal')
            ->get();

        $integrasiMap = [];
        foreach ($integrasiRaw as $r) {
            $integrasiMap[$r->upt_id][$r->tanggal] = [
                'created_at' => $r->first_created_at,
            ];
        }

        // 4. Data Bebas Harian: min created_at
        $bebasRaw = DB::table('data_bebas_harian')
            ->select('upt_id', 'tanggal', DB::raw('MIN(created_at) as first_created_at'))
            ->whereBetween('tanggal', [$queryStartDate, $queryEndDate])
            ->groupBy('upt_id', 'tanggal')
            ->get();

        $bebasMap = [];
        foreach ($bebasRaw as $r) {
            $bebasMap[$r->upt_id][$r->tanggal] = [
                'created_at' => $r->first_created_at,
            ];
        }

        $namaHari = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $rankings = [];

        foreach ($upts as $upt) {
            $uptId = $upt->id;
            $totalPoints = 0;
            $countHariSempurna = 0;
            $countModulTepat = 0;
            $countModulTerlambat = 0;
            $countModulKosong = 0;
            $countKroscekSesuai = 0;
            $countKroscekPerbaikan = 0;
            $countKroscekSelisih = 0;
            $countKroscekKosong = 0;

            // Tracking waktu input & kriteria penentu juara (tie-breaker)
            $countInputHariHTepat = 0;
            $countInputHMinus1 = 0;
            $countInputHariHTelat = 0;
            $countInputSusulan = 0;
            $countGold = 0;
            $countSilver = 0;

            $inputTimeMinutesHariHSum = 0;
            $inputTimeCountHariH = 0;
            $inputTimeMinutesAllSum = 0;
            $inputTimeCountAll = 0;

            $dailyBreakdown = [];
            $selisihDates = [];

            $trackModuleTiming = function ($score) use (
                &$countInputHariHTepat,
                &$countInputHMinus1,
                &$countInputHariHTelat,
                &$countInputSusulan,
                &$countGold,
                &$countSilver,
                &$inputTimeMinutesHariHSum,
                &$inputTimeCountHariH,
                &$inputTimeMinutesAllSum,
                &$inputTimeCountAll
            ) {
                if (! empty($score['medal'])) {
                    if ($score['medal'] === 'gold') {
                        $countGold++;
                    } elseif ($score['medal'] === 'silver') {
                        $countSilver++;
                    }
                }

                $type = $score['timing_type'] ?? null;
                if ($type === 'golden_hour' || $type === 'hari_h_tepat') {
                    $countInputHariHTepat++;
                    if ($score['jam_menit'] !== null) {
                        $inputTimeMinutesHariHSum += $score['jam_menit'];
                        $inputTimeCountHariH++;
                    }
                } elseif ($type === 'h_minus_1') {
                    $countInputHMinus1++;
                } elseif ($type === 'hari_h_telat') {
                    $countInputHariHTelat++;
                    if ($score['jam_menit'] !== null) {
                        $inputTimeMinutesHariHSum += $score['jam_menit'];
                        $inputTimeCountHariH++;
                    }
                } elseif ($type === 'susulan') {
                    $countInputSusulan++;
                }

                if ($score['jam_menit'] !== null) {
                    $inputTimeMinutesAllSum += $score['jam_menit'];
                    $inputTimeCountAll++;
                }
            };

            foreach ($dateList as $tgl) {
                $deadline = Carbon::parse($tgl.' '.$jamSelesaiStr);
                $carbonTgl = Carbon::parse($tgl);
                $hariLabel = $namaHari[$carbonTgl->englishDayOfWeek] ?? $carbonTgl->englishDayOfWeek;

                $dayPoints = 0;

                // Modul 1: Penghuni
                $pData = $penghuniMap[$uptId][$tgl] ?? null;
                $pScore = self::evaluateModule($pData, $deadline, $tgl);
                $dayPoints += $pScore['poin'];
                if ($pScore['status'] === 'tepat') {
                    $countModulTepat++;
                } elseif ($pScore['status'] === 'terlambat') {
                    $countModulTerlambat++;
                } else {
                    $countModulKosong++;
                }
                $trackModuleTiming($pScore);

                // Modul 2: Overstaying
                $oData = $overstayingMap[$uptId][$tgl] ?? null;
                $oScore = self::evaluateModule($oData, $deadline, $tgl);
                $dayPoints += $oScore['poin'];
                if ($oScore['status'] === 'tepat') {
                    $countModulTepat++;
                } elseif ($oScore['status'] === 'terlambat') {
                    $countModulTerlambat++;
                } else {
                    $countModulKosong++;
                }
                $trackModuleTiming($oScore);

                // Modul 3: Integrasi
                $iData = $integrasiMap[$uptId][$tgl] ?? null;
                $iScore = self::evaluateModule($iData, $deadline, $tgl);
                $dayPoints += $iScore['poin'];
                if ($iScore['status'] === 'tepat') {
                    $countModulTepat++;
                } elseif ($iScore['status'] === 'terlambat') {
                    $countModulTerlambat++;
                } else {
                    $countModulKosong++;
                }
                $trackModuleTiming($iScore);

                // Modul 4: Bebas Harian
                $bData = $bebasMap[$uptId][$tgl] ?? null;
                $bScore = self::evaluateModule($bData, $deadline, $tgl);
                $dayPoints += $bScore['poin'];
                if ($bScore['status'] === 'tepat') {
                    $countModulTepat++;
                } elseif ($bScore['status'] === 'terlambat') {
                    $countModulTerlambat++;
                } else {
                    $countModulKosong++;
                }
                $trackModuleTiming($bScore);

                // Komponen 5: Kroscek Tahanan (Penghuni vs Overstaying)
                $hasEditLog = isset($editLogMap[$uptId][$tgl]);
                $editLogTime = $editLogMap[$uptId][$tgl] ?? null;
                $kroscekScore = self::evaluateKroscek($pData, $oData, $hasEditLog, $deadline, $tgl, $editLogTime);

                if ($kroscekScore['status'] === 'sesuai') {
                    $countKroscekSesuai++;
                } elseif ($kroscekScore['status'] === 'sesuai_perbaikan_jam_input') {
                    $countKroscekSesuai++;
                    $countKroscekPerbaikan++;
                } elseif ($kroscekScore['status'] === 'sesuai_perbaikan_luar_jam' || $kroscekScore['status'] === 'sesuai_perbaikan') {
                    $countKroscekSesuai++;
                    $countKroscekPerbaikan++;
                } elseif ($kroscekScore['status'] === 'sesuai_perbaikan_lewat_hari') {
                    $countKroscekPerbaikan++;
                } elseif ($kroscekScore['status'] === 'selisih') {
                    $countKroscekSelisih++;
                    $selisihDates[] = [
                        'tanggal' => $tgl,
                        'hari' => $hariLabel,
                        'tahanan_penghuni' => $kroscekScore['tahanan_penghuni'],
                        'tahanan_overstaying' => $kroscekScore['tahanan_overstaying'],
                        'selisih' => $kroscekScore['selisih'],
                    ];
                } else {
                    $countKroscekKosong++;
                }

                $dayPoints += $kroscekScore['poin'];
                $totalPoints += $dayPoints;

                if ($dayPoints === 100) {
                    $countHariSempurna++;
                }

                $dailyBreakdown[] = [
                    'tanggal' => $tgl,
                    'hari' => $hariLabel,
                    'penghuni' => $pScore,
                    'overstaying' => $oScore,
                    'integrasi' => $iScore,
                    'bebas' => $bScore,
                    'kroscek' => $kroscekScore,
                    'total_poin' => $dayPoints,
                ];
            }

            $avgMinutesHariH = $inputTimeCountHariH > 0
                ? (int) round($inputTimeMinutesHariHSum / $inputTimeCountHariH)
                : null;
            $avgMinutesAll = $inputTimeCountAll > 0
                ? (int) round($inputTimeMinutesAllSum / $inputTimeCountAll)
                : null;

            if ($avgMinutesHariH !== null) {
                $avgTimeString = sprintf('%02d:%02d WIB', floor($avgMinutesHariH / 60), $avgMinutesHariH % 60);
            } elseif ($avgMinutesAll !== null) {
                $avgTimeString = sprintf('%02d:%02d WIB (H-1/Susulan)', floor($avgMinutesAll / 60), $avgMinutesAll % 60);
            } else {
                $avgTimeString = '-';
            }

            $compliancePct = $maxPointsPerUpt > 0
                ? round(($totalPoints / $maxPointsPerUpt) * 100, 1)
                : 0;

            $rankings[] = [
                'upt_id' => $uptId,
                'nama_upt' => $upt->nama_upt,
                'total_points' => $totalPoints,
                'max_points' => $maxPointsPerUpt,
                'persentase_kepatuhan' => $compliancePct,
                'count_gold' => $countGold,
                'count_silver' => $countSilver,
                'count_hari_sempurna' => $countHariSempurna,
                'count_modul_tepat' => $countModulTepat,
                'count_modul_terlambat' => $countModulTerlambat,
                'count_modul_kosong' => $countModulKosong,
                'count_input_hari_h_tepat' => $countInputHariHTepat,
                'count_input_h_minus_1' => $countInputHMinus1,
                'count_input_hari_h_telat' => $countInputHariHTelat,
                'count_input_susulan' => $countInputSusulan,
                'avg_minutes_hari_h' => $avgMinutesHariH ?? ($avgMinutesAll ?? PHP_INT_MAX),
                'count_kroscek_sesuai' => $countKroscekSesuai,
                'count_kroscek_perbaikan' => $countKroscekPerbaikan,
                'count_kroscek_selisih' => $countKroscekSelisih,
                'count_kroscek_kosong' => $countKroscekKosong,
                'rata_rata_jam_input' => $avgTimeString,
                'daily_breakdown' => $dailyBreakdown,
                'selisih_dates' => $selisihDates,
            ];
        }

        // Urutkan ranking dengan prioritas poin, kroscek, dan tie-breaker ketepatan waktu
        self::sortRankings($rankings);

        // Berikan nomor peringkat dan tandai yang terburuk (peringkat paling bawah)
        $totalRanked = count($rankings);
        foreach ($rankings as $idx => &$item) {
            $item['rank'] = $idx + 1;
            $item['is_worst'] = ($totalRanked > 1 && ($idx + 1) === $totalRanked);
        }
        unset($item);

        $totalCompliance = array_sum(array_column($rankings, 'persentase_kepatuhan'));
        $avgCompliance = count($rankings) > 0 ? round($totalCompliance / count($rankings), 1) : 0;
        $totalSync = array_sum(array_column($rankings, 'count_kroscek_sesuai'));

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'effective_end_date' => $effectiveEnd->format('Y-m-d'),
                'total_days' => $totalDays,
                'max_points' => $maxPointsPerUpt,
                'jam_mulai' => substr($jamMulaiStr, 0, 5),
                'jam_selesai' => substr($jamSelesaiStr, 0, 5),
            ],
            'rankings' => $rankings,
            'summary' => [
                'total_upt' => count($rankings),
                'top_upt' => $rankings[0] ?? null,
                'lowest_upt' => $totalRanked > 1 ? $rankings[$totalRanked - 1] : null,
                'avg_compliance' => $avgCompliance,
                'total_sync_kroscek' => $totalSync,
            ],
        ];
    }

    /**
     * Urutkan ranking berdasarkan kriteria poin, medali golden hours, kepatuhan, kroscek, dan tie-breaker.
     *
     * Prioritas Penentu Juara:
     * 1. Total Poin Tertinggi (desc)
     * 2. Jumlah Medali Emas (Gold) / Pagi Hari-H < 08.00 WIB (desc)
     * 3. Jumlah Medali Perak (Silver) / Jam Kerja Hari-H 08.00 - 11.00 WIB (desc)
     * 4. Jumlah Hari Sempurna (100 Poin) (desc)
     * 5. Jumlah Kroscek Sesuai (desc)
     * 6. Rata-rata Jam Input Hari-H paling awal (asc)
     * 7. Hari Sebelumnya (H-1) lebih diutamakan daripada keterlambatan (desc H-1)
     * 8. Minim keterlambatan pada Hari-H (asc)
     * 9. Minim keterlambatan susulan lewat hari (asc)
     * 10. Nama UPT alfabetis (asc)
     */
    public static function sortRankings(array &$rankings): void
    {
        usort($rankings, function ($a, $b) {
            // 1. Total Poin Tertinggi
            if ($a['total_points'] !== $b['total_points']) {
                return $b['total_points'] <=> $a['total_points'];
            }

            // 2. PRIORITAS UTAMA: Jumlah Medali Emas (Gold) - Input Hari-H sebelum 08.00 WIB
            $aGold = $a['count_gold'] ?? 0;
            $bGold = $b['count_gold'] ?? 0;
            if ($aGold !== $bGold) {
                return $bGold <=> $aGold;
            }

            // 3. PRIORITAS KEDUA: Jumlah Medali Perak (Silver) - Input Hari-H jam kerja 08.00 - 11.00 WIB
            $aSilver = $a['count_silver'] ?? 0;
            $bSilver = $b['count_silver'] ?? 0;
            if ($aSilver !== $bSilver) {
                return $bSilver <=> $aSilver;
            }

            // 4. Konsistensi Hari-H Tepat Waktu (Total input tepat di Hari-H)
            $aHariHTepat = $a['count_input_hari_h_tepat'] ?? 0;
            $bHariHTepat = $b['count_input_hari_h_tepat'] ?? 0;
            if ($aHariHTepat !== $bHariHTepat) {
                return $bHariHTepat <=> $aHariHTepat;
            }

            // 5. Jumlah Hari Sempurna (100 Poin)
            if ($a['count_hari_sempurna'] !== $b['count_hari_sempurna']) {
                return $b['count_hari_sempurna'] <=> $a['count_hari_sempurna'];
            }

            // 6. Jumlah Kroscek Sesuai
            if ($a['count_kroscek_sesuai'] !== $b['count_kroscek_sesuai']) {
                return $b['count_kroscek_sesuai'] <=> $a['count_kroscek_sesuai'];
            }

            // 7. Rata-rata jam input Hari-H paling awal
            $aAvg = $a['avg_minutes_hari_h'] ?? PHP_INT_MAX;
            $bAvg = $b['avg_minutes_hari_h'] ?? PHP_INT_MAX;
            if ($aAvg !== $bAvg) {
                return $aAvg <=> $bAvg;
            }

            // 8. Pendekatan hari sebelumnya (H-1) lebih diutamakan daripada keterlambatan
            $aH1 = $a['count_input_h_minus_1'] ?? 0;
            $bH1 = $b['count_input_h_minus_1'] ?? 0;
            if ($aH1 !== $bH1) {
                return $bH1 <=> $aH1;
            }

            // 9. Minim keterlambatan pada Hari-H
            $aTelat = $a['count_input_hari_h_telat'] ?? 0;
            $bTelat = $b['count_input_hari_h_telat'] ?? 0;
            if ($aTelat !== $bTelat) {
                return $aTelat <=> $bTelat;
            }

            // 10. Minim keterlambatan susulan lewat hari
            $aSusulan = $a['count_input_susulan'] ?? 0;
            $bSusulan = $b['count_input_susulan'] ?? 0;
            if ($aSusulan !== $bSusulan) {
                return $aSusulan <=> $bSusulan;
            }

            return strcmp($a['nama_upt'], $b['nama_upt']);
        });
    }

    /**
     * Evaluasi ketepatan waktu suatu modul berdasarkan created_at vs deadline
     */
    public static function evaluateModule(?array $data, Carbon $deadline, string $tanggalLaporan): array
    {
        if (! $data || empty($data['created_at'])) {
            return [
                'status' => 'kosong',
                'poin' => 0,
                'medal' => null,
                'jam_str' => '-',
                'jam_menit' => null,
                'keterangan' => 'Belum Input',
                'timing_type' => 'kosong',
                'is_hari_h' => false,
                'is_golden' => false,
                'is_silver' => false,
            ];
        }

        $createdAt = Carbon::parse($data['created_at']);
        $jamMenit = ($createdAt->hour * 60) + $createdAt->minute;
        $createdDate = $createdAt->format('Y-m-d');

        $targetDate = Carbon::parse($tanggalLaporan)->startOfDay();
        $sourceDate = $createdAt->copy()->startOfDay();

        // 1. Dibuat pada Hari-H (Same Day)
        if ($createdDate === $tanggalLaporan) {
            $jamStr = $createdAt->format('H:i');
            $ket = $jamStr.' WIB';

            // Dibuat sebelum atau tepat pada deadline jam kerja (<= 11:00 WIB)
            if ($createdAt->lte($deadline)) {
                // Golden Hours: Diinput pagi hari sebelum jam 08.00 WIB (< 08.00 WIB) -> Medali Emas
                $isGolden = ($createdAt->hour < 8);

                return [
                    'status' => 'tepat',
                    'poin' => 20,
                    'medal' => $isGolden ? 'gold' : 'silver',
                    'jam_str' => $jamStr,
                    'jam_menit' => $jamMenit,
                    'keterangan' => $isGolden ? "{$ket} (Pagi Prima)" : "{$ket} (Jam Kerja)",
                    'timing_type' => $isGolden ? 'golden_hour' : 'hari_h_tepat',
                    'is_hari_h' => true,
                    'is_golden' => $isGolden,
                    'is_silver' => ! $isGolden,
                ];
            }

            // Melewati batas jam kerja di Hari-H (lewat jam 11:00 WIB s/d 23:59) -> Penurunan Poin ke 10
            return [
                'status' => 'terlambat',
                'poin' => 10,
                'medal' => null,
                'jam_str' => $jamStr,
                'jam_menit' => $jamMenit,
                'keterangan' => "{$ket} (Terlambat Hari-H)",
                'timing_type' => 'hari_h_telat',
                'is_hari_h' => true,
                'is_golden' => false,
                'is_silver' => false,
            ];
        }

        // 2. Dibuat sebelum Hari-H (H-1, H-2, dst)
        if ($createdDate < $tanggalLaporan) {
            $daysDiff = abs((int) $targetDate->diffInDays($sourceDate));
            $ket = 'H-'.$daysDiff.' ('.$createdAt->format('d/m H:i').')';

            // Poin tetap 20 Penuh (penurunan poin hanya jika terlewat jam kerja), tapi TANPA MEDALI
            return [
                'status' => 'tepat',
                'poin' => 20,
                'medal' => null,
                'jam_str' => $createdAt->format('d/m H:i'),
                'jam_menit' => $jamMenit,
                'keterangan' => $ket.' (Persiapan Maju)',
                'timing_type' => 'h_minus_1',
                'is_hari_h' => false,
                'is_golden' => false,
                'is_silver' => false,
            ];
        }

        // 3. Susulan setelah hari-H lewat (H+1, H+2 dst) -> Penurunan Poin ke 5
        $daysDiff = abs((int) $sourceDate->diffInDays($targetDate));
        $ket = 'Susulan H+'.$daysDiff.' ('.$createdAt->format('d/m H:i').')';

        return [
            'status' => 'terlambat',
            'poin' => 5,
            'medal' => null,
            'jam_str' => $createdAt->format('d/m H:i'),
            'jam_menit' => $jamMenit,
            'keterangan' => $ket,
            'timing_type' => 'susulan',
            'is_hari_h' => false,
            'is_golden' => false,
            'is_silver' => false,
        ];
    }

    /**
     * Evaluasi kesesuaian (kroscek) jumlah tahanan antara Data Penghuni dan Data Overstaying.
     *
     * Aturan Poin:
     * - Sesuai Langsung = +20 Poin
     * - Sesuai Hasil Perbaikan di jam Input = +20 Poin
     * - Sesuai Hasil Perbaikan diluar jam input dihari yang sama = +10 Poin
     * - Masih Selisih / Kosong / diperbaiki lewat hari = 0 Poin
     */
    public static function evaluateKroscek(
        ?array $penghuniData,
        ?array $overstayingData,
        bool $hasEditLog = false,
        ?Carbon $deadline = null,
        ?string $tgl = null,
        ?Carbon $editLogTime = null
    ): array {
        if (! $penghuniData || ! $overstayingData) {
            return [
                'status' => 'kosong',
                'poin' => 0,
                'tahanan_penghuni' => $penghuniData['total_tahanan'] ?? null,
                'tahanan_overstaying' => $overstayingData['total_tahanan'] ?? null,
                'selisih' => 0,
                'is_perbaikan' => false,
                'keterangan' => 'Belum Lengkap',
                'edit_time' => null,
            ];
        }

        $pTotal = (int) ($penghuniData['total_tahanan'] ?? 0);
        $oTotal = (int) ($overstayingData['total_tahanan'] ?? 0);

        if ($pTotal !== $oTotal) {
            $selisih = abs($pTotal - $oTotal);

            return [
                'status' => 'selisih',
                'poin' => 0,
                'tahanan_penghuni' => $pTotal,
                'tahanan_overstaying' => $oTotal,
                'selisih' => $selisih,
                'is_perbaikan' => false,
                'keterangan' => "Selisih {$selisih}",
                'edit_time' => null,
            ];
        }

        // Tahanan Penghuni == Tahanan Overstaying (Sinkron)
        // Sebuah data yang sudah sinkron HANYA dianggap sebagai hasil perbaikan jika memang ada riwayat
        // log edit yang mengubah data tahanan ($hasEditLog == true).
        // Perubahan updated_at pada data_penghuni (misal karena edit data narapidana) atau data_overstaying
        // tanpa adanya log edit tahanan TIDAK BOLEH dianggap sebagai perbaikan tahanan.
        if (! $hasEditLog) {
            return [
                'status' => 'sesuai',
                'poin' => 20,
                'tahanan_penghuni' => $pTotal,
                'tahanan_overstaying' => $oTotal,
                'selisih' => 0,
                'is_perbaikan' => false,
                'keterangan' => 'Sesuai Langsung',
                'edit_time' => null,
            ];
        }

        // Tahanan Sinkron Hasil Perbaikan ($hasEditLog == true)
        $latestEditTime = $editLogTime;

        if (! $latestEditTime) {
            $pUpdated = ! empty($penghuniData['updated_at']) ? Carbon::parse($penghuniData['updated_at']) : null;
            $oUpdated = ! empty($overstayingData['updated_at']) ? Carbon::parse($overstayingData['updated_at']) : null;
            if ($pUpdated && $oUpdated) {
                $latestEditTime = $pUpdated->gt($oUpdated) ? $pUpdated : $oUpdated;
            } else {
                $latestEditTime = $pUpdated ?: $oUpdated;
            }
        }

        // 2, 3, 4: Sesuai Hasil Perbaikan
        $targetDateStr = $tgl;
        if (! $targetDateStr && $deadline) {
            $targetDateStr = $deadline->format('Y-m-d');
        }
        if (! $targetDateStr) {
            $targetDateStr = ! empty($penghuniData['created_at'])
                ? Carbon::parse($penghuniData['created_at'])->format('Y-m-d')
                : (! empty($overstayingData['created_at']) ? Carbon::parse($overstayingData['created_at'])->format('Y-m-d') : Carbon::now()->format('Y-m-d'));
        }

        $effectiveDeadline = $deadline ?? Carbon::parse($targetDateStr.' 11:00:00');

        // Jika waktu edit tidak terlacak namun terbukti ada edit, gunakan fallback luar jam hari-H
        if (! $latestEditTime) {
            return [
                'status' => 'sesuai_perbaikan_luar_jam',
                'poin' => 10,
                'tahanan_penghuni' => $pTotal,
                'tahanan_overstaying' => $oTotal,
                'selisih' => 0,
                'is_perbaikan' => true,
                'keterangan' => 'Sesuai Hasil Perbaikan di Luar Jam Hari-H',
                'edit_time' => null,
            ];
        }

        $editDateStr = $latestEditTime->format('Y-m-d');

        // 2. Sesuai Hasil Perbaikan di jam Input = +20 Poin
        // (Waktu perbaikan <= deadline jam input)
        if ($latestEditTime->lte($effectiveDeadline)) {
            return [
                'status' => 'sesuai_perbaikan_jam_input',
                'poin' => 20,
                'tahanan_penghuni' => $pTotal,
                'tahanan_overstaying' => $oTotal,
                'selisih' => 0,
                'is_perbaikan' => true,
                'keterangan' => 'Sesuai Perbaikan di Jam Input',
                'edit_time' => $latestEditTime->format('H:i'),
            ];
        }

        // 3. Sesuai Hasil Perbaikan diluar jam input dihari yang sama = +10 Poin
        // (Waktu perbaikan > deadline, namun masih pada tanggal laporan yang sama s/d 23:59:59)
        if ($editDateStr === $targetDateStr) {
            return [
                'status' => 'sesuai_perbaikan_luar_jam',
                'poin' => 10,
                'tahanan_penghuni' => $pTotal,
                'tahanan_overstaying' => $oTotal,
                'selisih' => 0,
                'is_perbaikan' => true,
                'keterangan' => 'Sesuai Perbaikan di Luar Jam Hari-H',
                'edit_time' => $latestEditTime->format('H:i'),
            ];
        }

        // 4. Masih Selisih / Kosong / diperbaiki lewat hari = 0 Poin
        // (Waktu perbaikan setelah hari-H lewat / tanggal edit > target tanggal laporan)
        return [
            'status' => 'sesuai_perbaikan_lewat_hari',
            'poin' => 0,
            'tahanan_penghuni' => $pTotal,
            'tahanan_overstaying' => $oTotal,
            'selisih' => 0,
            'is_perbaikan' => true,
            'keterangan' => 'Diperbaiki Lewat Hari',
            'edit_time' => $latestEditTime->format('d/m H:i'),
        ];
    }
}
