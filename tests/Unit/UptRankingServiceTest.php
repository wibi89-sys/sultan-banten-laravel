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

    public function test_evaluate_kroscek_synced_after_penghuni_edit_returns_ten_points(): void
    {
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-15 10:30:00', // Diedit 2.5 jam kemudian
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying);

        $this->assertEquals('sesuai_perbaikan', $result['status']);
        $this->assertEquals(10, $result['poin']);
        $this->assertTrue($result['is_perbaikan']);
    }

    public function test_evaluate_kroscek_synced_after_overstaying_edit_returns_ten_points(): void
    {
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-15 08:00:00',
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 11:00:00', // Diedit hampir 3 jam kemudian
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying);

        $this->assertEquals('sesuai_perbaikan', $result['status']);
        $this->assertEquals(10, $result['poin']);
        $this->assertTrue($result['is_perbaikan']);
    }

    public function test_evaluate_kroscek_synced_with_edit_log_returns_ten_points(): void
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

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, true);

        $this->assertEquals('sesuai_perbaikan', $result['status']);
        $this->assertEquals(10, $result['poin']);
        $this->assertTrue($result['is_perbaikan']);
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
