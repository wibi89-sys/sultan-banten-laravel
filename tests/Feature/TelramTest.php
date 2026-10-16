<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class TelramTest extends TestCase
{
    private ?User $testUser = null;

    protected function tearDown(): void
    {
        if ($this->testUser) {
            $this->testUser->delete();
        }

        parent::tearDown();
    }

    private function getTestUser(): User
    {
        if (! $this->testUser) {
            $this->testUser = User::create([
                'username' => 'tester_telram_'.uniqid(),
                'password' => 'password123',
                'nama_lengkap' => 'Tester Telram',
                'role' => 'operator',
            ]);
        }

        return $this->testUser;
    }

    public function test_guest_is_redirected_to_login_when_accessing_telram(): void
    {
        $response = $this->get('/telram');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_telram_page(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->get('/telram');
        $response->assertStatus(200);
        $response->assertSee('Kalkulator Perkiraan Ekspirasi');
        $response->assertSee('HITUNG EKSPIRASI');
    }

    public function test_calculate_mode_maju_returns_valid_json(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->postJson('/telram/calculate', [
            'mode' => 'maju',
            'tgl_awal' => '2026-01-01',
            'tahun' => 1,
            'bulan' => 0,
            'hari' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'mode' => 'maju',
                'tgl_awal' => '2026-01-01',
                'tgl_ekspirasi' => '2027-01-01',
                'total_hari_vonis' => 360,
                'lama_pidana_teks' => '1 Tahun',
            ],
        ]);
    }

    public function test_calculate_mode_mundur_returns_valid_json(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->postJson('/telram/calculate', [
            'mode' => 'mundur',
            'tgl_awal' => '2026-01-01',
            'tgl_ekspirasi' => '2027-01-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'mode' => 'mundur',
                'tahun' => 1,
                'bulan' => 0,
                'hari' => 0,
                'lama_pidana_teks' => '1 Tahun',
            ],
        ]);
    }
}
