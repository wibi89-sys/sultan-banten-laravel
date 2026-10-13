<?php

namespace Tests\Feature;

use App\Models\DataPenghuni;
use App\Models\User;
use App\Models\Upt;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_cron_endpoint_fails_with_invalid_secret_key(): void
    {
        $response = $this->get('/cron-wa?key=wrong_key');
        $response->assertStatus(403);
        $response->assertJson(['success' => false, 'message' => 'Invalid secret key']);
    }

    public function test_api_stats_fails_with_invalid_key(): void
    {
        $response = $this->get('/api/stats?api_key=wrong_key');
        $response->assertStatus(403);
    }

    public function test_api_stats_succeeds_with_valid_key(): void
    {
        $key = env('API_SECRET_KEY', 'ditjenpas_banten_secret_key_2026');
        $response = $this->get("/api/stats?api_key={$key}&action=list_upt");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_operator_cannot_update_penghuni_of_different_upt(): void
    {
        $user = User::where('role', 'operator')->whereNotNull('upt_id')->first();
        if (!$user) {
            $this->markTestSkipped('No operator with upt_id found for test');
        }

        $otherUpt = Upt::where('id', '!=', $user->upt_id)->first();
        if (!$otherUpt) {
            $this->markTestSkipped('No secondary UPT found');
        }

        // Find or create record belonging to other UPT
        $record = DataPenghuni::where('upt_id', $otherUpt->id)->first();
        if (!$record) {
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
        if (!$user) {
            $this->markTestSkipped('No admin found');
        }

        $response = $this->actingAs($user)->get('/laporan/excel?type=rekapitulasi');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel');
    }
}
