<?php

namespace Tests\Feature;

use App\Models\DataBebasHarian;
use App\Models\DataIntegrasi;
use App\Models\DataOverstaying;
use App\Models\DataPenghuni;
use App\Models\Upt;
use App\Models\User;
use Tests\TestCase;

class DuplicateInputPreventionTest extends TestCase
{
    private ?User $admin = null;

    private ?Upt $upt = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'admin')->first();
        if (! $this->admin) {
            $this->admin = User::first();
        }
        $this->upt = Upt::first();
    }

    public function test_penghuni_duplicate_prevention_and_force_update(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $testDate = '2026-12-01';
        DataPenghuni::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // 1. Initial insert
        $response1 = $this->actingAs($this->admin)->post('/penghuni', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_dl_pidana_umum' => 10,
        ]);
        $response1->assertSessionHas('success');

        // 2. Duplicate without force_update must fail
        $response2 = $this->actingAs($this->admin)->post('/penghuni', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_dl_pidana_umum' => 20,
        ]);
        $response2->assertSessionHas('error');

        // 3. Duplicate with force_update=1 must update existing
        $response3 = $this->actingAs($this->admin)->post('/penghuni', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_dl_pidana_umum' => 25,
            'force_update' => 1,
        ]);
        $response3->assertSessionHas('success');

        $this->assertDatabaseHas('data_penghuni', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'klasifikasi_pidana' => 'Pidana Umum',
            'tahanan_dewasa_laki' => 25,
        ]);

        // 4. check-exists endpoint
        $checkRes = $this->actingAs($this->admin)->getJson("/penghuni/check-exists?upt_id={$this->upt->id}&tanggal={$testDate}");
        $checkRes->assertOk();
        $checkRes->assertJson(['exists' => true]);

        // Cleanup
        DataPenghuni::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();
    }

    public function test_overstaying_duplicate_prevention_and_force_update(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $testDate = '2026-12-02';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // 1. Initial insert
        $response1 = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 5,
            'overstaying_ai' => 2,
        ]);
        $response1->assertSessionHas('success');

        // 2. Duplicate without force_update must fail
        $response2 = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 8,
            'overstaying_ai' => 3,
        ]);
        $response2->assertSessionHas('error');

        // 3. Duplicate with force_update=1 must succeed and update
        $response3 = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 12,
            'overstaying_ai' => 4,
            'force_update' => 1,
        ]);
        $response3->assertSessionHas('success');

        $this->assertDatabaseHas('data_overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 12,
            'overstaying_ai' => 4,
        ]);

        // 4. check-exists endpoint
        $checkRes = $this->actingAs($this->admin)->getJson("/overstaying/check-exists?upt_id={$this->upt->id}&tanggal={$testDate}");
        $checkRes->assertOk();
        $checkRes->assertJson(['exists' => true]);

        // Cleanup
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();
    }

    public function test_bebas_harian_duplicate_prevention_and_force_update(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $testDate = '2026-12-03';
        DataBebasHarian::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // 1. Initial insert
        $response1 = $this->actingAs($this->admin)->post('/bebas-harian', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'bebas_murni_pidana_umum' => 3,
        ]);
        $response1->assertSessionHas('success');

        // 2. Duplicate without force_update must fail
        $response2 = $this->actingAs($this->admin)->post('/bebas-harian', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'bebas_murni_pidana_umum' => 5,
        ]);
        $response2->assertSessionHas('error');

        // 3. Duplicate with force_update=1 must update existing
        $response3 = $this->actingAs($this->admin)->post('/bebas-harian', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'bebas_murni_pidana_umum' => 7,
            'force_update' => 1,
        ]);
        $response3->assertSessionHas('success');

        $this->assertDatabaseHas('data_bebas_harian', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'klasifikasi_pidana' => 'Pidana Umum',
            'bebas_murni' => 7,
        ]);

        // 4. check-exists endpoint
        $checkRes = $this->actingAs($this->admin)->getJson("/bebas-harian/check-exists?upt_id={$this->upt->id}&tanggal={$testDate}");
        $checkRes->assertOk();
        $checkRes->assertJson(['exists' => true]);

        // Cleanup
        DataBebasHarian::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();
    }

    public function test_integrasi_duplicate_prevention_and_force_update(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $testDate = '2026-12-04';
        DataIntegrasi::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // 1. Initial insert
        $response1 = $this->actingAs($this->admin)->post('/integrasi', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'usulan_pb' => 2,
            'sk_terbit_pb' => 1,
        ]);
        $response1->assertSessionHas('success');

        // 2. Duplicate without force_update must fail
        $response2 = $this->actingAs($this->admin)->post('/integrasi', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'usulan_pb' => 4,
            'sk_terbit_pb' => 2,
        ]);
        $response2->assertSessionHas('error');

        // 3. Duplicate with force_update=1 must succeed and update
        $response3 = $this->actingAs($this->admin)->post('/integrasi', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'usulan_pb' => 6,
            'sk_terbit_pb' => 3,
            'force_update' => 1,
        ]);
        $response3->assertSessionHas('success');

        $this->assertDatabaseHas('data_integrasi', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'usulan_pb' => 6,
            'sk_terbit_pb' => 3,
        ]);

        // 4. check-exists endpoint
        $checkRes = $this->actingAs($this->admin)->getJson("/integrasi/check-exists?upt_id={$this->upt->id}&tanggal={$testDate}");
        $checkRes->assertOk();
        $checkRes->assertJson(['exists' => true]);

        // Cleanup
        DataIntegrasi::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();
    }
}
