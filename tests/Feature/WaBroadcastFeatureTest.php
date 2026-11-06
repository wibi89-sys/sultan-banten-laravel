<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use App\Models\WaSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WaBroadcastFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first() ?? User::create([
            'username' => 'admin_test_'.time(),
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Admin Test',
            'role' => 'admin',
        ]);

        $this->adminUser->role = 'admin';
        if (Schema::hasColumn('users', 'akses_pengumuman')) {
            $this->adminUser->akses_pengumuman = 1;
        }
        if (Schema::hasColumn('users', 'akses_wa_settings')) {
            $this->adminUser->akses_wa_settings = 1;
        }
        $this->adminUser->save();
    }

    public function test_user_can_access_pengumuman_broadcast_tab(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('pengumuman.index', ['tab' => 'broadcast']));

        $response->assertStatus(200);
        $response->assertSee('Siaran WhatsApp Operator');
        $response->assertSee('Buat Siaran WhatsApp ke Operator');
    }

    public function test_can_calculate_recipients_count_dynamically(): void
    {
        $upt = Upt::firstOrCreate(
            ['nama_upt' => 'UPT Test Broadcast'],
            ['kapasitas' => 500, 'alamat' => 'Alamat Test']
        );

        User::create([
            'username' => 'op_broadcast_test_'.time(),
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Operator Lapas Test Broadcast',
            'role' => 'operator',
            'upt_id' => $upt->id,
            'no_wa' => '081234567890',
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('pengumuman.recipients-count'), [
            'target_type' => 'role',
            'target_roles' => ['operator'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'total',
            'valid_wa',
            'missing_wa',
            'role_counts' => ['operator', 'operator_wilayah', 'admin', 'viewer'],
            'users',
        ]);
    }

    public function test_can_get_upt_operators_for_pending_reminder(): void
    {
        $upt = Upt::firstOrCreate(
            ['nama_upt' => 'UPT Test Reminder'],
            ['kapasitas' => 300, 'alamat' => 'Alamat Test']
        );

        User::create([
            'username' => 'op_reminder_test_'.time(),
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Operator Rutan Test Reminder',
            'role' => 'operator',
            'upt_id' => $upt->id,
            'no_wa' => '081298765432',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('wa.upt-operators', $upt->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'upt_nama' => 'UPT Test Reminder',
        ]);
        $response->assertJsonFragment([
            'nama' => 'Operator Rutan Test Reminder',
        ]);
        $response->assertJsonStructure([
            'template_sidang_ba',
            'template_mutasi',
        ]);
    }

    public function test_can_save_sidang_ba_reminder_template(): void
    {
        $customTemplate = 'Pengingat Khusus BA TPP untuk {nama_upt}. Link: {link_sidang}';

        $response = $this->actingAs($this->adminUser)->postJson(route('wa.save-reminder-template'), [
            'type' => 'sidang_ba',
            'template' => $customTemplate,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $setting = WaSetting::getFirst();
        $this->assertEquals($customTemplate, $setting->template_pengingat_sidang_ba);
    }

    public function test_can_save_mutasi_reminder_template(): void
    {
        $customTemplate = 'Pengingat Khusus Mutasi untuk {nama_upt}. Berkas: {nama_berkas}';

        $response = $this->actingAs($this->adminUser)->postJson(route('wa.save-reminder-template'), [
            'type' => 'mutasi',
            'template' => $customTemplate,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $setting = WaSetting::getFirst();
        $this->assertEquals($customTemplate, $setting->template_pengingat_mutasi);
    }

    public function test_can_update_builtin_settings_with_reminder_templates(): void
    {
        $tppBaTemplate = 'Format Baru BA TPP dari Halaman Pengaturan {nama_upt}';
        $mutasiTemplate = 'Format Baru Mutasi dari Halaman Pengaturan {nama_upt}';

        // Test update sidang TPP
        $resTpp = $this->actingAs($this->adminUser)->post(route('wa.schedules.builtin.update'), [
            'type' => 'sidang_tpp',
            'template_sidang_tpp' => 'Undangan Sidang TPP',
            'template_pengingat_sidang_ba' => $tppBaTemplate,
        ]);
        $resTpp->assertRedirect();

        $setting = WaSetting::getFirst();
        $this->assertEquals($tppBaTemplate, $setting->template_pengingat_sidang_ba);

        // Test update mutasi
        $resMutasi = $this->actingAs($this->adminUser)->post(route('wa.schedules.builtin.update'), [
            'type' => 'mutasi',
            'template_mutasi_usulan' => 'Usulan Mutasi',
            'template_mutasi_disetujui' => 'Persetujuan Mutasi',
            'template_pengingat_mutasi' => $mutasiTemplate,
        ]);
        $resMutasi->assertRedirect();

        $setting->refresh();
        $this->assertEquals($mutasiTemplate, $setting->template_pengingat_mutasi);
    }
}
