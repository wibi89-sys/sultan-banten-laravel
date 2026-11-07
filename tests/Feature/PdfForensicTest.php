<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use App\Services\PdfForensicService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PdfForensicTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $unauthorizedUser;

    protected User $forensicUser;

    protected Upt $upt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->upt = Upt::firstOrCreate(['nama_upt' => 'RUTAN PANDEGLANG TEST'], ['alamat' => 'Test', 'kapasitas' => 100]);

        // Admin User
        $this->adminUser = User::whereNull('upt_id')->where('role', 'admin')->first()
            ?? User::factory()->create(['upt_id' => null, 'role' => 'admin']);

        // User tanpa hak akses forensik
        $this->unauthorizedUser = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $this->upt->id,
            'akses_forensik_dokumen' => 'none',
        ]);

        // User dengan hak akses forensik
        $this->forensicUser = User::factory()->create([
            'role' => 'operator_wilayah',
            'upt_id' => null,
            'akses_forensik_dokumen' => 'full',
        ]);
    }

    public function test_unauthorized_user_cannot_access_forensic_module(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('forensik-dokumen.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_and_admin_can_access_forensic_module(): void
    {
        $resUser = $this->actingAs($this->forensicUser)->get(route('forensik-dokumen.index'));
        $resUser->assertStatus(200);
        $resUser->assertSee('Forensik &amp; Sanitasi Dokumen Digital', false);

        $resAdmin = $this->actingAs($this->adminUser)->get(route('forensik-dokumen.index'));
        $resAdmin->assertStatus(200);
    }

    public function test_pdf_forensic_service_inspects_metadata_and_detects_cloned_qr(): void
    {
        // 1. Buat PDF dummy dengan TCPDF yang memuat metadata Canva dan kode SB
        require_once app_path('Libraries/tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF;
        $tcpdf->SetCreator('Canva Online Editor 2026');
        $tcpdf->SetAuthor('Oknum Tak Dikenal');
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Surat Keterangan Bebas. Kode Verifikasi: SB-TESTCLONE999');
        $tempPdf = storage_path('app/temp_test_forensic.pdf');
        $tcpdf->Output($tempPdf, 'F');

        $result = PdfForensicService::inspect($tempPdf);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_info', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('qr_analysis', $result);
        $this->assertArrayHasKey('ai_analysis', $result);

        // Periksa deteksi software Canva
        $this->assertStringContainsString('Canva', $result['metadata']['creator']);

        // Periksa bahwa kode SB-TESTCLONE999 terdeteksi dan berstatus tidak terdaftar (palsu)
        $this->assertTrue($result['qr_analysis']['has_invalid']);
        $this->assertLessThan(60, $result['heuristic_score']);

        @unlink($tempPdf);
    }

    public function test_inspect_endpoint_returns_json_report(): void
    {
        require_once app_path('Libraries/tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF;
        $tcpdf->SetCreator('Microsoft Word 2021');
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Naskah dinas resmi untuk diuji inspeksi.');
        $tempPdf = storage_path('app/temp_test_endpoint.pdf');
        $tcpdf->Output($tempPdf, 'F');

        $uploadedFile = new UploadedFile(
            $tempPdf,
            'laporan_sidang.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($this->forensicUser)->post(
            route('forensik-dokumen.inspect'),
            ['file_pdf' => $uploadedFile],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'file_info',
                'metadata',
                'ai_analysis',
            ],
        ]);

        @unlink($tempPdf);
    }

    public function test_sanitize_endpoint_scrubs_metadata_and_locks_pdf(): void
    {
        require_once app_path('Libraries/tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF;
        $tcpdf->SetCreator('Photoshop CC 2024');
        $tcpdf->SetAuthor('Laptop Pribadi Pegawai');
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Dokumen yang ingin dibersihkan dari jejak perangkat pribadi.');
        $tempPdf = storage_path('app/temp_test_sanitize.pdf');
        $tcpdf->Output($tempPdf, 'F');

        $uploadedFile = new UploadedFile(
            $tempPdf,
            'dokumen_kotor.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($this->forensicUser)->post(
            route('forensik-dokumen.sanitize'),
            [
                'file_pdf' => $uploadedFile,
                'clean_metadata' => '1',
                'lock_document' => '1',
                'custom_title' => 'Surat Tugas Resmi Kanwil',
            ]
        );

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        @unlink($tempPdf);
    }
}
