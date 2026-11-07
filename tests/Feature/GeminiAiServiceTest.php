<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\GeminiAiService;
use App\Services\NotaDinasGeneratorService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiAiServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->adminUser = User::first() ?? User::create([
            'username' => 'admin_ai_test_'.time(),
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Admin AI Test',
            'role' => 'admin',
        ]);
    }

    public function test_build_generation_config_handles_gemini_3_and_2_correctly(): void
    {
        // Gemini 3.8 Flash uses thinkingLevel: LOW
        $config38 = GeminiAiService::buildGenerationConfig('gemini-3.8-flash', ['maxOutputTokens' => 1000]);
        $this->assertEquals(0.1, $config38['temperature']);
        $this->assertEquals(1000, $config38['maxOutputTokens']);
        $this->assertEquals(['thinkingLevel' => 'LOW'], $config38['thinkingConfig']);

        // Gemini 2.5 Flash uses thinkingBudget: 0
        $config25 = GeminiAiService::buildGenerationConfig('gemini-2.5-flash');
        $this->assertEquals(['thinkingBudget' => 0], $config25['thinkingConfig']);

        // Gemini 1.5 Flash does not include thinkingConfig
        $config15 = GeminiAiService::buildGenerationConfig('gemini-1.5-flash');
        $this->assertArrayNotHasKey('thinkingConfig', $config15);
    }

    public function test_extract_text_from_candidate_filters_out_thoughts(): void
    {
        // Candidate with reasoning/thought part followed by final answer
        $candidateWithThought = [
            'content' => [
                'role' => 'model',
                'parts' => [
                    [
                        'text' => 'Thinking process: analyzing document...',
                        'thought' => true,
                    ],
                    [
                        'text' => 'Koneksi Google Gemini API berhasil terhubung.',
                    ],
                ],
            ],
        ];

        $extracted = GeminiAiService::extractTextFromCandidate($candidateWithThought);
        $this->assertEquals('Koneksi Google Gemini API berhasil terhubung.', $extracted);

        // Standard single part candidate without thought
        $standardCandidate = [
            'content' => [
                'role' => 'model',
                'parts' => [
                    [
                        'text' => 'Simple answer without thought.',
                    ],
                ],
            ],
        ];

        $extractedStandard = GeminiAiService::extractTextFromCandidate($standardCandidate);
        $this->assertEquals('Simple answer without thought.', $extractedStandard);
    }

    public function test_clean_json_payload_removes_markdown_fences(): void
    {
        $raw = "```json\n{\"nomor_undangan\": \"123/2026\"}\n```";
        $cleaned = GeminiAiService::cleanJsonPayload($raw);
        $this->assertEquals('{"nomor_undangan": "123/2026"}', $cleaned);

        $plain = '{"nomor_undangan": "123/2026"}';
        $this->assertEquals('{"nomor_undangan": "123/2026"}', GeminiAiService::cleanJsonPayload($plain));
    }

    public function test_test_connection_succeeds_with_gemini_3_8_flash_mock(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Thinking internally...', 'thought' => true],
                                ['text' => 'Koneksi Google Gemini API berhasil terhubung.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = GeminiAiService::testConnection('fake_api_key_123', 'gemini-3.8-flash');

        $this->assertTrue($result['success']);
        $this->assertEquals('gemini-3.8-flash', $result['model']);
        $this->assertStringContainsString('Koneksi Google Gemini API berhasil terhubung.', $result['message']);
    }

    public function test_is_high_demand_error_detection(): void
    {
        $googleMsg = 'This model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.';
        $this->assertTrue(GeminiAiService::isHighDemandError($googleMsg, 503));
        $this->assertTrue(GeminiAiService::isHighDemandError('Resource exhausted', 429));
        $this->assertFalse(GeminiAiService::isHighDemandError('API key not valid. Please pass a valid API key.', 400));
    }

    public function test_test_connection_identifies_high_demand(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent*' => Http::response([
                'error' => [
                    'code' => 503,
                    'message' => 'This model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.',
                    'status' => 'UNAVAILABLE',
                ],
            ], 503),
        ]);

        $result = GeminiAiService::testConnection('fake_key', 'gemini-3.8-flash');

        $this->assertFalse($result['success']);
        $this->assertTrue($result['is_high_demand']);
        $this->assertEquals('gemini-2.5-flash', $result['suggested_model']);
        $this->assertStringContainsString('High Demand', $result['message']);
    }

    public function test_post_generate_content_with_fallback_auto_switches_when_high_demand(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent*' => Http::response([
                'error' => [
                    'code' => 503,
                    'message' => 'This model is currently experiencing high demand.',
                ],
            ], 503),
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"nomor_undangan": "W12.PAS/123/2026"}'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $res = GeminiAiService::postGenerateContentWithFallback(
            apiKey: 'fake_key',
            primaryModel: 'gemini-3.8-flash',
            contents: [['parts' => [['text' => 'Test']]]],
        );

        $this->assertNotNull($res['response']);
        $this->assertTrue($res['response']->successful());
        $this->assertEquals('gemini-2.5-flash', $res['model_used']);
        $this->assertTrue($res['is_fallback']);
    }

    public function test_admin_can_update_ai_setting_to_gemini_3_8_flash(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['_token' => 'test_token'])
            ->put(route('ai-setting.update'), [
                '_token' => 'test_token',
                'provider' => 'gemini',
                'api_key' => 'AIzaSyTestApiKey38',
                'model' => 'gemini-3.8-flash',
                'is_active' => '1',
                'nomor_surat_prefix' => 'WP.12-UM.01.01-',
                'yth_tujuan' => 'Kepala Bagian Tata Usaha dan Umum',
                'dari_pengirim' => 'Kepala Bidang Pelayanan dan Pembinaan',
                'hal_surat' => 'Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP',
                'penandatangan_nama' => 'Surya Dharma',
                'penandatangan_jabatan' => 'Kepala Bidang Pelayanan dan Pembinaan',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $setting = AiSetting::getFirst();
        $this->assertEquals('gemini-3.8-flash', $setting->model);
        $this->assertEquals('AIzaSyTestApiKey38', $setting->api_key);
        $this->assertTrue($setting->is_active);
    }

    public function test_draft_nota_dinas_narasi_generates_formal_eyd_compliant_output(): void
    {
        $setting = AiSetting::getFirst();
        $setting->update([
            'is_active' => true,
            'api_key' => 'fake_api_key',
            'model' => 'gemini-3.8-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'hal_surat' => 'Permohonan Surat Tugas Bintorwasdal Sidang TPP di Lapas Kelas I Tangerang',
                                        'narasi_pengantar' => 'Menindaklanjuti Surat Kepala Lapas Kelas I Tangerang Nomor WP.12.PAS1/123/2026, bersama ini kami mohon perkenan Bapak untuk menerbitkan Surat Tugas.',
                                        'catatan_urgensi' => 'Penting untuk verifikasi berkas usulan hak integrasi.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $res = GeminiAiService::draftNotaDinasNarasi([
            'upt_nama' => 'Lapas Kelas I Tangerang',
            'nomor_undangan' => 'WP.12.PAS1/123/2026',
            'tanggal_surat_upt' => '05 November 2026',
            'hal_undangan' => 'Undangan Sidang TPP',
            'hari_tanggal' => 'Jumat, 06 November 2026',
            'pukul' => '09.00 WIB s.d selesai',
            'tempat' => 'Ruang Sidang TPP',
        ]);

        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['data']['hal_surat']);
        $this->assertStringContainsString('Menindaklanjuti', $res['data']['narasi_pengantar']);
    }

    public function test_nota_dinas_generator_service_uses_dynamic_narasi_pengantar(): void
    {
        $customNarasi = 'Sehubungan dengan surat permohonan resmi dan dalam rangka penguatan pengawasan teknis pemasyarakatan, kami mohon diterbitkan surat tugas bagi personil berikut:';

        $html = NotaDinasGeneratorService::renderHtmlTemplate([
            'nomor_nota_dinas' => 'WP.12-UM.01.01-999',
            'upt_nama' => 'Lapas Pemuda Tangerang',
            'narasi_pengantar' => $customNarasi,
            'pegawai_list' => [
                ['nama' => 'Budi Santoso', 'nip' => '198501012010011001', 'jabatan' => 'Pembina Narapidana'],
            ],
        ]);

        $this->assertStringContainsString($customNarasi, $html);
        $this->assertStringContainsString('Budi Santoso', $html);
    }
}
