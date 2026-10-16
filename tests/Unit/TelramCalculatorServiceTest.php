<?php

namespace Tests\Unit;

use App\Services\TelramCalculatorService;
use InvalidArgumentException;
use Tests\TestCase;

class TelramCalculatorServiceTest extends TestCase
{
    private TelramCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TelramCalculatorService;
    }

    public function test_hitung_maju_1_hari(): void
    {
        // 01/02/2026 Vonis 1 hari -> ekspirasi 02/02/2026
        $result = $this->service->hitungMaju('2026-02-01', 0, 0, 1);

        $this->assertEquals('2026-02-02', $result['tgl_ekspirasi']);
        $this->assertEquals(1, $result['total_hari_vonis']);
        $this->assertEquals('1 Hari', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_1_bulan(): void
    {
        // 01/01/2026 Vonis 1 bulan -> ekspirasi 31/01/2026
        $result = $this->service->hitungMaju('2026-01-01', 0, 1, 0);

        $this->assertEquals('2026-01-31', $result['tgl_ekspirasi']);
        $this->assertEquals(30, $result['total_hari_vonis']);
        $this->assertEquals('1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_1_tahun(): void
    {
        // 01/01/2026 Vonis 1 tahun -> ekspirasi 01/01/2027 (di tanggal & bulan yang sama)
        $result = $this->service->hitungMaju('2026-01-01', 1, 0, 0);

        $this->assertEquals('2027-01-01', $result['tgl_ekspirasi']);
        $this->assertEquals(360, $result['total_hari_vonis']);
        $this->assertEquals('1 Tahun', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_kombinasi_tahun_dan_bulan(): void
    {
        // 01/01/2026 Vonis 1 tahun 1 bulan -> 01/01/2027 geser 1 bulan (-1 hari) = 31/01/2027
        $result = $this->service->hitungMaju('2026-01-01', 1, 1, 0);

        $this->assertEquals('2027-01-31', $result['tgl_ekspirasi']);
        $this->assertEquals(390, $result['total_hari_vonis']);
        $this->assertEquals('1 Tahun, 1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_kombinasi_tahun_bulan_dan_hari(): void
    {
        // 01/01/2026 Vonis 1 tahun 1 bulan 5 hari -> 31/01/2027 + 5 hari = 05/02/2027
        $result = $this->service->hitungMaju('2026-01-01', 1, 1, 5);

        $this->assertEquals('2027-02-05', $result['tgl_ekspirasi']);
        $this->assertEquals(395, $result['total_hari_vonis']);
        $this->assertEquals('1 Tahun, 1 Bulan, 5 Hari', $result['lama_pidana_teks']);
    }

    public function test_kabisat_februari_2024_dan_2028(): void
    {
        // 2024 adalah tahun kabisat (Februari ada 29 hari)
        // 01/02/2024 vonis 1 bulan -> ekspirasi 29/02/2024
        $resKabisat = $this->service->hitungMaju('2024-02-01', 0, 1, 0);
        $this->assertEquals('2024-02-29', $resKabisat['tgl_ekspirasi']);

        // 2026 adalah tahun normal (Februari ada 28 hari)
        // 01/02/2026 vonis 1 bulan -> ekspirasi 28/02/2026
        $resNormal = $this->service->hitungMaju('2026-02-01', 0, 1, 0);
        $this->assertEquals('2026-02-28', $resNormal['tgl_ekspirasi']);

        // 2028 adalah tahun kabisat berikutnya
        // 01/02/2028 vonis 1 bulan -> ekspirasi 29/02/2028
        $resKabisat2028 = $this->service->hitungMaju('2028-02-01', 0, 1, 0);
        $this->assertEquals('2028-02-29', $resKabisat2028['tgl_ekspirasi']);
    }

    public function test_kabisat_ditahan_pada_29_februari(): void
    {
        // Ditahan pada 29 Februari 2024
        // Vonis 1 hari -> 01/03/2024
        $res1Hari = $this->service->hitungMaju('2024-02-29', 0, 0, 1);
        $this->assertEquals('2024-03-01', $res1Hari['tgl_ekspirasi']);

        // Vonis 1 tahun -> 2025 bukan kabisat, jatuh pada hari terakhir Februari (28/02/2025)
        $res1Thn = $this->service->hitungMaju('2024-02-29', 1, 0, 0);
        $this->assertEquals('2025-02-28', $res1Thn['tgl_ekspirasi']);

        // Vonis 4 tahun -> 2028 adalah kabisat, jatuh tepat pada 29/02/2028
        $res4Thn = $this->service->hitungMaju('2024-02-29', 4, 0, 0);
        $this->assertEquals('2028-02-29', $res4Thn['tgl_ekspirasi']);
    }

    public function test_kabisat_mode_mundur(): void
    {
        // Mundur dari 29/02/2024 ke 01/02/2024 -> 1 Bulan
        $res1 = $this->service->hitungMundur('2024-02-01', '2024-02-29');
        $this->assertEquals(1, $res1['bulan']);
        $this->assertEquals(0, $res1['hari']);

        // Mundur dari 28/02/2025 ke 29/02/2024 -> 1 Tahun
        $res2 = $this->service->hitungMundur('2024-02-29', '2025-02-28');
        $this->assertEquals(1, $res2['tahun']);
        $this->assertEquals(0, $res2['bulan']);
        $this->assertEquals(0, $res2['hari']);

        // Mundur dari 29/02/2028 ke 29/02/2024 -> 4 Tahun
        $res3 = $this->service->hitungMundur('2024-02-29', '2028-02-29');
        $this->assertEquals(4, $res3['tahun']);
        $this->assertEquals(0, $res3['bulan']);
        $this->assertEquals(0, $res3['hari']);
    }

    public function test_hitung_maju_fails_when_all_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungMaju('2026-01-01', 0, 0, 0);
    }

    public function test_hitung_mundur_1_hari(): void
    {
        $result = $this->service->hitungMundur('2026-02-01', '2026-02-02');

        $this->assertEquals(0, $result['tahun']);
        $this->assertEquals(0, $result['bulan']);
        $this->assertEquals(1, $result['hari']);
        $this->assertEquals('1 Hari', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_1_bulan(): void
    {
        $result = $this->service->hitungMundur('2026-01-01', '2026-01-31');

        $this->assertEquals(0, $result['tahun']);
        $this->assertEquals(1, $result['bulan']);
        $this->assertEquals(0, $result['hari']);
        $this->assertEquals('1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_1_tahun(): void
    {
        $result = $this->service->hitungMundur('2026-01-01', '2027-01-01');

        $this->assertEquals(1, $result['tahun']);
        $this->assertEquals(0, $result['bulan']);
        $this->assertEquals(0, $result['hari']);
        $this->assertEquals('1 Tahun', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_kombinasi(): void
    {
        $result = $this->service->hitungMundur('2026-01-01', '2027-02-05');

        $this->assertEquals(1, $result['tahun']);
        $this->assertEquals(1, $result['bulan']);
        $this->assertEquals(5, $result['hari']);
        $this->assertEquals('1 Tahun, 1 Bulan, 5 Hari', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_fails_when_ekspirasi_before_awal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungMundur('2026-05-10', '2026-05-01');
    }
}
