<?php

namespace Tests\Unit;

use App\Services\TppExcelService;
use PHPUnit\Framework\TestCase;

class TppExcelServiceTest extends TestCase
{
    public function test_download_template_returns_streamed_response_with_excel_headers(): void
    {
        $response = TppExcelService::downloadTemplate();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Template_Usulan_Sidang_TPP', $response->headers->get('Content-Disposition'));
    }

    public function test_parse_csv_content_with_perlu_perhatian_and_strictly_asimilasi(): void
    {
        $csvContent = "No;Nama WBP;Nomor Register;Perkara;Lama Pidana;Ekspirasi;1/2 Pidana;2/3 Pidana;Usulan;Menarik Perhatian Kanwil;Keterangan\n";
        $csvContent .= "1;Ahmad Asimilasi;BI.888/2026;Pencurian Ps 363;2 Tahun;2027-12-01;2026-06-01;2026-10-01;Asimilasi Kerja Mandiri;Ya;Perhatian khusus kanwil\n";
        $csvContent .= "2;Budi Santoso;BI.889/2026;Narkotika Ps 114;4 Tahun;2028-12-01;2026-12-01;2027-04-01;PB;Tidak;Syarat lengkap\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'tpp_test_').'.csv';
        file_put_contents($tempFile, $csvContent);

        $reflection = new \ReflectionClass(TppExcelService::class);
        $method = $reflection->getMethod('parseCsv');
        $method->setAccessible(true);

        $result = $method->invoke(null, $tempFile);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total']);

        // Check Row 1: Normalized to strictly Asimilasi and flagged for attention
        $row1 = $result['data'][0];
        $this->assertEquals('Ahmad Asimilasi', $row1['nama_wbp']);
        $this->assertEquals('Asimilasi', $row1['jenis_usulan']);
        $this->assertTrue($row1['perlu_perhatian']);
        $this->assertEquals('Perhatian khusus kanwil', $row1['catatan_perhatian']);
        $this->assertEquals('belum_putus', $row1['keputusan']);

        // Check Row 2: Normal participant
        $row2 = $result['data'][1];
        $this->assertEquals('Budi Santoso', $row2['nama_wbp']);
        $this->assertEquals('PB', $row2['jenis_usulan']);
        $this->assertFalse($row2['perlu_perhatian']);
        $this->assertEquals('Syarat lengkap', $row2['alasan_keterangan']);

        @unlink($tempFile);
    }

    public function test_parse_csv_content_with_penempatan_mutasi(): void
    {
        $csvContent = "No;Nama WBP;Nomor Register;Perkara;Lama Pidana;Ekspirasi;1/2 Pidana;2/3 Pidana;Usulan;Menarik Perhatian Kanwil;Keterangan\n";
        $csvContent .= "1;Doni Pratama;BI.210/2026;Narkotika;6 Tahun;2029-01-01;;;Penempatan / Mutasi;Tidak;Mutasi ke Lapas Terbuka\n";
        $csvContent .= "2;Eko Wahyudi;BI.211/2026;Pencurian;3 Tahun;2028-05-01;;;Mutasi;Tidak;Pemindahan pembinaan lanjutan\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'tpp_test_mutasi_').'.csv';
        file_put_contents($tempFile, $csvContent);

        $reflection = new \ReflectionClass(TppExcelService::class);
        $method = $reflection->getMethod('parseCsv');
        $method->setAccessible(true);

        $result = $method->invoke(null, $tempFile);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total']);

        $row1 = $result['data'][0];
        $this->assertEquals('Doni Pratama', $row1['nama_wbp']);
        $this->assertEquals('Penempatan/Mutasi', $row1['jenis_usulan']);
        $this->assertNull($row1['tanggal_setengah_pidana']);
        $this->assertNull($row1['tanggal_dua_pertiga']);

        $row2 = $result['data'][1];
        $this->assertEquals('Eko Wahyudi', $row2['nama_wbp']);
        $this->assertEquals('Penempatan/Mutasi', $row2['jenis_usulan']);

        @unlink($tempFile);
    }
}
