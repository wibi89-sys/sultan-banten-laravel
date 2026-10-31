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

        // 2. Viewer role with view and none permissions
        $viewer = new User([
            'role' => 'viewer',
            'akses_penghuni' => 'view',
            'akses_mutasi' => 'view',
            'akses_upt' => 'none',
        ]);
        $this->assertTrue($viewer->isViewer());
        $this->assertEquals('view', $viewer->getAccessLevel('penghuni'));
        $this->assertTrue($viewer->canView('penghuni'));
        $this->assertFalse($viewer->canManage('penghuni'));
        $this->assertEquals('none', $viewer->getAccessLevel('upt'));
        $this->assertFalse($viewer->canView('upt'));

        // 3. Operator Wilayah (Kanwil scope) with flexible permissions
        $opWilayah = new User([
            'role' => 'operator_wilayah',
            'upt_id' => null,
            'akses_mutasi' => 'full',
            'akses_sidang_tpp' => 'full',
            'akses_penghuni' => 'view',
            'akses_user' => 'none',
        ]);
        $this->assertTrue($opWilayah->isOperatorWilayah());
        $this->assertTrue($opWilayah->isKanwil());
        $this->assertNull($opWilayah->getUserUptFilter());
        $this->assertEquals('full', $opWilayah->getAccessLevel('mutasi'));
        $this->assertTrue($opWilayah->canManage('mutasi'));
        $this->assertEquals('view', $opWilayah->getAccessLevel('penghuni'));
        $this->assertTrue($opWilayah->canView('penghuni'));
        $this->assertFalse($opWilayah->canManage('penghuni'));
        $this->assertEquals('none', $opWilayah->getAccessLevel('user'));
        $this->assertFalse($opWilayah->canView('user'));

        // 4. Regular operator with mixed permissions
        $operator = new User([
            'role' => 'operator',
            'upt_id' => $upt ? $upt->id : null,
            'akses_penghuni' => 'view',
            'akses_overstaying' => 'full',
            'akses_mutasi' => 'none',
        ]);
        $this->assertTrue($operator->isOperator());
        $this->assertTrue($operator->isOperatorUpt());
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

    public function test_create_and_edit_user_full_pages_render_successfully(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin found');
        }

        // Admin can access create page
        $createResponse = $this->actingAs($admin)->get('/users/create');
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Tambah Pengguna Baru');
        $createResponse->assertSee('Layanan &amp; Data Pemasyarakatan', false);

        // Admin can access edit page
        $editResponse = $this->actingAs($admin)->get("/users/{$admin->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Pengguna');
        $editResponse->assertSee($admin->username);

        // View-only user on user module cannot access create page
        $upt = Upt::first();
        $viewOnlyUser = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $upt ? $upt->id : null,
            'akses_user' => 'view',
        ]);

        $blockedCreateResponse = $this->actingAs($viewOnlyUser)->get('/users/create');
        $blockedCreateResponse->assertStatus(403);
    }

    public function test_user_controller_can_create_and_update_operator_wilayah(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin found');
        }

        $uniqueUsername = 'opwilayah_'.uniqid();

        // 1. Create operator_wilayah
        $response = $this->actingAs($admin)->from('/users')->post('/users', [
            'nama_lengkap' => 'Operator Kanwil Mutasi & TPP',
            'username' => $uniqueUsername,
            'password' => 'secret123',
            'role' => 'operator_wilayah',
            'upt_id' => '',
            'akses_mutasi' => 'full',
            'akses_sidang_tpp' => 'full',
            'akses_penghuni' => 'view',
            'akses_user' => 'none',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $user = User::where('username', $uniqueUsername)->first();
        $this->assertNotNull($user);
        $this->assertEquals('operator_wilayah', $user->role);
        $this->assertTrue($user->isOperatorWilayah());
        $this->assertTrue($user->isKanwil());
        $this->assertNull($user->upt_id);
        $this->assertEquals('full', $user->akses_mutasi);
        $this->assertEquals('full', $user->akses_sidang_tpp);
        $this->assertEquals('view', $user->akses_penghuni);
        $this->assertEquals('none', $user->akses_user);

        // 2. Update operator_wilayah permissions
        $updateResponse = $this->actingAs($admin)->from('/users')->put("/users/{$user->id}", [
            'nama_lengkap' => 'Operator Kanwil Updated',
            'role' => 'operator_wilayah',
            'upt_id' => '',
            'akses_mutasi' => 'view',
            'akses_sidang_tpp' => 'full',
            'akses_penghuni' => 'none',
            'akses_laporan' => 'full',
        ]);

        $updateResponse->assertRedirect('/users');
        $updateResponse->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('Operator Kanwil Updated', $user->nama_lengkap);
        $this->assertEquals('view', $user->akses_mutasi);
        $this->assertEquals('full', $user->akses_sidang_tpp);
        $this->assertEquals('none', $user->akses_penghuni);
        $this->assertEquals('full', $user->akses_laporan);
    }
}
