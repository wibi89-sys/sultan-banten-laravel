<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
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

    public function test_operator_without_mutasi_access_is_forbidden(): void
    {
        $restrictedOperator = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $this->uptAsal->id,
            'akses_mutasi' => false,
        ]);

        $response = $this->actingAs($restrictedOperator)->get(route('mutasi.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_grant_mutasi_access_to_user(): void
    {
        $operatorWithoutAccess = User::factory()->create([
            'role' => 'operator',
            'nama_lengkap' => 'Operator Test',
            'upt_id' => $this->uptAsal->id,
            'akses_mutasi' => false,
        ]);

        $response = $this->actingAs($this->admin)->put(route('users.update', $operatorWithoutAccess->id), [
            'nama_lengkap' => $operatorWithoutAccess->nama_lengkap,
            'role' => 'operator',
            'upt_id' => $this->uptAsal->id,
            'akses_mutasi' => 'on',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue((bool) $operatorWithoutAccess->fresh()->akses_mutasi);
    }

    public function test_grant_mutasi_artisan_command(): void
    {
        $testUser = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $this->uptAsal->id,
            'akses_mutasi' => false,
        ]);

        $this->artisan('users:grant-mutasi', ['username' => $testUser->username])
            ->assertExitCode(0);

        $this->assertTrue($testUser->fresh()->akses_mutasi);
    }

    public function test_kanwil_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mutasi.kanwil'));
        $response->assertStatus(200);
        $response->assertSee('Persetujuan &amp; Penetapan Kanwil', false);
    }

    public function test_pelaksanaan_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mutasi.pelaksanaan'));
        $response->assertStatus(200);
        $response->assertSee('Laporan Pemindahan &amp; BAST', false);
    }

    public function test_create_page_displays_clean_categories_and_manual_input_field(): void
    {
        $response = $this->actingAs($this->operator)->get(route('mutasi.create'));
        $response->assertStatus(200);
        $response->assertSee('<option value="Pembinaan"', false);
        $response->assertSee('Pembinaan</option>', false);
        $response->assertSee('Keamanan</option>', false);
        $response->assertSee('Overkapasitas</option>', false);
        $response->assertSee('Lainnya</option>', false);
        $response->assertDontSee('Pembinaan Lanjutan (Revitalisasi', false);
        $response->assertSee('id="divKategoriLainnya"', false);
        $response->assertSee('name="kategori_lainnya"', false);
    }

    public function test_user_can_submit_usulan_mutasi_with_custom_category(): void
    {
        $file = UploadedFile::fake()->create('surat_usulan.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->operator)->post(route('mutasi.store'), [
            'nomor_surat' => 'TEST/MUTASI/'.uniqid(),
            'tanggal_surat' => now()->toDateString(),
            'upt_asal_id' => $this->uptAsal->id,
            'kategori_mutasi' => 'Lainnya',
            'kategori_lainnya' => 'Rujukan Medis & Kesehatan RSUD',
            'keterangan' => 'Mutasi rujukan perawatan medis intensif',
            'jumlah_narkotika' => 1,
            'jumlah_pidum' => 0,
            'jumlah_korupsi' => 0,
            'file_surat_usulan' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mutasi_narapidana', [
            'kategori_mutasi' => 'Lainnya: Rujukan Medis & Kesehatan RSUD',
            'jumlah_narkotika' => 1,
            'total_usulan' => 1,
        ]);
    }

    public function test_submitting_kategori_lainnya_without_text_fails_validation(): void
    {
        $file = UploadedFile::fake()->create('surat_usulan.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->operator)->post(route('mutasi.store'), [
            'nomor_surat' => 'TEST/MUTASI/'.uniqid(),
            'tanggal_surat' => now()->toDateString(),
            'upt_asal_id' => $this->uptAsal->id,
            'kategori_mutasi' => 'Lainnya',
            'kategori_lainnya' => '',
            'jumlah_narkotika' => 1,
            'jumlah_pidum' => 0,
            'jumlah_korupsi' => 0,
            'file_surat_usulan' => $file,
        ]);

        $response->assertSessionHasErrors(['kategori_lainnya']);
    }
}
