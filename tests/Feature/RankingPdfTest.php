<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RankingPdfTest extends TestCase
{
    public function test_ranking_pdf_requires_authentication(): void
    {
        $response = $this->get(route('activity-log.ranking.pdf'));
        $response->assertRedirect(route('login'));
    }

    public function test_certificate_pdf_requires_authentication(): void
    {
        $response = $this->get(route('activity-log.ranking.sertifikat'));
        $response->assertRedirect(route('login'));
    }

    public function test_ranking_pdf_accessible_by_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking.pdf', [
            'tanggal_mulai' => '2026-10-01',
            'tanggal_akhir' => '2026-10-15',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Laporan_Penetapan_Peringkat_Kedisiplinan_UPT', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_ranking_certificate_pdf_single_download(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking.sertifikat', [
            'tanggal_mulai' => '2026-10-01',
            'tanggal_akhir' => '2026-10-15',
            'rank' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Piagam_Penghargaan_Juara_1', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_ranking_certificate_pdf_bundle_download(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking.sertifikat', [
            'tanggal_mulai' => '2026-10-01',
            'tanggal_akhir' => '2026-10-15',
            'bundle' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Paket_Piagam_Penghargaan_Top3_UPT', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_ranking_view_contains_pdf_and_certificate_buttons(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin)->get(route('activity-log.ranking'));

        $response->assertStatus(200);
        $response->assertSee('Cetak Laporan PDF');
        $response->assertSee('Paket Piagam (3 Besar)');
        $response->assertSee('activity-log/ranking/pdf');
        $response->assertSee('activity-log/ranking/sertifikat');
    }
}
