<?php

namespace Tests\Feature;

use App\Models\DataBebasHarian;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BebasHarianDanFluktuasiMutasiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userAdmin;

    protected Upt $upt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->upt = Upt::first() ?? Upt::create([
            'nama_upt' => 'LAPAS TEST MUTASI',
            'singkatan' => 'LTM',
            'alamat' => 'Jl. Test',
            'kapasitas' => 500,
        ]);

        $this->userAdmin = User::where('role', 'admin')->first()
            ?? User::factory()->create([
                'upt_id' => null,
                'role' => 'admin',
                'akses_bebas_harian' => true,
            ]);
    }

    public function test_model_data_bebas_harian_supports_mutasi_keluar_and_total_pengurang(): void
    {
        $record = DataBebasHarian::create([
            'upt_id' => $this->upt->id,
            'tanggal' => '2026-11-05',
            'klasifikasi_pidana' => 'Tipikor',
            'bebas_murni' => 1,
            'pb' => 2,
            'cb' => 0,
            'cmb' => 0,
            'asimilasi' => 0,
            'bdh' => 0,
            'mutasi_keluar' => 3,
            'ket_mutasi' => 'mutasi ke Lapas Sukamiskin',
            'lainnya' => 0,
        ]);

        $this->assertEquals(3, $record->mutasi_keluar);
        $this->assertEquals('mutasi ke Lapas Sukamiskin', $record->ket_mutasi);
        $this->assertEquals(3, $record->total_bebas); // 1 BM + 2 PB
        $this->assertEquals(6, $record->total_pengurang); // 3 Bebas + 3 Mutasi
    }

    public function test_fluktuasi_page_displays_mutasi_keluar_and_reconciliation(): void
    {
        $response = $this->actingAs($this->userAdmin)->get(route('fluktuasi.index', [
            'tanggal' => '2026-11-01',
            'tanggal_pembanding' => '2026-10-31',
            'upt_id' => 1, // Lapas Kelas I Tangerang
        ]));

        $response->assertStatus(200);
        $response->assertSee('Mutasi Keluar');
        $response->assertSee('Lapas Sukamiskin');
    }
}
