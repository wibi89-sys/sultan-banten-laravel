<?php

namespace Tests\Feature;

use App\Models\DataOverstaying;
use App\Models\Upt;
use App\Models\User;
use Tests\TestCase;

class OverstayingDuplicateTest extends TestCase
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

    public function test_cannot_input_duplicate_overstaying_on_same_date_and_upt(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $testDate = '2026-11-20';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // 1. First insert should succeed
        $response1 = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 5,
            'overstaying_ai' => 2,
        ]);

        $response1->assertSessionHas('success');
        $this->assertDatabaseHas('data_overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
        ]);

        // 2. Second insert on the same date and UPT must fail
        $response2 = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 10,
            'overstaying_ai' => 3,
        ]);

        $response2->assertSessionHas('error');

        // Only one record should exist
        $count = DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->count();
        $this->assertEquals(1, $count);

        // Clean up
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();
    }

    public function test_cannot_update_overstaying_to_existing_date_and_upt(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $date1 = '2026-11-21';
        $date2 = '2026-11-22';
        DataOverstaying::where('upt_id', $this->upt->id)->whereIn('tanggal', [$date1, $date2])->delete();

        $record1 = DataOverstaying::create([
            'upt_id' => $this->upt->id,
            'tanggal' => $date1,
            'tahanan_ai' => 1,
            'overstaying_ai' => 1,
        ]);

        $record2 = DataOverstaying::create([
            'upt_id' => $this->upt->id,
            'tanggal' => $date2,
            'tahanan_ai' => 2,
            'overstaying_ai' => 2,
        ]);

        // Try updating record2 to have date1 (which belongs to record1)
        $response = $this->actingAs($this->admin)->put("/overstaying/{$record2->id}", [
            'upt_id' => $this->upt->id,
            'tanggal' => $date1,
            'tahanan_ai' => 3,
            'overstaying_ai' => 3,
        ]);

        $response->assertSessionHas('error');

        // Clean up
        $record1->delete();
        $record2->delete();
    }

    public function test_check_exists_endpoint_detects_existing_date(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $testDate = '2026-11-23';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // When not exists
        $response1 = $this->actingAs($this->admin)->getJson("/overstaying/check-exists?upt_id={$this->upt->id}&tanggal={$testDate}");
        $response1->assertOk();
        $response1->assertJson(['exists' => false]);

        // Create record
        $record = DataOverstaying::create([
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 4,
            'overstaying_ai' => 1,
        ]);

        // When exists
        $response2 = $this->actingAs($this->admin)->getJson("/overstaying/check-exists?upt_id={$this->upt->id}&tanggal={$testDate}");
        $response2->assertOk();
        $response2->assertJson(['exists' => true]);

        // When excluding current record
        $response3 = $this->actingAs($this->admin)->getJson("/overstaying/check-exists?upt_id={$this->upt->id}&tanggal={$testDate}&exclude_id={$record->id}");
        $response3->assertOk();
        $response3->assertJson(['exists' => false]);

        // Clean up
        $record->delete();
    }

    public function test_store_with_force_update_uses_update_or_create_and_prevents_duplicate(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Admin user or UPT not available.');
        }

        $testDate = '2026-11-25';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // 1. Initial insert
        $response1 = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 10,
            'overstaying_ai' => 2,
        ]);
        $response1->assertSessionHas('success');

        // 2. Duplicate with force_update=1 updates existing record and creates no duplicate
        $response2 = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 35,
            'overstaying_ai' => 8,
            'force_update' => 1,
        ]);
        $response2->assertSessionHas('success');

        $this->assertDatabaseHas('data_overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 35,
            'overstaying_ai' => 8,
        ]);

        $count = DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->count();
        $this->assertEquals(1, $count);

        // Cleanup
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();
    }
}
