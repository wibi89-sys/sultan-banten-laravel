<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Tests\TestCase;

class BackupTest extends TestCase
{
    private ?User $adminUser = null;

    private ?User $operatorUser = null;

    protected function tearDown(): void
    {
        if ($this->adminUser) {
            $this->adminUser->delete();
        }

        if ($this->operatorUser) {
            $this->operatorUser->delete();
        }

        // Bersihkan file uji jika ada
        $service = new DatabaseBackupService;
        $service->deleteBackupFile('feature_test_backup.sql.gz');

        parent::tearDown();
    }

    private function getAdminUser(): User
    {
        if (! $this->adminUser) {
            $this->adminUser = User::create([
                'username' => 'admin_test_'.uniqid(),
                'password' => bcrypt('password123'),
                'nama_lengkap' => 'Administrator Tester',
                'role' => 'admin',
            ]);
        }

        return $this->adminUser;
    }

    private function getOperatorUser(): User
    {
        if (! $this->operatorUser) {
            $this->operatorUser = User::create([
                'username' => 'operator_test_'.uniqid(),
                'password' => bcrypt('password123'),
                'nama_lengkap' => 'Operator Tester',
                'role' => 'operator',
                'akses_instansi' => 'view',
            ]);
        }

        return $this->operatorUser;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('backup.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $operator = $this->getOperatorUser();

        $response = $this->actingAs($operator)->get(route('backup.index'));
        $response->assertForbidden();

        $responseDownload = $this->actingAs($operator)->get(route('backup.download'));
        $responseDownload->assertForbidden();

        $responseGenerate = $this->actingAs($operator)->post(route('backup.generate'));
        $responseGenerate->assertForbidden();

        $responseImport = $this->actingAs($operator)->post(route('backup.import'));
        $responseImport->assertForbidden();
    }

    public function test_admin_can_access_backup_page(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->get(route('backup.index'));

        $response->assertOk();
        $response->assertSee('Backup &amp; Ekspor Database', false);
        $response->assertSee('Unduh Database (.sql.gz)');
        $response->assertSee('Unduh SQL Mentah (.sql)');
    }

    public function test_admin_can_generate_and_delete_server_backup(): void
    {
        $admin = $this->getAdminUser();
        $service = new DatabaseBackupService;

        // Generate file manual dengan nama khusus untuk verifikasi
        $service->generateBackupFile('feature_test_backup.sql.gz', true);
        $this->assertFileExists($service->getBackupDirectory().DIRECTORY_SEPARATOR.'feature_test_backup.sql.gz');

        // Pastikan muncul di view
        $response = $this->actingAs($admin)->get(route('backup.index'));
        $response->assertSee('feature_test_backup.sql.gz');

        // Pastikan struktur SQL memiliki pembersihan awal seluruh tabel & tabel induk `upt` di atas `data_integrasi`
        $gzPath = $service->getBackupDirectory().DIRECTORY_SEPARATOR.'feature_test_backup.sql.gz';
        $sqlContent = (string) gzdecode((string) file_get_contents($gzPath));
        $firstDropPos = strpos($sqlContent, 'DROP TABLE IF EXISTS `data_integrasi`;');
        $firstCreatePos = strpos($sqlContent, 'CREATE TABLE');
        $createUptPos = strpos($sqlContent, 'CREATE TABLE `upt`');
        $createIntegrasiPos = strpos($sqlContent, 'CREATE TABLE `data_integrasi`');

        $this->assertNotFalse($firstDropPos);
        $this->assertNotFalse($firstCreatePos);
        $this->assertLessThan($firstCreatePos, $firstDropPos);
        $this->assertLessThan($createIntegrasiPos, $createUptPos);

        // Unduh file yang tersimpan
        $responseDownload = $this->actingAs($admin)->get(route('backup.download-file', 'feature_test_backup.sql.gz'));
        $responseDownload->assertOk();
        $responseDownload->assertHeader('content-disposition', 'attachment; filename=feature_test_backup.sql.gz');

        // Hapus file
        $responseDelete = $this->actingAs($admin)->delete(route('backup.destroy-file', 'feature_test_backup.sql.gz'));
        $responseDelete->assertRedirect(route('backup.index'));
        $this->assertFileDoesNotExist($service->getBackupDirectory().DIRECTORY_SEPARATOR.'feature_test_backup.sql.gz');
    }

    public function test_admin_can_download_database_stream_directly(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->get(route('backup.download', ['format' => 'gz']));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'attachment; filename=sultan_banten_backup_'));
    }
}
