<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MutasiNarapidanaTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $operator;

    protected Upt $uptAsal;

    protected Upt $uptTujuan;

    protected function setUp(): void
    {
        parent::setUp();

        // Cari atau gunakan UPT yang ada
        $this->uptAsal = Upt::first() ?? Upt::create([
            'nama_upt' => 'Lapas Kelas IIA Cilegon Test',
            'kapasitas' => 500,
        ]);

        $this->uptTujuan = Upt::where('id', '!=', $this->uptAsal->id)->first() ?? Upt::create([
            'nama_upt' => 'Lapas Terbuka Ciangir Test',
            'kapasitas' => 200,
        ]);

        // Admin User
        $this->admin = User::where('role', 'admin')->first() ?? User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
            'akses_mutasi' => true,
        ]);

        // Operator User
        $this->operator = User::where('role', 'operator')->where('upt_id', $this->uptAsal->id)->first() ?? User::factory()->create([
            'role' => 'operator',
            'upt_id' => $this->uptAsal->id,
            'akses_mutasi' => true,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('mutasi.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_access_mutasi_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mutasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Mutasi Narapidana Antar-UPT Wilayah Banten');
    }

    public function test_authenticated_user_can_access_create_page(): void
    {
        $response = $this->actingAs($this->operator)->get(route('mutasi.create'));
        $response->assertStatus(200);
        $response->assertSee('Buat Usulan Mutasi Narapidana Antar-UPT');
        $response->assertSee('Pendataan Jumlah Narapidana per Klasifikasi Perkara');
    }

    public function test_rekapitulasi_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mutasi.rekapitulasi'));
        $response->assertStatus(200);
        $response->assertSee('Laporan Rekapitulasi Pemindahan Narapidana Antar-UPT');
        $response->assertSee('Matriks Arus Mutasi Narapidana Per UPT');
    }

    public function test_rekapitulasi_excel_export_streams_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mutasi.export-excel'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
