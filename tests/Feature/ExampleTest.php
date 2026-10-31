<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sultan Banten');
    }

    public function test_public_user_can_access_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Statistik Utama');
    }

    public function test_unauthenticated_user_accessing_kroscek_is_redirected_to_login(): void
    {
        $response = $this->get('/kroscek');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Statistik Utama');
        $response->assertSee('Pidum dan Pidsus');
        $response->assertSee('Peta Lokasi UPT di Banten');
    }

    public function test_authenticated_user_can_access_kroscek(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::first();
        $response = $this->actingAs($user)->get('/kroscek');
        $response->assertStatus(200);
        $response->assertSee('Kroscek Data Tahanan');
        $response->assertSee('Bebas Harian Input');
        $response->assertSee('Status & Jam Input 4 Modul Laporan', false);
        $response->assertSee('kroscek-table-grid');
        $response->assertSee('Kelengkapan Detail per Tanggal (Per UPT)');
        $response->assertSee('detailKroscekTableBody');
    }

    public function test_admin_can_filter_kroscek_detail_per_tanggal(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::first();
        $upt = Upt::first();
        $response = $this->actingAs($user)->get("/kroscek?tab=detail&detail_upt_id={$upt->id}&tanggal_mulai=2026-10-01&tanggal_akhir=2026-10-05");
        $response->assertStatus(200);
        $response->assertSee('Kelengkapan Pelaporan & Kroscek Harian per Tanggal (Detil Tiap UPT)', false);
        $response->assertSee('detailKroscekTableBody');
        $response->assertSee('Hari Kroscek Sinkron');
        $response->assertSee('Hari 4/4 Lengkap');
        $response->assertSee($upt->nama_upt);
    }

    public function test_operator_user_sees_restricted_detail_per_tanggal(): void
    {
        $operator = User::where('role', 'operator')->whereNotNull('upt_id')->first();
        if ($operator) {
            $operatorUpt = Upt::find($operator->upt_id);
            $response = $this->actingAs($operator)->get('/kroscek');
            $response->assertStatus(200);
            $response->assertSee('Kroscek Data & Kelengkapan Pelaporan Harian UPT', false);
            $response->assertSee('Role Operator UPT');
            $response->assertSee('detailKroscekTableBody');
            $response->assertSee('Hari Kroscek Sinkron');
            $response->assertSee('Hari 4/4 Lengkap');
            $response->assertSee('UPT Anda');
            $response->assertSee($operatorUpt->nama_upt);
            $response->assertDontSee('Matriks 12 UPT (Monitoring Harian)');
        }
    }

    public function test_admin_can_download_kroscek_detail_pdf(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::first();
        $upt = Upt::first();
        $response = $this->actingAs($user)->get("/kroscek/detail-pdf?detail_upt_id={$upt->id}&tanggal_mulai=2026-10-01&tanggal_akhir=2026-10-05&sort=desc");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline; filename="Laporan_Kroscek_Detail_', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_operator_user_is_restricted_to_their_own_upt_on_detail_pdf(): void
    {
        $operator = User::where('role', 'operator')->whereNotNull('upt_id')->first();
        if ($operator) {
            $otherUpt = Upt::where('id', '!=', $operator->upt_id)->first();
            $otherUptId = $otherUpt ? $otherUpt->id : 9999;

            // Operator mencoba request PDF UPT lain
            $response = $this->actingAs($operator)->get("/kroscek/detail-pdf?detail_upt_id={$otherUptId}&tanggal_mulai=2026-10-01&tanggal_akhir=2026-10-05");

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/pdf');

            // Header Content-Disposition harus berisi nama UPT operator sendiri, bukan UPT lain
            $operatorUpt = Upt::find($operator->upt_id);
            $expectedSlug = Str::slug($operatorUpt->nama_upt);
            $this->assertStringContainsString($expectedSlug, $response->headers->get('Content-Disposition') ?? '');
        }
    }

    public function test_unauthenticated_user_accessing_kroscek_detail_pdf_is_redirected(): void
    {
        $response = $this->get('/kroscek/detail-pdf');
        $response->assertRedirect('/login');
    }

    public function test_kroscek_modal_has_quick_action_buttons(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::first();
        $response = $this->actingAs($user)->get('/kroscek');
        $response->assertStatus(200);
        $response->assertSee('btnFixPenghuni');
        $response->assertSee('btnFixOverstaying');
        $response->assertSee('p_btn_input');
        $response->assertSee('o_btn_input');
        $response->assertSee('i_btn_input');
        $response->assertSee('b_btn_input');
    }

    public function test_module_forms_support_date_prefill_and_auto_open_action(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::first();
        $testDate = '2026-10-12';

        // 1. Penghuni
        $resPenghuni = $this->actingAs($user)->get("/penghuni?tanggal={$testDate}&action=input");
        $resPenghuni->assertStatus(200);
        $resPenghuni->assertSee("value=\"{$testDate}\"", false);

        // 2. Overstaying
        $resOverstaying = $this->actingAs($user)->get("/overstaying?tanggal={$testDate}&action=input");
        $resOverstaying->assertStatus(200);
        $resOverstaying->assertSee("value=\"{$testDate}\"", false);

        // 3. Integrasi
        $resIntegrasi = $this->actingAs($user)->get("/integrasi?tanggal={$testDate}&action=input");
        $resIntegrasi->assertStatus(200);
        $resIntegrasi->assertSee("value=\"{$testDate}\"", false);

        // 4. Bebas Harian
        $resBebas = $this->actingAs($user)->get("/bebas-harian?tanggal={$testDate}&action=input");
        $resBebas->assertStatus(200);
        $resBebas->assertSee("value=\"{$testDate}\"", false);
    }
}
