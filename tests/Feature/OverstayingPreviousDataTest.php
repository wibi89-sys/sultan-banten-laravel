<?php

namespace Tests\Feature;

use App\Models\DataOverstaying;
use App\Models\Upt;
use App\Models\User;
use Tests\TestCase;

class OverstayingPreviousDataTest extends TestCase
{
    private ?User $admin = null;

    private ?Upt $upt = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'admin')->first();
        if (! $this->admin) {
            $this->admin = User::where('akses_overstaying', true)->first();
        }
        $this->upt = Upt::first();
    }

    public function test_unauthorized_user_cannot_fetch_previous_overstaying_data(): void
    {
        $response = $this->getJson('/overstaying/previous-data?upt_id=1&tanggal=2026-11-20');
        $response->assertUnauthorized();
    }

    public function test_returns_empty_when_upt_id_not_provided(): void
    {
        if (! $this->admin) {
            $this->markTestSkipped('Admin user not available.');
        }

        $response = $this->actingAs($this->admin)->getJson('/overstaying/previous-data?tanggal=2026-11-20');
        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'has_data' => false,
        ]);
    }

    public function test_returns_has_data_false_when_yesterday_has_no_data(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $today = '2026-11-25';
        $yesterday = '2026-11-24';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $yesterday)->delete();

        $response = $this->actingAs($this->admin)->getJson("/overstaying/previous-data?upt_id={$this->upt->id}&tanggal={$today}");
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'has_data' => false,
            'previous_date' => $yesterday,
        ]);
    }

    public function test_successfully_fetches_previous_day_overstaying_data(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $today = '2026-11-28';
        $yesterday = '2026-11-27';

        DataOverstaying::where('upt_id', $this->upt->id)->whereIn('tanggal', [$yesterday, $today])->delete();

        DataOverstaying::create([
            'upt_id' => $this->upt->id,
            'tanggal' => $yesterday,
            'tahanan_ai' => 15,
            'tahanan_aii' => 35,
            'tahanan_aiii' => 200,
            'tahanan_aiv' => 10,
            'tahanan_av' => 5,
            'overstaying_ai' => 2,
            'overstaying_aii' => 1,
            'overstaying_aiii' => 0,
            'overstaying_aiv' => 0,
            'overstaying_av' => 0,
            'keterangan' => 'Kondisi aman terkendali',
        ]);

        $response = $this->actingAs($this->admin)->getJson("/overstaying/previous-data?upt_id={$this->upt->id}&tanggal={$today}");
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'has_data' => true,
            'previous_date' => $yesterday,
            'data' => [
                'tahanan_ai' => 15,
                'tahanan_aii' => 35,
                'tahanan_aiii' => 200,
                'tahanan_aiv' => 10,
                'tahanan_av' => 5,
                'overstaying_ai' => 2,
                'overstaying_aii' => 1,
                'overstaying_aiii' => 0,
                'overstaying_aiv' => 0,
                'overstaying_av' => 0,
                'keterangan' => 'Kondisi aman terkendali',
            ],
        ]);

        // Clean up
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $yesterday)->delete();
    }
}
