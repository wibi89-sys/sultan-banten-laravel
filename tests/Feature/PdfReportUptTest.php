<?php

namespace Tests\Feature;

use App\Http\Controllers\KroscekController;
use App\Models\Upt;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_upt_user_can_generate_bebas_harian_pdf_report(): void
    {
        $upt = Upt::first();
        $uptUser = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt->id,
            'akses_laporan' => 'view',
        ]);

        $response = $this->actingAs($uptUser)->get('/laporan/pdf?type=bebas_harian&tanggal_mulai='.date('Y-m-01').'&tanggal_akhir='.date('Y-m-d'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_generate_single_upt_bebas_harian_pdf_report(): void
    {
        $upt = Upt::first();
        $admin = User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/laporan/pdf?type=bebas_harian&upt_id='.$upt->id.'&tanggal_mulai='.date('Y-m-01').'&tanggal_akhir='.date('Y-m-d'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_generate_single_upt_penghuni_pdf_report(): void
    {
        $upt = Upt::first();
        $admin = User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/laporan/pdf?type=penghuni&upt_id='.$upt->id.'&tanggal_mulai='.date('Y-m-01').'&tanggal_akhir='.date('Y-m-d'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_generate_single_upt_overstaying_pdf_report(): void
    {
        $upt = Upt::first();
        $admin = User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/laporan/pdf?type=overstaying&upt_id='.$upt->id.'&tanggal_mulai='.date('Y-m-01').'&tanggal_akhir='.date('Y-m-d'));

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

    public function test_admin_can_generate_rekapitulasi_and_integrasi_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'upt_id' => null,
        ]);

        $resRekap = $this->actingAs($admin)->get('/laporan/pdf?type=rekapitulasi&tanggal_mulai=2026-01-01&tanggal_akhir=2026-10-31');
        $resRekap->assertStatus(200);
        $resRekap->assertHeader('Content-Type', 'application/pdf');

        $resIntegrasi = $this->actingAs($admin)->get('/laporan/pdf?type=integrasi&tanggal_mulai=2026-01-01&tanggal_akhir=2026-10-31');
        $resIntegrasi->assertStatus(200);
        $resIntegrasi->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_h_tag_calculation_relative_to_report_date(): void
    {
        $hSameDay = KroscekController::calculateHTagInfo('2026-10-31', Carbon::parse('2026-10-31 07:45:00'));
        $this->assertEquals('H', $hSameDay['h_tag']);
        $this->assertEquals('07:45', $hSameDay['jam_str']);

        $hYesterday = KroscekController::calculateHTagInfo('2026-10-31', Carbon::parse('2026-10-30 20:30:00'));
        $this->assertEquals('H-1', $hYesterday['h_tag']);
        $this->assertEquals('20:30', $hYesterday['jam_str']);

        $hTomorrow = KroscekController::calculateHTagInfo('2026-10-31', Carbon::parse('2026-11-01 08:15:00'));
        $this->assertEquals('H+1', $hTomorrow['h_tag']);
        $this->assertEquals('08:15', $hTomorrow['jam_str']);

        $hNull = KroscekController::calculateHTagInfo('2026-10-31', null);
        $this->assertNull($hNull['h_tag']);
        $this->assertNull($hNull['jam_str']);
    }
}
