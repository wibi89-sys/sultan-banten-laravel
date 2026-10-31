<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use App\Services\UptRankingService;
use Tests\TestCase;

class UptRankingTest extends TestCase
{
    public function test_activity_log_ranking_requires_authentication(): void
    {
        $response = $this->get(route('activity-log.ranking'));
        $response->assertRedirect(route('login'));
    }

    public function test_activity_log_ranking_accessible_by_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking'));
        $response->assertStatus(200);
        $response->assertSee('Leaderboard');
        $response->assertSee('Kepatuhan UPT');
    }

    public function test_upt_ranking_service_returns_valid_structure(): void
    {
        $result = UptRankingService::calculateRanking('2026-10-01', '2026-10-13');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('rankings', $result);
        $this->assertArrayHasKey('summary', $result);

        $this->assertGreaterThan(0, count($result['rankings']));
        $first = $result['rankings'][0];

        $this->assertArrayHasKey('rank', $first);
        $this->assertArrayHasKey('nama_upt', $first);
        $this->assertArrayHasKey('total_points', $first);
        $this->assertArrayHasKey('persentase_kepatuhan', $first);
        $this->assertArrayHasKey('daily_breakdown', $first);
        $this->assertEquals(1, $first['rank']);
    }

    public function test_activity_log_ranking_excel_export(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking.excel', [
            'tanggal_mulai' => '2026-10-01',
            'tanggal_akhir' => '2026-10-13',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
    }

    public function test_activity_log_ranking_detail_pdf_requires_authentication(): void
    {
        $response = $this->get(route('activity-log.ranking.detail-pdf'));
        $response->assertRedirect(route('login'));
    }

    public function test_activity_log_ranking_detail_pdf_accessible_by_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $upt = Upt::first();
        if (! $upt) {
            $this->markTestSkipped('No UPT found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking.detail-pdf', [
            'upt_id' => $upt->id,
            'tanggal_mulai' => '2026-10-01',
            'tanggal_akhir' => '2026-10-15',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Lampiran_Detail_Poin_Medali', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_activity_log_ranking_view_contains_detail_pdf_buttons(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking'));

        $response->assertStatus(200);
        $response->assertSee('activity-log/ranking/detail-pdf');
        $response->assertSee('PDF');
    }
}
