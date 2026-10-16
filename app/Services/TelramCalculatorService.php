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

        // Rumus Baku Ditjen PAS:
        // 1 Bulan = 30 Hari, Tahun kalender aktual, dikurangi 1 hari
        $tglEkspirasi = $tglAwal->copy()
            ->addYears($tahun)
            ->addDays(($bulan * 30) + $hari)
            ->subDay();

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

        // Reverse Rule: batas akhir komputasi sebelum dikurangi 1 hari
        $tglEndCalc = $tglEkspirasi->copy()->addDay();

        $vThn = $tglEndCalc->year - $tglAwal->year;
        $tempDate = $tglAwal->copy()->addYears($vThn);

        if ($tempDate->gt($tglEndCalc)) {
            $vThn--;
            $tempDate = $tglAwal->copy()->addYears($vThn);
        }

        $diffDays = $tempDate->diffInDays($tglEndCalc);

        // Konversi sisa hari ke Bulan dan Hari (Aturan Baku: 1 Bulan = 30 Hari)
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
}
