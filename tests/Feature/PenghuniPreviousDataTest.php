<?php

namespace Tests\Feature;

use App\Models\DataPenghuni;
use App\Models\Upt;
use App\Models\User;
use Tests\TestCase;

class PenghuniPreviousDataTest extends TestCase
{
    private ?User $admin = null;

    private ?Upt $upt = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'admin')->first();
        if (! $this->admin) {
            $this->admin = User::where('akses_penghuni', true)->first();
        }
        $this->upt = Upt::first();
    }

    public function test_unauthorized_user_cannot_fetch_previous_penghuni_data(): void
    {
        $response = $this->getJson('/penghuni/previous-data?upt_id=1&tanggal=2026-11-20');
        $response->assertUnauthorized();
    }

    public function test_returns_empty_when_upt_id_not_provided(): void
    {
        if (! $this->admin) {
            $this->markTestSkipped('Admin user not available.');
        }

        $response = $this->actingAs($this->admin)->getJson('/penghuni/previous-data?tanggal=2026-11-20');
        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'has_data' => false,
        ]);
    }

    public function test_successfully_fetches_exact_yesterday_penghuni_data(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $today = '2026-11-28';
        $yesterday = '2026-11-27';

        DataPenghuni::where('upt_id', $this->upt->id)->whereIn('tanggal', [$yesterday, $today])->delete();

        DataPenghuni::create([
            'upt_id' => $this->upt->id,
            'tanggal' => $yesterday,
            'klasifikasi_pidana' => 'Tipikor',
            'tahanan_dewasa_laki' => 5,
            'tahanan_dewasa_perempuan' => 1,
            'tahanan_anak_laki' => 0,
            'tahanan_anak_perempuan' => 0,
            'narapidana_dewasa_laki' => 15,
            'narapidana_dewasa_perempuan' => 2,
            'narapidana_anak_laki' => 0,
            'narapidana_anak_perempuan' => 0,
            'total_tahanan' => 6,
            'total_narapidana' => 17,
            'total_penghuni' => 23,
            'status' => 'Lengkap',
        ]);

        $response = $this->actingAs($this->admin)->getJson("/penghuni/previous-data?upt_id={$this->upt->id}&tanggal={$today}");
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'has_data' => true,
            'is_exact_yesterday' => true,
            'previous_date' => $yesterday,
            'data' => [
                'tipikor' => [
                    'tahanan_dl' => 5,
                    'tahanan_dp' => 1,
                    'narapidana_dl' => 15,
                    'narapidana_dp' => 2,
                ],
            ],
        ]);
    }

    public function test_fallbacks_to_latest_prior_data_when_yesterday_missing(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $today = '2026-11-28';
        $yesterday = '2026-11-27';
        $threeDaysAgo = '2026-11-25';

        DataPenghuni::where('upt_id', $this->upt->id)->whereIn('tanggal', [$threeDaysAgo, $yesterday, $today])->delete();

        // Only create data for 3 days ago, simulate weekend or missed day on yesterday
        DataPenghuni::create([
            'upt_id' => $this->upt->id,
            'tanggal' => $threeDaysAgo,
            'klasifikasi_pidana' => 'Pidana Umum',
            'tahanan_dewasa_laki' => 20,
            'tahanan_dewasa_perempuan' => 3,
            'tahanan_anak_laki' => 0,
            'tahanan_anak_perempuan' => 0,
            'narapidana_dewasa_laki' => 150,
            'narapidana_dewasa_perempuan' => 12,
            'narapidana_anak_laki' => 0,
            'narapidana_anak_perempuan' => 0,
            'total_tahanan' => 23,
            'total_narapidana' => 162,
            'total_penghuni' => 185,
            'status' => 'Lengkap',
        ]);

        $response = $this->actingAs($this->admin)->getJson("/penghuni/previous-data?upt_id={$this->upt->id}&tanggal={$today}");
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'has_data' => true,
            'is_exact_yesterday' => false,
            'previous_date' => $threeDaysAgo,
            'data' => [
                'pidana_umum' => [
                    'tahanan_dl' => 20,
                    'tahanan_dp' => 3,
                    'narapidana_dl' => 150,
                    'narapidana_dp' => 12,
                ],
            ],
        ]);
    }
}

