<?php

namespace Tests\Feature;

use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PembersihPdfTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Upt $upt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->upt = Upt::firstOrCreate(
            ['nama_upt' => 'RUTAN SERANG TEST'],
            ['alamat' => 'Serang Banten', 'kapasitas' => 300]
        );

        $this->user = User::factory()->create([
            'role' => 'operator',
            'upt_id' => $this->upt->id,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('pembersih-pdf.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_pembersih_pdf_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('pembersih-pdf.index'));

        $response->assertStatus(200);
        $response->assertSee('Pembersih PDF (Sanitasi Naskah Kedinasan)', false);
        $response->assertSee('RUTAN SERANG TEST', false);
        $response->assertSee('Srikandi', false);
    }

    public function test_user_without_utilitas_permission_gets_forbidden_403_on_pembersih_pdf(): void
    {
        $restrictedUser = User::factory()->create([
            'role' => 'operator',
            'akses_utilitas' => 'none',
        ]);

        $response = $this->actingAs($restrictedUser)->get(route('pembersih-pdf.index'));
        $response->assertStatus(403);

        $cleanResponse = $this->actingAs($restrictedUser)->postJson(route('pembersih-pdf.clean'), []);
        $cleanResponse->assertStatus(403);
    }

    public function test_clean_fails_when_no_file_uploaded(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('pembersih-pdf.clean'), []);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Silakan pilih berkas PDF', $response->json('message'));
    }

    public function test_clean_fails_when_file_is_not_pdf(): void
    {
        $docxFile = UploadedFile::fake()->create('naskah.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->user)->post(
            route('pembersih-pdf.clean'),
            ['file_pdf' => $docxFile],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Format berkas harus berekstensi .pdf', $response->json('message'));
    }

    public function test_clean_sanitizes_pdf_standardizes_metadata_and_provides_download(): void
    {
        require_once app_path('Libraries/tcpdf/tcpdf.php');

        $tcpdf = new \TCPDF;
        $tcpdf->SetCreator('Photoshop CC 2024');
        $tcpdf->SetAuthor('Laptop Pribadi Pegawai');
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Surat Keputusan Mutasi Pegawai - Naskah Asli untuk diunggah ke Srikandi.');
        $tempSource = storage_path('app/temp_test_source.pdf');
        $tcpdf->Output($tempSource, 'F');

        $uploadedFile = new UploadedFile(
            $tempSource,
            'sk_mutasi_pegawai.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($this->user)->post(
            route('pembersih-pdf.clean'),
            [
                'file_pdf' => $uploadedFile,
                'custom_title' => 'Surat Keputusan Mutasi Pegawai 2026',
                'custom_author' => 'Rutan Kelas IIB Serang',
                'custom_creator' => 'Aplikasi SULTAN Banten',
                'lock_document' => '1',
                'allow_copy' => '1',
            ],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'page_count',
                'original',
                'cleaned' => [
                    'title',
                    'creator',
                    'author',
                    'sha256',
                    'is_locked',
                ],
                'sanitized_items',
            ],
            'download_url',
            'download_name',
        ]);

        $this->assertEquals(1, $response->json('data.page_count'));
        $this->assertEquals('Surat Keputusan Mutasi Pegawai 2026', $response->json('data.cleaned.title'));
        $this->assertEquals('Rutan Kelas IIB Serang', $response->json('data.cleaned.author'));
        $this->assertTrue($response->json('data.cleaned.is_locked'));

        $sha256 = $response->json('data.cleaned.sha256');
        $this->assertEquals(64, strlen($sha256));

        // Test pengunduhan berkas bersih
        $downloadUrl = $response->json('download_url');
        $downloadResponse = $this->actingAs($this->user)->get($downloadUrl);
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'application/pdf');

        @unlink($tempSource);
    }

    public function test_download_fails_with_invalid_or_expired_token(): void
    {
        $response = $this->actingAs($this->user)->get(route('pembersih-pdf.download', ['token' => 'invalidtoken123']));
        $response->assertStatus(404);

        $fakeToken = bin2hex(random_bytes(16));
        $response404 = $this->actingAs($this->user)->get(route('pembersih-pdf.download', ['token' => $fakeToken]));
        $response404->assertStatus(404);
    }

    public function test_clean_fails_when_file_is_fake_non_pdf(): void
    {
        $fakePdf = UploadedFile::fake()->createWithContent('bukan_pdf.pdf', 'Ini bukan berkas PDF asli.');

        $response = $this->actingAs($this->user)->post(
            route('pembersih-pdf.clean'),
            ['file_pdf' => $fakePdf],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Format berkas tidak valid', $response->json('message'));
    }

    public function test_clean_fails_when_file_exceeds_30mb(): void
    {
        $tempPath = storage_path('app/temp_oversized_clean_test.pdf');
        $fp = fopen($tempPath, 'wb');
        fwrite($fp, "%PDF-1.4\n");
        fseek($fp, 32 * 1024 * 1024);
        fwrite($fp, "%%EOF\n");
        fclose($fp);

        $oversizedPdf = new UploadedFile(
            $tempPath,
            'oversized.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($this->user)->post(
            route('pembersih-pdf.clean'),
            ['file_pdf' => $oversizedPdf],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('melebihi batas maksimal 30 MB', $response->json('message'));

        @unlink($tempPath);
    }
}
