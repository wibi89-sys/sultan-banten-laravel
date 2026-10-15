<?php

namespace Tests\Unit;

use App\Services\UptRankingService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class UptRankingServiceTest extends TestCase
{
    public function test_evaluate_module_empty_data_returns_zero_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $result = UptRankingService::evaluateModule(null, $deadline, '2026-10-15');

        $this->assertEquals('kosong', $result['status']);
        $this->assertEquals(0, $result['poin']);
        $this->assertEquals('Belum Input', $result['keterangan']);
    }

    public function test_evaluate_module_on_time_returns_twenty_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $data = ['created_at' => '2026-10-15 08:30:00'];
        $result = UptRankingService::evaluateModule($data, $deadline, '2026-10-15');

        $this->assertEquals('tepat', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertEquals('08:30 WIB', $result['keterangan']);
    }

    public function test_evaluate_module_pre_input_h_minus_1_returns_twenty_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $data = ['created_at' => '2026-10-14 17:00:00'];
        $result = UptRankingService::evaluateModule($data, $deadline, '2026-10-15');

        $this->assertEquals('tepat', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertStringContainsString('H-1', $result['keterangan']);
    }

    public function test_evaluate_module_late_on_same_day_returns_ten_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $data = ['created_at' => '2026-10-15 14:00:00'];
        $result = UptRankingService::evaluateModule($data, $deadline, '2026-10-15');

        $this->assertEquals('terlambat', $result['status']);
        $this->assertEquals(10, $result['poin']);
        $this->assertEquals('14:00 WIB', $result['keterangan']);
    }

    public function test_evaluate_module_late_past_day_returns_five_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $data = ['created_at' => '2026-10-16 09:00:00'];
        $result = UptRankingService::evaluateModule($data, $deadline, '2026-10-15');

        $this->assertEquals('terlambat', $result['status']);
        $this->assertEquals(5, $result['poin']);
        $this->assertStringContainsString('Susulan H+1', $result['keterangan']);
    }

    public function test_evaluate_kroscek_empty_data_returns_zero_points(): void
    {
        $result = UptRankingService::evaluateKroscek(null, null);

        $this->assertEquals('kosong', $result['status']);
        $this->assertEquals(0, $result['poin']);
        $this->assertFalse($result['is_perbaikan']);
    }

    public function test_evaluate_kroscek_synced_without_edit_returns_twenty_points(): void
    {
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-15 08:00:00',
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying);

        $this->assertEquals('sesuai', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertFalse($result['is_perbaikan']);
    }

    public function test_evaluate_kroscek_synced_after_penghuni_edit_within_input_hours_returns_twenty_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-15 10:30:00', // Diedit pukul 10:30 (sebelum deadline 11:00)
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, false, $deadline, '2026-10-15');

        $this->assertEquals('sesuai_perbaikan_jam_input', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertTrue($result['is_perbaikan']);
        $this->assertEquals('10:30', $result['edit_time']);
    }

    public function test_evaluate_kroscek_synced_after_edit_outside_input_hours_same_day_returns_ten_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-15 14:30:00', // Diedit pukul 14:30 (melewati deadline 11:00 tetapi masih hari-H)
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, false, $deadline, '2026-10-15');

        $this->assertEquals('sesuai_perbaikan_luar_jam', $result['status']);
        $this->assertEquals(10, $result['poin']);
        $this->assertTrue($result['is_perbaikan']);
        $this->assertEquals('14:30', $result['edit_time']);
    }

    public function test_evaluate_kroscek_synced_after_edit_past_day_returns_zero_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-16 09:00:00', // Diedit keesokan harinya (H+1)
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, false, $deadline, '2026-10-15');

        $this->assertEquals('sesuai_perbaikan_lewat_hari', $result['status']);
        $this->assertEquals(0, $result['poin']);
        $this->assertTrue($result['is_perbaikan']);
    }

    public function test_evaluate_kroscek_synced_with_edit_log_time(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-15 08:00:00',
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        // Edit log di jam input (10:00)
        $logTimeInHour = Carbon::parse('2026-10-15 10:00:00');
        $resInHour = UptRankingService::evaluateKroscek($penghuni, $overstaying, true, $deadline, '2026-10-15', $logTimeInHour);
        $this->assertEquals('sesuai_perbaikan_jam_input', $resInHour['status']);
        $this->assertEquals(20, $resInHour['poin']);

        // Edit log di luar jam input hari-H (16:00)
        $logTimeOutside = Carbon::parse('2026-10-15 16:00:00');
        $resOutside = UptRankingService::evaluateKroscek($penghuni, $overstaying, true, $deadline, '2026-10-15', $logTimeOutside);
        $this->assertEquals('sesuai_perbaikan_luar_jam', $resOutside['status']);
        $this->assertEquals(10, $resOutside['poin']);

        // Edit log lewat hari (H+1)
        $logTimePast = Carbon::parse('2026-10-16 08:00:00');
        $resPast = UptRankingService::evaluateKroscek($penghuni, $overstaying, true, $deadline, '2026-10-15', $logTimePast);
        $this->assertEquals('sesuai_perbaikan_lewat_hari', $resPast['status']);
        $this->assertEquals(0, $resPast['poin']);
    }

    public function test_evaluate_kroscek_with_difference_returns_zero_points(): void
    {
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-15 08:00:00',
        ];
        $overstaying = [
            'total_tahanan' => 140, // Selisih 10
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying);

        $this->assertEquals('selisih', $result['status']);
        $this->assertEquals(0, $result['poin']);
        $this->assertEquals(10, $result['selisih']);
        $this->assertFalse($result['is_perbaikan']);
    }
}
