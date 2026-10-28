<?php

namespace Tests\Feature;

use App\Models\DataPenghuni;
use App\Models\Upt;
use App\Models\User;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_cron_endpoint_fails_with_invalid_secret_key(): void
    {
        $response = $this->get('/cron-wa?key=wrong_key');
        $response->assertStatus(403);
        $response->assertJson(['success' => false, 'message' => 'Invalid secret key']);
    }

    public function test_api_stats_endpoint_is_disabled_and_returns_404(): void
    {
        $response = $this->get('/api/stats');
        $response->assertStatus(404);
    }

    public function test_operator_cannot_update_penghuni_of_different_upt(): void
    {
        $user = User::where('role', 'operator')->whereNotNull('upt_id')->first();
        if (! $user) {
            $this->markTestSkipped('No operator with upt_id found for test');
        }

        $otherUpt = Upt::where('id', '!=', $user->upt_id)->first();
        if (! $otherUpt) {
            $this->markTestSkipped('No secondary UPT found');
        }

        // Find or create record belonging to other UPT
        $record = DataPenghuni::where('upt_id', $otherUpt->id)->first();
        if (! $record) {
            $record = DataPenghuni::create([
                'upt_id' => $otherUpt->id,
                'klasifikasi_pidana' => 'Pidana Umum',
                'tanggal' => '2026-01-01',
            ]);
        }

        $response = $this->actingAs($user)->put("/penghuni/{$record->id}", [
            'klasifikasi_pidana' => 'Pidana Umum',
            'tanggal' => '2026-01-01',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_laporan(): void
    {
        $response = $this->get('/laporan');
        $response->assertRedirect('/login');
    }

    public function test_laporan_excel_rekapitulasi_streams_successfully(): void
    {
        $user = User::where('role', 'admin')->first();
        if (! $user) {
            $this->markTestSkipped('No admin found');
        }

        $response = $this->actingAs($user)->get('/laporan/excel?type=rekapitulasi');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel');
    }

    public function test_activity_log_page_renders_successfully(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin found');
        }

        $response = $this->actingAs($admin)->get('/activity-log');
        $response->assertStatus(200);
        $response->assertSee('Activity Log');
        $response->assertSee('Distribusi Aktivitas Sistem');
        $response->assertSee('Top 5 Pengguna Paling Aktif');
    }
}
