<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserGranularPermissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_granular_access_levels_and_methods(): void
    {
        $upt = Upt::first();

        // 1. Admin always has full access
        $admin = new User([
            'role' => 'admin',
        ]);
        $this->assertTrue($admin->isAdmin());
        $this->assertEquals('full', $admin->getAccessLevel('penghuni'));
        $this->assertTrue($admin->canView('penghuni'));
        $this->assertTrue($admin->canManage('penghuni'));

        // 2. Viewer role is capped at 'view' even if column has 'full'
        $viewer = new User([
            'role' => 'viewer',
            'akses_penghuni' => 'full',
            'akses_mutasi' => 'view',
            'akses_upt' => 'none',
        ]);
        $this->assertTrue($viewer->isViewer());
        $this->assertEquals('view', $viewer->getAccessLevel('penghuni'));
        $this->assertTrue($viewer->canView('penghuni'));
        $this->assertFalse($viewer->canManage('penghuni'));
        $this->assertEquals('none', $viewer->getAccessLevel('upt'));
        $this->assertFalse($viewer->canView('upt'));

        // 3. Regular operator with mixed permissions
        $operator = new User([
            'role' => 'operator',
            'upt_id' => $upt ? $upt->id : null,
            'akses_penghuni' => 'view',
            'akses_overstaying' => 'full',
            'akses_mutasi' => 'none',
        ]);
        // Penghuni: view only
        $this->assertEquals('view', $operator->getAccessLevel('penghuni'));
        $this->assertTrue($operator->canView('penghuni'));
        $this->assertFalse($operator->canManage('penghuni'));

        // Overstaying: full access
        $this->assertEquals('full', $operator->getAccessLevel('overstaying'));
        $this->assertTrue($operator->canView('overstaying'));
        $this->assertTrue($operator->canManage('overstaying'));

        // Mutasi: none
        $this->assertEquals('none', $operator->getAccessLevel('mutasi'));
        $this->assertFalse($operator->canView('mutasi'));
        $this->assertFalse($operator->canManage('mutasi'));

        // Dashboard: always automatically accessible
        $this->assertEquals('view', $operator->getAccessLevel('dashboard'));
        $this->assertTrue($operator->canView('dashboard'));
        $this->assertTrue($operator->hasAccess('dashboard'));
    }

    public function test_dashboard_is_always_accessible_to_any_user(): void
    {
        $operator = new User([
            'role' => 'operator',
        ]);
        $this->assertTrue($operator->hasAccess('dashboard'));
        $this->assertTrue($operator->canView('dashboard'));
        $this->assertEquals('view', $operator->getAccessLevel('dashboard'));

        $response = $this->actingAs($operator)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_view_only_user_can_access_penghuni_index_but_cannot_create(): void
    {
        $upt = Upt::first();
        if (! $upt) {
            $this->markTestSkipped('No UPT found');
        }

        $user = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt->id,
            'akses_penghuni' => 'view',
        ]);

        // GET index should be allowed and show read-only indicator
        $response = $this->actingAs($user)->get('/penghuni');
        $response->assertStatus(200);
        $response->assertSee('Mode Lihat Saja');

        // POST store should return 403 Forbidden
        $storeResponse = $this->actingAs($user)->post('/penghuni', [
            'upt_id' => $upt->id,
            'tanggal' => now()->toDateString(),
            'tahanan_pria' => 10,
        ]);
        $storeResponse->assertStatus(403);
    }

    public function test_user_with_none_access_is_blocked_from_viewing_module(): void
    {
        $upt = Upt::first();
        if (! $upt) {
            $this->markTestSkipped('No UPT found');
        }

        $user = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt->id,
            'akses_penghuni' => 'none',
        ]);

        $response = $this->actingAs($user)->get('/penghuni');
        $response->assertStatus(403);
    }

    public function test_view_only_user_cannot_mutate_bebas_harian_or_integrasi(): void
    {
        $upt = Upt::first();
        if (! $upt) {
            $this->markTestSkipped('No UPT found');
        }

        $user = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt->id,
            'akses_bebas_harian' => 'view',
            'akses_integrasi' => 'view',
        ]);

        // POST bebas harian should be blocked with 403
        $bebasResponse = $this->actingAs($user)->post('/bebas-harian', [
            'tanggal' => now()->toDateString(),
            'upt_id' => $upt->id,
        ]);
        $bebasResponse->assertStatus(403);

        // POST integrasi should be blocked with 403
        $integrasiResponse = $this->actingAs($user)->post('/integrasi', [
            'tanggal' => now()->toDateString(),
            'upt_id' => $upt->id,
        ]);
        $integrasiResponse->assertStatus(403);
    }

    public function test_user_controller_stores_and_updates_granular_permissions(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin found');
        }

        $upt = Upt::first();
        $uniqueUsername = 'testuser_'.uniqid();

        // Admin creates a user with explicit mixed permissions
        $response = $this->actingAs($admin)->from('/users')->post('/users', [
            'nama_lengkap' => 'Test Granular Operator',
            'username' => $uniqueUsername,
            'password' => 'password123',
            'role' => 'operator',
            'upt_id' => $upt ? $upt->id : null,
            'akses_penghuni' => 'view',
            'akses_mutasi' => 'full',
            'akses_upt' => 'none',
            'akses_overstaying' => 'view',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $createdUser = User::where('username', $uniqueUsername)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('view', $createdUser->akses_penghuni);
        $this->assertEquals('full', $createdUser->akses_mutasi);
        $this->assertEquals('none', $createdUser->akses_upt);
        $this->assertEquals('view', $createdUser->akses_overstaying);

        // Admin updates user's permissions
        $updateResponse = $this->actingAs($admin)->from('/users')->put("/users/{$createdUser->id}", [
            'nama_lengkap' => 'Updated Operator Name',
            'username' => $uniqueUsername,
            'role' => 'operator',
            'upt_id' => $upt ? $upt->id : null,
            'akses_penghuni' => 'full',
            'akses_mutasi' => 'none',
            'akses_upt' => 'view',
        ]);

        $updateResponse->assertRedirect('/users');
        $updateResponse->assertSessionHas('success');

        $createdUser->refresh();
        $this->assertEquals('full', $createdUser->akses_penghuni);
        $this->assertEquals('none', $createdUser->akses_mutasi);
        $this->assertEquals('view', $createdUser->akses_upt);
    }
}
