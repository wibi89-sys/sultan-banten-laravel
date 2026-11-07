<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\Upt;
use App\Models\User;
use App\Services\GeminiAiService;
use App\Services\PdfForensicService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_normal_document_with_nusakambangan_text_does_not_hallucinate_attachment_deletion(): void
    {
        require_once app_path('Libraries/tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF;
        $tcpdf->SetCreator('Microsoft Word 2021');
        $tcpdf->SetAuthor('Kementerian Imigrasi dan Pemasyarakatan');
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Surat Usulan Mutasi Narapidana ke Lapas Nusakambangan. Naskah dinas resmi dan sah.');
        $tempPdf = storage_path('app/temp_test_nusakambangan.pdf');
        $tcpdf->Output($tempPdf, 'F');

        $result = PdfForensicService::inspect($tempPdf);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_info', $result);
        $this->assertArrayHasKey('ai_analysis', $result);

        $ai = $result['ai_analysis'];
        $score = $ai['authenticity_score'] ?? $result['heuristic_score'];

        // Dokumen wajar harus memiliki skor keaslian tinggi (>= 85)
        $this->assertGreaterThanOrEqual(85, $score);
        $this->assertEquals('rendah', $ai['risk_level']);

        // Pastikan tidak ada anomali palsu tentang penghapusan lampiran
        $anomalies = $ai['anomalies'] ?? [];
        foreach ($anomalies as $anomaly) {
            $this->assertStringNotContainsStringIgnoringCase('penghapusan lampiran', $anomaly);
            $this->assertStringNotContainsStringIgnoringCase('nusakambangan', $anomaly);
        }

        @unlink($tempPdf);
    }

    public function test_gemini_forensic_filter_purges_hallucinated_nusakambangan_deletion(): void
    {
        // Simulasi respon Gemini AI dengan HTTP Fake
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'authenticity_score' => 60,
                                        'risk_level' => 'sedang',
                                        'status_label' => 'Perlu Klarifikasi',
                                        'summary' => 'Ditemukan indikasi penghapusan lampiran berkas.',
                                        'key_findings' => ['Dokumen dibuat oleh Microsoft Word.'],
                                        'anomalies' => [
                                            'Terdeteksi penghapusan lampiran nusakambangan pada naskah.',
                                        ],
                                        'recommendation' => 'Periksa fisik dokumen.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $setting = AiSetting::getFirst();
        if ($setting) {
            $setting->update([
                'is_active' => true,
                'api_key' => 'fake_api_key_for_testing',
            ]);
        }

        $result = GeminiAiService::analyzeForensicPdf([
            'file_name' => 'usulan_mutasi_nusakambangan.pdf',
            'file_size' => '250 KB',
            'doc_type_hint' => 'Dokumen Digital Asli',
            'pdf_version' => '1.7',
            'creator' => 'Microsoft Word 2019',
            'producer' => 'Microsoft: Print to PDF',
            'creation_date' => '2026-11-07 10:00:00',
            'mod_date' => '2026-11-07 10:00:00',
            'revisions_count' => 1,
            'detected_suspicious_software' => [],
            'qr_verification_results' => [],
            'sample_text' => 'Mutasi Narapidana ke Lapas Nusakambangan',
        ]);

        // Verifikasi anomali palsu 'penghapusan lampiran nusakambangan' berhasil dibersihkan
        $this->assertEmpty($result['anomalies']);
        $this->assertGreaterThanOrEqual(85, $result['authenticity_score']);
        $this->assertEquals('rendah', $result['risk_level']);
        $this->assertEquals('Dokumen Terindikasi Wajar / Asli', $result['status_label']);
    }

    public function test_forensic_detects_actual_deleted_nusakambangan_attachment(): void
    {
        // Buat PDF yang memiliki 1 halaman aktif (Surat Pengantar), namun di dalam stream tersembunyi (slack space)
        // memuat naskah lampiran narapidana Nusakambangan yang telah dipotong/dihapus
        require_once app_path('Libraries/tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF;
        $tcpdf->SetCreator('Microsoft Word 2021');
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Surat Pengantar Usulan Mutasi Narapidana Kanwil Banten.');
        $tempPdf = storage_path('app/temp_test_tampered_nusakambangan.pdf');
        $tcpdf->Output($tempPdf, 'F');

        // Simulasikan penyuntingan/penghapusan lampiran dengan menyisipkan objek stream lampiran Nusakambangan
        // yang tertinggal dalam berkas (orphan / slack space) dengan revisi baru
        $originalRaw = file_get_contents($tempPdf);
        $orphanStream = "15 0 obj\n<< /Length 120 >>\nstream\nBT /F1 12 Tf (Lampiran Usulan Pemindahan Narapidana ke Lapas Nusakambangan: 1. Terpidana A 2. Terpidana B) Tj ET\nendstream\nendobj\n";
        $simulatedIncremental = $originalRaw."\n".$orphanStream."%%EOF\n";
        file_put_contents($tempPdf, $simulatedIncremental);

        $result = PdfForensicService::inspect($tempPdf);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('deleted_content', $result);
        $this->assertTrue($result['deleted_content']['has_deletion']);

        // Verifikasi bahwa bukti memuat kata Nusakambangan
        $evidenceStr = implode(' ', $result['deleted_content']['evidence']);
        $this->assertStringContainsStringIgnoringCase('Nusakambangan', $evidenceStr);

        // Verifikasi skor anjlok karena terbukti ada pemotongan
        $this->assertLessThanOrEqual(35, $result['ai_analysis']['authenticity_score']);
        $this->assertEquals('tinggi', $result['ai_analysis']['risk_level']);
        $this->assertStringContainsStringIgnoringCase('Manipulasi', $result['ai_analysis']['status_label']);

        @unlink($tempPdf);
    }
}
