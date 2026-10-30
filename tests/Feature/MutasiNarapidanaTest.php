<?php

namespace Tests\Feature;

use App\Models\MutasiNarapidana;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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

    protected function tearDown(): void
    {
        $uploadDir = public_path('uploads/mutasi');
        if (File::exists($uploadDir)) {
            foreach (File::files($uploadDir) as $file) {
                if ($file->getFilename() !== '.gitkeep') {
                    File::delete($file->getRealPath());
                }
            }
        }

        parent::tearDown();
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
        $response->assertDontSee('Buat Usulan Baru');
    }

    public function test_operator_sees_buat_usulan_baru_button(): void
    {
        $response = $this->actingAs($this->operator)->get(route('mutasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Buat Usulan Baru');
    }

    public function test_kanwil_user_cannot_access_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mutasi.create'));
        $response->assertRedirect(route('mutasi.index'));
        $response->assertSessionHas('error');
    }

    public function test_kanwil_user_cannot_submit_usulan_mutasi(): void
    {
        $file = UploadedFile::fake()->create('surat_usulan.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('mutasi.store'), [
            'nomor_surat' => 'TEST/KANWIL/'.uniqid(),
            'tanggal_surat' => now()->toDateString(),
            'upt_asal_id' => $this->uptAsal->id,
            'kategori_mutasi' => 'Pembinaan',
            'jumlah_narkotika' => 1,
            'jumlah_pidum' => 0,
            'jumlah_korupsi' => 0,
            'file_surat_usulan' => $file,
        ]);

        $response->assertStatus(403);
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
            'akses_mutasi' => 'none',
        ]);

        $this->artisan('users:grant-mutasi', ['username' => $testUser->username])
            ->assertExitCode(0);

        $this->assertEquals('full', $testUser->fresh()->akses_mutasi);
        $this->assertTrue($testUser->fresh()->canManage('mutasi'));
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

    public function test_kanwil_can_approve_mutasi_to_multiple_upt_destinations(): void
    {
        $uptTujuan2 = Upt::whereNotIn('id', [$this->uptAsal->id, $this->uptTujuan->id])->first() ?? Upt::create([
            'nama_upt' => 'Lapas Pemuda Tangerang Test',
            'kapasitas' => 300,
        ]);

        $mutasi = MutasiNarapidana::create([
            'nomor_surat' => 'USUL/MULTI/'.uniqid(),
            'tanggal_surat' => now()->toDateString(),
            'upt_asal_id' => $this->uptAsal->id,
            'kategori_mutasi' => 'Overkapasitas',
            'jumlah_narkotika' => 30,
            'jumlah_pidum' => 20,
            'jumlah_korupsi' => 0,
            'total_usulan' => 50,
            'status' => 'diajukan',
            'created_by' => $this->operator->id,
        ]);

        $pdfKanwil = UploadedFile::fake()->create('persetujuan_kanwil.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('mutasi.verifikasi', $mutasi->id), [
            'status' => 'disetujui',
            'nomor_surat_kanwil' => 'W.12.PAS-PK.01.05-MULTI-101',
            'tanggal_surat_kanwil' => now()->toDateString(),
            'file_persetujuan_kanwil' => $pdfKanwil,
            'alokasi' => [
                [
                    'upt_tujuan_id' => $this->uptTujuan->id,
                    'jumlah_narkotika' => 20,
                    'jumlah_pidum' => 10,
                    'jumlah_korupsi' => 0,
                ],
                [
                    'upt_tujuan_id' => $uptTujuan2->id,
                    'jumlah_narkotika' => 10,
                    'jumlah_pidum' => 10,
                    'jumlah_korupsi' => 0,
                ],
            ],
            'catatan_kanwil' => 'Disetujui 30 orang ke UPT 1 dan 20 orang ke UPT 2',
        ]);

        $response->assertRedirect(route('mutasi.show', $mutasi->id));

        $mutasi->refresh();
        $this->assertEquals('disetujui', $mutasi->status);
        $this->assertEquals(50, $mutasi->total_disetujui);
        $this->assertCount(2, $mutasi->tujuans);

        $tujuan1 = $mutasi->tujuans->firstWhere('upt_tujuan_id', $this->uptTujuan->id);
        $tujuan2 = $mutasi->tujuans->firstWhere('upt_tujuan_id', $uptTujuan2->id);

        $this->assertNotNull($tujuan1);
        $this->assertEquals(30, $tujuan1->total_disetujui);
        $this->assertNotNull($tujuan2);
        $this->assertEquals(20, $tujuan2->total_disetujui);
    }

    public function test_multi_destination_execution_flow_completes_when_all_bast_uploaded(): void
    {
        $uptTujuan2 = Upt::whereNotIn('id', [$this->uptAsal->id, $this->uptTujuan->id])->first() ?? Upt::create([
            'nama_upt' => 'Lapas Pemuda Test 2',
            'kapasitas' => 300,
        ]);

        $mutasi = MutasiNarapidana::create([
            'nomor_surat' => 'USUL/FLOW/'.uniqid(),
            'tanggal_surat' => now()->toDateString(),
            'upt_asal_id' => $this->uptAsal->id,
            'kategori_mutasi' => 'Pembinaan',
            'jumlah_narkotika' => 30,
            'jumlah_pidum' => 20,
            'jumlah_korupsi' => 0,
            'total_usulan' => 50,
            'status' => 'diajukan',
            'created_by' => $this->operator->id,
        ]);

        $pdfKanwil = UploadedFile::fake()->create('persetujuan.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('mutasi.verifikasi', $mutasi->id), [
            'status' => 'disetujui',
            'nomor_surat_kanwil' => 'W.12.PAS-FLOW-99',
            'tanggal_surat_kanwil' => now()->toDateString(),
            'file_persetujuan_kanwil' => $pdfKanwil,
            'alokasi' => [
                [
                    'upt_tujuan_id' => $this->uptTujuan->id,
                    'jumlah_narkotika' => 20,
                    'jumlah_pidum' => 10,
                    'jumlah_korupsi' => 0,
                ],
                [
                    'upt_tujuan_id' => $uptTujuan2->id,
                    'jumlah_narkotika' => 10,
                    'jumlah_pidum' => 10,
                    'jumlah_korupsi' => 0,
                ],
            ],
        ]);

        $mutasi->refresh();
        $this->assertCount(2, $mutasi->tujuans);
        $tj1 = $mutasi->tujuans->firstWhere('upt_tujuan_id', $this->uptTujuan->id);
        $tj2 = $mutasi->tujuans->firstWhere('upt_tujuan_id', $uptTujuan2->id);

        // 1. Upload Laporan Pemindahan Rute 1
        $fileP1 = UploadedFile::fake()->create('pemindahan1.pdf', 100, 'application/pdf');
        $this->actingAs($this->operator)->post(route('mutasi.laporan-pemindahan.store', $mutasi->id), [
            'tujuan_id' => $tj1->id,
            'tanggal_pemindahan' => now()->toDateString(),
            'file_laporan_pemindahan' => $fileP1,
        ])->assertRedirect();

        // 2. Upload BAST Rute 1
        $fileB1 = UploadedFile::fake()->create('bast1.pdf', 100, 'application/pdf');
        $this->actingAs($this->admin)->post(route('mutasi.bast.store', $mutasi->id), [
            'tujuan_id' => $tj1->id,
            'nomor_bast' => 'BAST/01/'.uniqid(),
            'tanggal_bast' => now()->toDateString(),
            'file_bast' => $fileB1,
        ])->assertRedirect();

        $tj1->refresh();
        $mutasi->refresh();
        $this->assertEquals('selesai', $tj1->status);
        // Status induk belum selesai karena rute 2 masih aktif
        $this->assertEquals('disetujui', $mutasi->status);
        $this->assertStringContainsString('Sebagian Selesai', $mutasi->status_label);

        // 3. Upload BAST Rute 2
        $fileB2 = UploadedFile::fake()->create('bast2.pdf', 100, 'application/pdf');
        $this->actingAs($this->admin)->post(route('mutasi.bast.store', $mutasi->id), [
            'tujuan_id' => $tj2->id,
            'nomor_bast' => 'BAST/02/'.uniqid(),
            'tanggal_bast' => now()->toDateString(),
            'file_bast' => $fileB2,
        ])->assertRedirect();

        $tj2->refresh();
        $mutasi->refresh();
        $this->assertEquals('selesai', $tj2->status);
        // Sekarang seluruh rute selesai, sehingga status induk menjadi selesai
        $this->assertEquals('selesai', $mutasi->status);
        $this->assertEquals('Selesai (BAST Terbit)', $mutasi->status_label);
    }
}
