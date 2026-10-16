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

    public function test_hitung_maju_1_tahun(): void
    {
        // 2024 adalah tahun kabisat (366 hari)
        $result = $this->service->hitungMaju('2024-01-01', 1, 0, 0);

        $this->assertEquals('2024-12-31', $result['tgl_ekspirasi']);
        $this->assertEquals(360, $result['total_hari_vonis']);
        $this->assertEquals('2024-04-30', $result['tgl_1_3']); // +120 hari
        $this->assertEquals('2024-06-29', $result['tgl_1_2']); // +180 hari
        $this->assertEquals('2024-08-28', $result['tgl_2_3']); // +240 hari
        $this->assertEquals('1 Tahun', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_dengan_bulan_dan_hari(): void
    {
        // Tgl awal 1 Maret 2026, vonis 2 bulan 10 hari (70 hari)
        $result = $this->service->hitungMaju('2026-03-01', 0, 2, 10);

        $this->assertEquals(70, $result['total_hari_vonis']);
        $this->assertEquals('2 Bulan, 10 Hari', $result['lama_pidana_teks']);
        // 1 Maret 2026 + 70 hari - 1 hari = 1 Maret + 69 hari
        // Maret 31 hari, April 30 hari. 1 Mar + 30 = 31 Mar. + 30 = 30 Apr (60 hari). + 9 = 9 Mei 2026.
        $this->assertEquals('2026-05-09', $result['tgl_ekspirasi']);
    }

    public function test_hitung_maju_fails_when_all_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungMaju('2026-01-01', 0, 0, 0);
    }

    public function test_hitung_mundur_1_tahun(): void
    {
        $result = $this->service->hitungMundur('2024-01-01', '2024-12-31');

        $this->assertEquals(1, $result['tahun']);
        $this->assertEquals(0, $result['bulan']);
        $this->assertEquals(0, $result['hari']);
        $this->assertEquals('1 Tahun', $result['lama_pidana_teks']);
        $this->assertEquals('2024-12-31', $result['tgl_ekspirasi']);
    }

    public function test_hitung_mundur_fails_when_ekspirasi_before_awal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungMundur('2026-05-10', '2026-05-01');
    }
}
