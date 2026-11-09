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
                'akses_utilitas' => 'full',
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
        $response->assertSee('Kalkulator Telram');
        $response->assertSee('Perkiraan Remisi');
        $response->assertSee('Unduh File Offline');
    }

    public function test_user_without_utilitas_permission_gets_forbidden_403(): void
    {
        $restrictedUser = User::create([
            'username' => 'restricted_telram_'.uniqid(),
            'password' => 'password123',
            'nama_lengkap' => 'Restricted User',
            'role' => 'operator',
            'akses_utilitas' => 'none',
        ]);

        try {
            $response = $this->actingAs($restrictedUser)->get('/telram');
            $response->assertStatus(403);

            $calcResponse = $this->actingAs($restrictedUser)->postJson('/telram/calculate', [
                'mode' => 'maju',
                'tgl_awal' => '2026-01-01',
            ]);
            $calcResponse->assertStatus(403);
        } finally {
            $restrictedUser->delete();
        }
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

    public function test_calculate_mode_simulasi_remisi_returns_valid_json(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->postJson('/telram/calculate', [
            'mode' => 'simulasi_remisi',
            'tgl_awal' => '2025-01-01',
            'tahun' => 2,
            'bulan' => 6,
            'hari' => 0,
            'agama' => 'islam',
            'remisi_awal_hari' => 15,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'mode' => 'simulasi_remisi',
                'jenis_program' => 'PB',
                'agama' => 'Islam',
                'remisi_awal_hari' => 15,
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'milestones' => [
                    'ekspirasi_murni',
                    'tgl_1_3_murni',
                    'tgl_1_2_murni',
                    'tgl_2_3_murni',
                    'tgl_1_3_definitif',
                    'tgl_1_2_definitif',
                    'tgl_2_3_definitif',
                    'tgl_mulai_pengusulan',
                    'ekspirasi_definitif',
                ],
                'status_pengusulan' => [
                    'kode',
                    'label',
                    'pesan',
                ],
                'proyeksi_ledger',
            ],
        ]);
    }

    public function test_calculate_mode_simulasi_remisi_with_wbp_identity_and_options(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->postJson('/telram/calculate', [
            'mode' => 'simulasi_remisi',
            'tgl_awal' => '2024-01-01',
            'tahun' => 3,
            'bulan' => 0,
            'hari' => 0,
            'agama' => 'islam',
            'nama_wbp' => 'Budi Santoso',
            'no_register' => 'BI.123/2024',
            'perkara_pasal' => 'Pasal 363 KUHP',
            'include_dasawarsa' => true,
            'kategori_tambahan' => 'pemuka',
            'tahun_mulai_tambahan' => 2,
            'target_basis' => '2_3',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'nama_wbp' => 'Budi Santoso',
                'no_register' => 'BI.123/2024',
                'perkara_pasal' => 'Pasal 363 KUHP',
                'include_dasawarsa' => true,
                'kategori_tambahan' => 'pemuka',
                'tahun_mulai_tambahan' => 2,
                'target_basis' => '2_3',
            ],
        ]);
    }

    public function test_export_pdf_returns_valid_pdf_response(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->post('/telram/export-pdf', [
            'tgl_awal' => '2024-01-01',
            'tahun' => 3,
            'bulan' => 0,
            'hari' => 0,
            'agama' => 'islam',
            'nama_wbp' => 'Narapidana Uji',
            'no_register' => 'REG.001/2024',
            'perkara_pasal' => 'Pasal 363 KUHP',
            'include_dasawarsa' => '1',
            'kategori_tambahan' => 'pemuka',
            'tahun_mulai_tambahan' => '2',
            'target_basis' => '2_3',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_export_pdf_fails_if_vonis_empty(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->post('/telram/export-pdf', [
            'tgl_awal' => '2024-01-01',
            'tahun' => 0,
            'bulan' => 0,
            'hari' => 0,
            'agama' => 'islam',
        ]);

        $response->assertStatus(422);
    }
}
