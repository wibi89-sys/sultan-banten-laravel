<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PdfReportUptTest extends TestCase
{
    use DatabaseTransactions;

    public function test_laporan_page_displays_clean_upt_banner_for_upt_user(): void
    {
        $upt = Upt::first();
        $this->assertNotNull($upt, 'Database must have at least one UPT');

        $uptUser = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt->id,
            'akses_laporan' => 'view',
        ]);

        $response = $this->actingAs($uptUser)->get('/laporan?type=penghuni');

        $response->assertStatus(200);
        $response->assertSee('Format Cetak Laporan Satuan Kerja (UPT)');
        $response->assertDontSee('Tempat Penandatanganan');
    }

    public function test_laporan_page_displays_signature_settings_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/laporan?type=penghuni');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Dokumen Laporan (PDF)');
        $response->assertSee('Tempat Penandatanganan');
    }

    public function test_upt_user_can_generate_pdf_report(): void
    {
        $upt = Upt::first();
        $uptUser = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt->id,
            'akses_laporan' => 'view',
        ]);

        $response = $this->actingAs($uptUser)->get('/laporan/pdf?type=penghuni&tanggal_mulai='.date('Y-m-01').'&tanggal_akhir='.date('Y-m-d'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_kroscek_detail_pdf_generation(): void
    {
        $upt = Upt::first();
        $uptUser = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt->id,
        ]);

        $response = $this->actingAs($uptUser)->get('/kroscek/detail-pdf?sort=desc');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_kanwil_can_generate_laporan_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/laporan/pdf?type=penghuni&show_ttd=1&show_stempel=1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_kanwil_can_generate_kroscek_detail_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/kroscek/detail-pdf?sort=asc&show_ttd=1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
