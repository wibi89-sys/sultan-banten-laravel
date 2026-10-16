<?php

namespace Tests\Feature;

use App\Models\DataOverstaying;
use App\Models\Upt;
use App\Models\User;
use Tests\TestCase;

class OverstayingValidationTest extends TestCase
{
    private ?User $admin = null;

    private ?Upt $upt = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->admin = User::where('role', 'admin')->first();
            if (! $this->admin) {
                $this->admin = User::where('akses_overstaying', true)->first();
            }
            $this->upt = Upt::first();
        } catch (\Throwable) {
            $this->admin = null;
            $this->upt = null;
        }
    }

    public function test_cannot_store_overstaying_greater_than_tahanan(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Database/Admin user or UPT not available.');
        }

        $testDate = '2026-11-25';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // Tahanan AI = 1, Overstaying AI = 2 (Tidak mungkin / harus ditolak)
        $response = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_ai' => 1,
            'overstaying_ai' => 2,
        ]);

        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertStringContainsString('Overstaying AI', $errorMessage);
        $this->assertStringContainsString('tidak boleh melebihi', $errorMessage);

        $this->assertDatabaseMissing('data_overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
        ]);
    }

    public function test_cannot_store_overstaying_when_tahanan_is_zero(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Database/Admin user or UPT not available.');
        }

        $testDate = '2026-11-26';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // Tahanan AII = 0, Overstaying AII = 1 (Tahanan 0 tidak boleh ada overstaying)
        $response = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_aii' => 0,
            'overstaying_aii' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('data_overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
        ]);
    }

    public function test_can_store_overstaying_when_valid(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Database/Admin user or UPT not available.');
        }

        $testDate = '2026-11-27';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // Tahanan AIII = 5, Overstaying AIII = 2 (Valid: 2 <= 5)
        $response = $this->actingAs($this->admin)->post('/overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_aiii' => 5,
            'overstaying_aiii' => 2,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('data_overstaying', [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_aiii' => 5,
            'overstaying_aiii' => 2,
        ]);

        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();
    }

    public function test_cannot_update_overstaying_greater_than_tahanan(): void
    {
        if (! $this->admin || ! $this->upt) {
            $this->markTestSkipped('Database/Admin user or UPT not available.');
        }

        $testDate = '2026-11-28';
        DataOverstaying::where('upt_id', $this->upt->id)->where('tanggal', $testDate)->delete();

        // Simpan data awal yang valid lebih dulu
        $data = DataOverstaying::create([
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_aiv' => 3,
            'overstaying_aiv' => 1,
        ]);

        // Coba update dengan Overstaying AIV = 4 (padahal Tahanan AIV = 3)
        $response = $this->actingAs($this->admin)->put('/overstaying/'.$data->id, [
            'upt_id' => $this->upt->id,
            'tanggal' => $testDate,
            'tahanan_aiv' => 3,
            'overstaying_aiv' => 4,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Overstaying AIV', session('error'));

        // Pastikan nilai di database tidak berubah menjadi 4
        $this->assertDatabaseMissing('data_overstaying', [
            'id' => $data->id,
            'overstaying_aiv' => 4,
        ]);

        $data->delete();
    }
}
