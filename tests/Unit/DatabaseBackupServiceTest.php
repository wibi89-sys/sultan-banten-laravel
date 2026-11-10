<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use Tests\TestCase;

class DatabaseBackupServiceTest extends TestCase
{
    protected DatabaseBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DatabaseBackupService;
    }

    public function test_get_database_stats_returns_expected_structure(): void
    {
        $stats = $this->service->getDatabaseStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('database_name', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('total_tables', $stats);
        $this->assertArrayHasKey('total_rows', $stats);
        $this->assertArrayHasKey('total_size_mb', $stats);
        $this->assertGreaterThan(0, $stats['total_tables']);
    }

    public function test_generate_and_delete_backup_file(): void
    {
        $result = $this->service->generateBackupFile('unit_test_sample.sql.gz', true);

        $this->assertIsArray($result);
        $this->assertEquals('unit_test_sample.sql.gz', $result['filename']);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, $result['size_bytes']);

        // Pastikan muncul di daftar file
        $files = $this->service->getBackupFiles();
        $filenames = array_column($files, 'filename');
        $this->assertContains('unit_test_sample.sql.gz', $filenames);

        // Hapus file
        $deleted = $this->service->deleteBackupFile('unit_test_sample.sql.gz');
        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($result['path']);
    }

    public function test_path_traversal_protection(): void
    {
        $this->assertFalse($this->service->deleteBackupFile('../../../test.sql'));
        $this->assertNull($this->service->getValidBackupPath('../../../test.sql'));
    }
}
