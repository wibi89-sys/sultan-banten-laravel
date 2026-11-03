<?php

namespace Tests\Unit;

use Tests\TestCase;

class OvercrowdingClassificationTest extends TestCase
{
    /**
     * Test kategori Tidak Overcrowding (<= 100%)
     */
    public function test_klasifikasi_tidak_overcrowding_ketika_hunian_kurang_atau_sama_dengan_kapasitas(): void
    {
        // 50% hunian
        $status50 = getOvercrowdingStatus(50, 100);
        $this->assertSame(50.0, $status50['tingkat_hunian']);
        $this->assertSame(0.0, $status50['persen_over']);
        $this->assertSame(-50, $status50['selisih']);
        $this->assertSame('Tidak Overcrowding', $status50['status']);
        $this->assertSame('normal', $status50['level']);
        $this->assertSame('badge-overcrowding-normal', $status50['badge_class']);
        $this->assertSame('#10b981', $status50['badge_color']);
        $this->assertSame('fas fa-check-circle', $status50['icon']);

        // Tepat 100% hunian
        $status100 = getOvercrowdingStatus(100, 100);
        $this->assertSame(100.0, $status100['tingkat_hunian']);
        $this->assertSame(0.0, $status100['persen_over']);
        $this->assertSame(0, $status100['selisih']);
        $this->assertSame('Tidak Overcrowding', $status100['status']);
        $this->assertSame('normal', $status100['level']);
    }

    /**
     * Test kategori Overcrowding (100.1% - 150%)
     */
    public function test_klasifikasi_overcrowding_ketika_hunian_antara_100_koma_1_hingga_150_persen(): void
    {
        // 120% hunian
        $status120 = getOvercrowdingStatus(120, 100);
        $this->assertSame(120.0, $status120['tingkat_hunian']);
        $this->assertSame(20.0, $status120['persen_over']);
        $this->assertSame(20, $status120['selisih']);
        $this->assertSame('Overcrowding', $status120['status']);
        $this->assertSame('overcrowding', $status120['level']);
        $this->assertSame('badge-overcrowding-warning', $status120['badge_class']);
        $this->assertSame('#f59e0b', $status120['badge_color']);
        $this->assertSame('fas fa-exclamation-circle', $status120['icon']);

        // Tepat 150% batas atas
        $status150 = getOvercrowdingStatus(150, 100);
        $this->assertSame(150.0, $status150['tingkat_hunian']);
        $this->assertSame('Overcrowding', $status150['status']);
        $this->assertSame('overcrowding', $status150['level']);
    }

    /**
     * Test kategori Critical Overcrowding (150.1% - 299.9%)
     */
    public function test_klasifikasi_critical_overcrowding_ketika_hunian_antara_150_koma_1_hingga_299_koma_9_persen(): void
    {
        // 151% hunian
        $status151 = getOvercrowdingStatus(151, 100);
        $this->assertSame(151.0, $status151['tingkat_hunian']);
        $this->assertSame(51.0, $status151['persen_over']);
        $this->assertSame(51, $status151['selisih']);
        $this->assertSame('Critical Overcrowding', $status151['status']);
        $this->assertSame('critical', $status151['level']);
        $this->assertSame('badge-overcrowding-critical', $status151['badge_class']);
        $this->assertSame('#ea580c', $status151['badge_color']);
        $this->assertSame('fas fa-exclamation-triangle', $status151['icon']);

        // 252% hunian (Kasus Rutan Pandeglang)
        $statusPandeglang = getOvercrowdingStatus(252, 100);
        $this->assertSame('Critical Overcrowding', $statusPandeglang['status']);
        $this->assertSame('critical', $statusPandeglang['level']);

        // 299.9% hunian
        $status299 = getOvercrowdingStatus(2999, 1000);
        $this->assertSame(299.9, $status299['tingkat_hunian']);
        $this->assertSame('Critical Overcrowding', $status299['status']);
    }

    /**
     * Test kategori Extreme Overcrowding (>= 300%)
     */
    public function test_klasifikasi_extreme_overcrowding_ketika_hunian_sama_atau_melebihi_300_persen(): void
    {
        // Tepat 300% batas extreme
        $status300 = getOvercrowdingStatus(300, 100);
        $this->assertSame(300.0, $status300['tingkat_hunian']);
        $this->assertSame(200.0, $status300['persen_over']);
        $this->assertSame(200, $status300['selisih']);
        $this->assertSame('Extreme Overcrowding', $status300['status']);
        $this->assertSame('extreme', $status300['level']);
        $this->assertSame('badge-overcrowding-extreme', $status300['badge_class']);
        $this->assertSame('#991b1b', $status300['badge_color']);
        $this->assertSame('fas fa-fire-alt', $status300['icon']);

        // 317% hunian (Kasus Empiris Lapas Rangkasbitung: Kapasitas 100, Isi 317)
        $statusRangkas = getOvercrowdingStatus(317, 100);
        $this->assertSame(317.0, $statusRangkas['tingkat_hunian']);
        $this->assertSame(217.0, $statusRangkas['persen_over']);
        $this->assertSame('Extreme Overcrowding', $statusRangkas['status']);
        $this->assertSame('extreme', $statusRangkas['level']);
    }

    /**
     * Test edge case kapasitas nol atau belum diatur
     */
    public function test_penanganan_kapasitas_nol_atau_belum_diatur(): void
    {
        $status0 = getOvercrowdingStatus(100, 0);
        $this->assertSame(0.0, $status0['tingkat_hunian']);
        $this->assertSame(0.0, $status0['persen_over']);
        $this->assertSame(100, $status0['selisih']);
        $this->assertSame('Belum Diatur', $status0['status']);
        $this->assertSame('unknown', $status0['level']);
        $this->assertSame('bg-secondary text-white', $status0['badge_class']);
        $this->assertSame('fas fa-question-circle', $status0['icon']);
    }

    /**
     * Test helper renderOvercrowdingBadge
     */
    public function test_render_overcrowding_badge_html_output(): void
    {
        // Badge dengan persentase
        $badgeExtreme = renderOvercrowdingBadge(317, 100, true);
        $this->assertStringContainsString('badge-overcrowding-extreme', $badgeExtreme);
        $this->assertStringContainsString('Extreme Overcrowding', $badgeExtreme);
        $this->assertStringContainsString('317,0%', $badgeExtreme);
        $this->assertStringContainsString('fa-fire-alt', $badgeExtreme);

        // Badge tanpa persentase
        $badgeNormal = renderOvercrowdingBadge(80, 100, false);
        $this->assertStringContainsString('badge-overcrowding-normal', $badgeNormal);
        $this->assertStringContainsString('Tidak Overcrowding', $badgeNormal);
        $this->assertStringNotContainsString('(80,0%)', $badgeNormal);
    }
}
