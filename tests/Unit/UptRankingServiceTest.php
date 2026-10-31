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
        $this->assertEquals('silver', $result['medal']);
        $this->assertStringContainsString('08:30 WIB', $result['keterangan']);
    }

    public function test_evaluate_module_golden_hour_returns_twenty_points_and_gold_medal(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $data = ['created_at' => '2026-10-15 07:15:00'];
        $result = UptRankingService::evaluateModule($data, $deadline, '2026-10-15');

        $this->assertEquals('tepat', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertEquals('gold', $result['medal']);
        $this->assertEquals('golden_hour', $result['timing_type']);
        $this->assertStringContainsString('07:15 WIB', $result['keterangan']);
        $this->assertStringContainsString('Pagi Prima', $result['keterangan']);
    }

    public function test_evaluate_module_pre_input_h_minus_1_returns_twenty_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $data = ['created_at' => '2026-10-14 17:00:00'];
        $result = UptRankingService::evaluateModule($data, $deadline, '2026-10-15');

        $this->assertEquals('tepat', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertNull($result['medal']);
        $this->assertStringContainsString('H-1', $result['keterangan']);
    }

    public function test_evaluate_module_late_on_same_day_returns_ten_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        $data = ['created_at' => '2026-10-15 14:00:00'];
        $result = UptRankingService::evaluateModule($data, $deadline, '2026-10-15');

        $this->assertEquals('terlambat', $result['status']);
        $this->assertEquals(10, $result['poin']);
        $this->assertNull($result['medal']);
        $this->assertStringContainsString('14:00 WIB', $result['keterangan']);
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

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, true, $deadline, '2026-10-15');

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

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, true, $deadline, '2026-10-15');

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

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, true, $deadline, '2026-10-15');

        $this->assertEquals('sesuai_perbaikan_lewat_hari', $result['status']);
        $this->assertEquals(0, $result['poin']);
        $this->assertTrue($result['is_perbaikan']);
    }

    public function test_evaluate_kroscek_synced_with_penghuni_updated_at_without_edit_log_returns_twenty_points(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');
        // Kasus ghost edit: updated_at berubah karena update data narapidana, namun tidak ada log edit tahanan (hasEditLog = false)
        $penghuni = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:00:00',
            'updated_at' => '2026-10-20 14:00:00', // Tanggal update jauh di masa depan
        ];
        $overstaying = [
            'total_tahanan' => 150,
            'created_at' => '2026-10-15 08:15:00',
            'updated_at' => '2026-10-15 08:15:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying, false, $deadline, '2026-10-15');

        $this->assertEquals('sesuai', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertFalse($result['is_perbaikan']);
        $this->assertEquals('Sesuai Langsung', $result['keterangan']);
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

    public function test_evaluate_kroscek_with_zero_tahanan_on_both_sides_defaults_to_sesuai_langsung(): void
    {
        $penghuni = [
            'total_tahanan' => 0,
            'created_at' => '2026-08-31 14:15:00',
            'updated_at' => '2026-08-31 14:15:00',
        ];
        $overstaying = [
            'total_tahanan' => 0,
            'created_at' => '2026-08-31 14:16:00',
            'updated_at' => '2026-08-31 14:16:00',
        ];

        $result = UptRankingService::evaluateKroscek($penghuni, $overstaying);

        $this->assertEquals('sesuai', $result['status']);
        $this->assertEquals(20, $result['poin']);
        $this->assertFalse($result['is_perbaikan']);
    }

    public function test_evaluate_module_returns_correct_timing_types(): void
    {
        $deadline = Carbon::parse('2026-10-15 11:00:00');

        // Hari-H tepat
        $resHariH = UptRankingService::evaluateModule(['created_at' => '2026-10-15 08:30:00'], $deadline, '2026-10-15');
        $this->assertEquals('hari_h_tepat', $resHariH['timing_type']);
        $this->assertTrue($resHariH['is_hari_h']);

        // H-1 pre-input
        $resH1 = UptRankingService::evaluateModule(['created_at' => '2026-10-14 18:00:00'], $deadline, '2026-10-15');
        $this->assertEquals('h_minus_1', $resH1['timing_type']);
        $this->assertFalse($resH1['is_hari_h']);

        // Hari-H telat
        $resTelatHariH = UptRankingService::evaluateModule(['created_at' => '2026-10-15 13:00:00'], $deadline, '2026-10-15');
        $this->assertEquals('hari_h_telat', $resTelatHariH['timing_type']);
        $this->assertTrue($resTelatHariH['is_hari_h']);

        // Susulan lewat hari
        $resSusulan = UptRankingService::evaluateModule(['created_at' => '2026-10-16 09:00:00'], $deadline, '2026-10-15');
        $this->assertEquals('susulan', $resSusulan['timing_type']);
        $this->assertFalse($resSusulan['is_hari_h']);
    }

    public function test_tie_breaker_prioritizes_hari_h_tepat_over_h_minus_1_when_points_are_equal(): void
    {
        $rankings = [
            [
                'nama_upt' => 'Lapas B (Banyak H-1)',
                'total_points' => 2000,
                'count_hari_sempurna' => 20,
                'count_kroscek_sesuai' => 20,
                'count_input_hari_h_tepat' => 60,
                'count_input_h_minus_1' => 20,
                'count_input_hari_h_telat' => 0,
                'count_input_susulan' => 0,
                'avg_minutes_hari_h' => 510,
            ],
            [
                'nama_upt' => 'Lapas A (Konsisten Hari-H)',
                'total_points' => 2000,
                'count_hari_sempurna' => 20,
                'count_kroscek_sesuai' => 20,
                'count_input_hari_h_tepat' => 80,
                'count_input_h_minus_1' => 0,
                'count_input_hari_h_telat' => 0,
                'count_input_susulan' => 0,
                'avg_minutes_hari_h' => 510,
            ],
        ];

        UptRankingService::sortRankings($rankings);

        $this->assertEquals('Lapas A (Konsisten Hari-H)', $rankings[0]['nama_upt']);
        $this->assertEquals('Lapas B (Banyak H-1)', $rankings[1]['nama_upt']);
    }

    public function test_tie_breaker_prioritizes_earlier_average_time_on_hari_h(): void
    {
        $rankings = [
            [
                'nama_upt' => 'Lapas Siang (10:30 WIB)',
                'total_points' => 2000,
                'count_hari_sempurna' => 20,
                'count_kroscek_sesuai' => 20,
                'count_input_hari_h_tepat' => 80,
                'count_input_h_minus_1' => 0,
                'count_input_hari_h_telat' => 0,
                'count_input_susulan' => 0,
                'avg_minutes_hari_h' => 630, // 10:30 WIB
            ],
            [
                'nama_upt' => 'Lapas Pagi (08:15 WIB)',
                'total_points' => 2000,
                'count_hari_sempurna' => 20,
                'count_kroscek_sesuai' => 20,
                'count_input_hari_h_tepat' => 80,
                'count_input_h_minus_1' => 0,
                'count_input_hari_h_telat' => 0,
                'count_input_susulan' => 0,
                'avg_minutes_hari_h' => 495, // 08:15 WIB
            ],
        ];

        UptRankingService::sortRankings($rankings);

        $this->assertEquals('Lapas Pagi (08:15 WIB)', $rankings[0]['nama_upt']);
        $this->assertEquals('Lapas Siang (10:30 WIB)', $rankings[1]['nama_upt']);
    }

    public function test_tie_breaker_prioritizes_h_minus_1_over_late_inputs_when_points_equal(): void
    {
        $rankings = [
            [
                'nama_upt' => 'Lapas Telat (Ada Keterlambatan)',
                'total_points' => 1500,
                'count_hari_sempurna' => 15,
                'count_kroscek_sesuai' => 15,
                'count_input_hari_h_tepat' => 50,
                'count_input_h_minus_1' => 0,
                'count_input_hari_h_telat' => 10,
                'count_input_susulan' => 0,
                'avg_minutes_hari_h' => 510,
            ],
            [
                'nama_upt' => 'Lapas Pre-Input (Input H-1 Lebih Awal)',
                'total_points' => 1500,
                'count_hari_sempurna' => 15,
                'count_kroscek_sesuai' => 15,
                'count_input_hari_h_tepat' => 50,
                'count_input_h_minus_1' => 10,
                'count_input_hari_h_telat' => 0,
                'count_input_susulan' => 0,
                'avg_minutes_hari_h' => 510,
            ],
        ];

        UptRankingService::sortRankings($rankings);

        $this->assertEquals('Lapas Pre-Input (Input H-1 Lebih Awal)', $rankings[0]['nama_upt']);
        $this->assertEquals('Lapas Telat (Ada Keterlambatan)', $rankings[1]['nama_upt']);
    }

    public function test_tie_breaker_prioritizes_gold_medals_when_points_are_equal(): void
    {
        $rankings = [
            [
                'nama_upt' => 'Lapas Perak (Banyak Jam Kerja)',
                'total_points' => 2000,
                'count_gold' => 10,
                'count_silver' => 70,
                'count_hari_sempurna' => 20,
                'count_kroscek_sesuai' => 20,
            ],
            [
                'nama_upt' => 'Lapas Emas (Rajin Pagi < 08.00 WIB)',
                'total_points' => 2000,
                'count_gold' => 60,
                'count_silver' => 20,
                'count_hari_sempurna' => 20,
                'count_kroscek_sesuai' => 20,
            ],
            [
                'nama_upt' => 'Lapas H-1 (Tanpa Medali)',
                'total_points' => 2000,
                'count_gold' => 0,
                'count_silver' => 0,
                'count_hari_sempurna' => 20,
                'count_kroscek_sesuai' => 20,
                'count_input_h_minus_1' => 80,
            ],
        ];

        UptRankingService::sortRankings($rankings);

        $this->assertEquals('Lapas Emas (Rajin Pagi < 08.00 WIB)', $rankings[0]['nama_upt']);
        $this->assertEquals('Lapas Perak (Banyak Jam Kerja)', $rankings[1]['nama_upt']);
        $this->assertEquals('Lapas H-1 (Tanpa Medali)', $rankings[2]['nama_upt']);
    }
}
